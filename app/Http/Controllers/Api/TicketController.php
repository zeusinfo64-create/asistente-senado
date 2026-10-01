<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tickets\StoreTicketRequest;
use App\Http\Requests\Tickets\TicketQueryRequest;
use App\Http\Requests\Tickets\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Models\TicketEvent;
use App\Models\TicketPriority;
use App\Models\TicketSequence;
use App\Models\TicketStatus;
use App\Models\TicketStatusTransition;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * API core de tickets (FASE 6.2).
 *
 * Endpoints: listado paginado con filtros, creación, detalle y edición
 * controlada (asignación, ciclo de vida, campos permitidos por rol). Cada
 * cambio genera su evento en ticket_events (historial append-only).
 */
class TicketController extends Controller
{
    /**
     * Listado paginado dentro del alcance del rol (§34-§37).
     *
     * ADMIN ve todos, REQUESTER los que solicita, TECH los asignados y un
     * usuario sin rol ve la lista vacía. Los filtros se aplican encima del
     * alcance: nunca lo amplían.
     */
    public function index(TicketQueryRequest $request): JsonResponse
    {
        $filters = $request->validated();

        /** @var User $user */
        $user = $request->user();
        $roleCodes = $user->roles->pluck('code');

        $query = Ticket::query()->with($this->eagerLoads());

        if (! $roleCodes->contains('ADMIN')) {
            $query->where(function (Builder $scope) use ($roleCodes, $user): void {
                $hasClause = false;

                if ($roleCodes->contains('REQUESTER')) {
                    $scope->orWhere('requester_id', $user->id);
                    $hasClause = true;
                }

                if ($roleCodes->contains('TECH')) {
                    $scope->orWhere('assigned_to_id', $user->id);
                    $hasClause = true;
                }

                if (! $hasClause) {
                    $scope->whereRaw('1 = 0');
                }
            });
        }

        $query
            ->when(
                $filters['status'] ?? null,
                fn (Builder $q, string $value): Builder => $q->whereHas(
                    'status',
                    fn (Builder $statusQuery): Builder => $statusQuery->where('code', $value),
                ),
            )
            ->when(
                $filters['priority'] ?? null,
                fn (Builder $q, string $value): Builder => $q->whereHas(
                    'priority',
                    fn (Builder $priorityQuery): Builder => $priorityQuery->where('code', $value),
                ),
            )
            ->when(
                $filters['ticket_type'] ?? null,
                fn (Builder $q, string $value): Builder => $q->whereHas(
                    'ticketType',
                    fn (Builder $typeQuery): Builder => $typeQuery->where('code', $value),
                ),
            )
            ->when(
                $filters['category'] ?? null,
                fn (Builder $q, int $value): Builder => $q->where('category_id', $value),
            )
            ->when(
                $filters['subcategory'] ?? null,
                fn (Builder $q, int $value): Builder => $q->where('subcategory_id', $value),
            )
            ->when(
                $filters['assigned_technician'] ?? null,
                fn (Builder $q, int $value): Builder => $q->where('assigned_to_id', $value),
            )
            ->when(
                $filters['organizational_unit'] ?? null,
                fn (Builder $q, int $value): Builder => $q->where('organizational_unit_id', $value),
            )
            ->when(
                $filters['date_from'] ?? null,
                fn (Builder $q, string $value): Builder => $q->whereDate('created_at', '>=', $value),
            )
            ->when(
                $filters['date_to'] ?? null,
                fn (Builder $q, string $value): Builder => $q->whereDate('created_at', '<=', $value),
            )
            ->when(
                $filters['search'] ?? null,
                fn (Builder $q, string $value): Builder => $q->where(
                    fn (Builder $group): Builder => $group
                        ->where('ticket_number', 'like', "%{$value}%")
                        ->orWhere('subject', 'like', "%{$value}%")
                        ->orWhere('description', 'like', "%{$value}%"),
                ),
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $paginator = $query->paginate((int) ($filters['per_page'] ?? 15))->withQueryString();

        return response()->json([
            'data' => TicketResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Creación (201). El servidor fija número (TCK-2026-000125, docs §D.13),
     * estado inicial REGISTERED, canal web y registra el evento "created".
     *
     * 403: TECH sin capacidad de creación. 422: validación.
     */
    public function store(StoreTicketRequest $request): JsonResponse
    {
        $data = $request->validated();

        /** @var User $user */
        $user = $request->user();

        $ticket = DB::transaction(function () use ($data, $user): Ticket {
            $requesterId = (int) ($data['requester_id'] ?? $user->id);

            $unitId = $data['organizational_unit_id']
                ?? User::query()->whereKey($requesterId)->value('organizational_unit_id')
                ?? $user->organizational_unit_id;

            $ticket = new Ticket;
            $ticket->ticket_number = TicketSequence::nextNumber();
            $ticket->ticket_type_id = TicketType::query()->where('code', $data['ticket_type'])->firstOrFail()->id;
            $ticket->requester_id = $requesterId;
            $ticket->created_by_id = $user->id;
            $ticket->organizational_unit_id = $unitId;
            $ticket->category_id = $data['category_id'] ?? null;
            $ticket->subcategory_id = $data['subcategory_id'] ?? null;
            $ticket->priority_id = TicketPriority::query()->where('code', $data['priority'])->firstOrFail()->id;
            $ticket->status_id = TicketStatus::query()->where('code', 'REGISTERED')->firstOrFail()->id;
            $ticket->asset_id = $data['asset_id'] ?? null;
            $ticket->subject = $data['subject'];
            $ticket->description = $data['description'];
            $ticket->channel = 'web';
            $ticket->save();

            $this->recordEvent($ticket, $user, 'created', [
                'to_status_id' => $ticket->status_id,
                'to_priority_id' => $ticket->priority_id,
            ]);

            return $ticket;
        });

        $ticket->load($this->eagerLoads());

        return response()->json([
            'message' => 'Ticket creado correctamente.',
            'data' => new TicketResource($ticket),
        ], 201);
    }

    /**
     * Detalle con historial (events en orden cronológico).
     *
     * 404: ticket inexistente o fuera del alcance del rol (anti-IDOR).
     */
    public function show(Request $request, Ticket $ticket): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! Gate::forUser($user)->allows('view', $ticket)) {
            abort(404, 'Ticket no encontrado.');
        }

        $ticket->load($this->eagerLoads());
        $ticket->load([
            'events' => function (Relation $query): void {
                $query->with([
                    'actor',
                    'fromStatus',
                    'toStatus',
                    'fromPriority',
                    'toPriority',
                    'fromAssignee',
                    'toAssignee',
                    'fromCategory',
                    'toCategory',
                ])->orderBy('created_at')->orderBy('id');
            },
        ]);

        return response()->json(['data' => new TicketResource($ticket)]);
    }

    /**
     * Edición controlada (200). Orden de comprobaciones ya aplicado por el
     * FormRequest: 404 fuera de alcance, 409 ticket terminal, 403 rol/campo,
     * 422 validación. Aquí se resuelven transición (409) y eventos.
     *
     * La asignación sobre REGISTERED auto-transiciona a ASSIGNED emitiendo un
     * único evento "assigned" con el cambio de asignado y de estado.
     */
    public function update(UpdateTicketRequest $request, Ticket $ticket): JsonResponse
    {
        $data = $request->validated();

        /** @var User $user */
        $user = $request->user();

        if (array_key_exists('assigned_technician_id', $data)
            && ! Gate::forUser($user)->allows('assign', $ticket)) {
            throw new AuthorizationException('No tiene permisos para realizar esta acción.');
        }

        DB::transaction(function () use ($data, $user, $ticket): void {
            $eventQueue = [];

            if (array_key_exists('assigned_technician_id', $data)) {
                $assignmentEvent = $this->assign($ticket, $user, $data['assigned_technician_id']);

                if ($assignmentEvent !== null) {
                    $eventQueue[] = $assignmentEvent;
                }
            }

            if (is_string($data['status'] ?? null)) {
                $notes = $data['notes'] ?? $data['resolution_notes'] ?? null;

                $eventQueue[] = $this->changeStatus($ticket, $user, $data['status'], $notes);
            }

            if (is_string($data['priority'] ?? null) && $data['priority'] !== $ticket->priority?->code) {
                $newPriority = TicketPriority::query()->where('code', $data['priority'])->firstOrFail();

                $eventQueue[] = [
                    'event_type' => 'priority_changed',
                    'attributes' => [
                        'from_priority_id' => $ticket->priority_id,
                        'to_priority_id' => $newPriority->id,
                    ],
                ];

                $ticket->priority_id = $newPriority->id;
                $ticket->setRelation('priority', $newPriority);
            }

            if (is_string($data['ticket_type'] ?? null) && $data['ticket_type'] !== $ticket->ticketType?->code) {
                $newType = TicketType::query()->where('code', $data['ticket_type'])->firstOrFail();

                $eventQueue[] = [
                    'event_type' => 'type_changed',
                    'attributes' => [
                        'data' => [
                            'from_ticket_type_id' => $ticket->ticket_type_id,
                            'to_ticket_type_id' => $newType->id,
                        ],
                    ],
                ];

                $ticket->ticket_type_id = $newType->id;
                $ticket->setRelation('ticketType', $newType);
            }

            $this->applyCategoryChange($ticket, $data, $eventQueue);

            if (array_key_exists('subject', $data)) {
                $ticket->subject = $data['subject'];
            }

            if (array_key_exists('description', $data)) {
                $ticket->description = $data['description'];
            }

            if (array_key_exists('asset_id', $data)) {
                $ticket->asset_id = $data['asset_id'];
            }

            if (array_key_exists('organizational_unit_id', $data)) {
                $ticket->organizational_unit_id = $data['organizational_unit_id'];
            }

            if (array_key_exists('resolution_notes', $data)) {
                $ticket->resolution_notes = $data['resolution_notes'];
            }

            $ticket->save();

            foreach ($eventQueue as $queued) {
                $this->recordEvent($ticket, $user, $queued['event_type'], $queued['attributes']);
            }
        });

        $ticket->load($this->eagerLoads());

        return response()->json([
            'message' => 'Ticket actualizado correctamente.',
            'data' => new TicketResource($ticket),
        ]);
    }

    /**
     * Relaciones eagerly cargadas con las columnas exactas que expone el
     * recurso (definen qué se serializa).
     *
     * @return array<int, string>
     */
    private function eagerLoads(): array
    {
        return [
            'ticketType:id,code,name',
            'status:id,code,name,color,is_terminal',
            'priority:id,code,name,color',
            'requester:id,first_name,last_name,email',
            'createdBy:id,first_name,last_name,email',
            'assignedTechnician:id,first_name,last_name,email',
            'organizationalUnit:id,code,name',
            'category:id,name',
            'subcategory:id,name',
            'asset:id,asset_code,brand,model',
        ];
    }

    /**
     * Asignación/reasignación con evento "assigned" (docs §G.2.1).
     *
     * @param  int|string|null  $assigneeId
     * @return array{event_type: string, attributes: array<string, mixed>}|null null si no cambia.
     */
    private function assign(Ticket $ticket, User $user, $assigneeId): ?array
    {
        $currentId = $ticket->assigned_to_id === null ? null : (int) $ticket->assigned_to_id;
        $assigneeId = $assigneeId === null ? null : (int) $assigneeId;

        if ($assigneeId === $currentId) {
            return null;
        }

        $event = [
            'event_type' => 'assigned',
            'attributes' => [
                'from_assignee_id' => $currentId,
                'to_assignee_id' => $assigneeId,
            ],
        ];

        $ticket->assigned_to_id = $assigneeId;

        if ($assigneeId !== null && $ticket->assigned_at === null) {
            $ticket->assigned_at = now();
        }

        if ($assigneeId !== null && $ticket->status?->code === 'REGISTERED') {
            $assignedStatus = TicketStatus::query()->where('code', 'ASSIGNED')->firstOrFail();

            $event['attributes']['from_status_id'] = $ticket->status_id;
            $event['attributes']['to_status_id'] = $assignedStatus->id;

            $ticket->status_id = $assignedStatus->id;
            $ticket->setRelation('status', $assignedStatus);
        }

        return $event;
    }

    /**
     * Transición de estado con su evento ("status_changed", "resolved" o
     * "closed") y marcas de tiempo (docs §G.3).
     *
     * 409: par de estados no permitido. 403: rol no admitido por la
     * transición.
     *
     * @return array{event_type: string, attributes: array<string, mixed>}
     */
    private function changeStatus(Ticket $ticket, User $user, string $toCode, ?string $notes): array
    {
        $fromStatus = $ticket->status;

        $transition = TicketStatusTransition::resolve($fromStatus->code, $toCode);

        if ($transition === null) {
            abort(409, 'Transición de estado no permitida.');
        }

        if (! Gate::forUser($user)->allows('changeStatus', [$ticket, $toCode])) {
            throw new AuthorizationException('No tiene permisos para realizar esta acción.');
        }

        $toStatus = TicketStatus::query()->where('code', $toCode)->firstOrFail();

        $ticket->status_id = $toStatus->id;
        $ticket->setRelation('status', $toStatus);

        match ($toCode) {
            'IN_PROGRESS' => $ticket->started_at ??= now(),
            'RESOLVED' => $ticket->resolved_at = now(),
            'CLOSED' => $ticket->closed_at = now(),
            default => null,
        };

        $eventType = match ($toCode) {
            'RESOLVED' => 'resolved',
            'CLOSED' => 'closed',
            default => 'status_changed',
        };

        return [
            'event_type' => $eventType,
            'attributes' => [
                'from_status_id' => $fromStatus->id,
                'to_status_id' => $toStatus->id,
                'notes' => $notes,
            ],
        ];
    }

    /**
     * Cambio de categoría/subcategoría con evento "category_changed"
     * (subcategorías en data, docs §D.13).
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array{event_type: string, attributes: array<string, mixed>}>  $eventQueue
     */
    private function applyCategoryChange(Ticket $ticket, array $data, array &$eventQueue): void
    {
        if (! array_key_exists('category_id', $data) && ! array_key_exists('subcategory_id', $data)) {
            return;
        }

        $fromCategoryId = $ticket->category_id === null ? null : (int) $ticket->category_id;
        $fromSubcategoryId = $ticket->subcategory_id === null ? null : (int) $ticket->subcategory_id;
        $toCategoryId = array_key_exists('category_id', $data)
            ? ($data['category_id'] === null ? null : (int) $data['category_id'])
            : $fromCategoryId;
        $toSubcategoryId = array_key_exists('subcategory_id', $data)
            ? ($data['subcategory_id'] === null ? null : (int) $data['subcategory_id'])
            : $fromSubcategoryId;

        if ($toCategoryId === $fromCategoryId && $toSubcategoryId === $fromSubcategoryId) {
            return;
        }

        $eventQueue[] = [
            'event_type' => 'category_changed',
            'attributes' => [
                'from_category_id' => $fromCategoryId,
                'to_category_id' => $toCategoryId,
                'data' => [
                    'from_subcategory_id' => $fromSubcategoryId,
                    'to_subcategory_id' => $toSubcategoryId,
                ],
            ],
        ];

        $ticket->category_id = $toCategoryId;
        $ticket->subcategory_id = $toSubcategoryId;
    }

    /**
     * Inserta en el historial sin asignación masiva (los modelos no tienen
     * $fillable, convención del proyecto).
     *
     * @param  array<string, mixed>  $attributes
     */
    private function recordEvent(Ticket $ticket, User $user, string $eventType, array $attributes): TicketEvent
    {
        $event = new TicketEvent;
        $event->ticket_id = $ticket->id;
        $event->actor_id = $user->id;
        $event->event_type = $eventType;

        foreach ($attributes as $column => $value) {
            $event->{$column} = $value;
        }

        $event->save();

        return $event;
    }
}
