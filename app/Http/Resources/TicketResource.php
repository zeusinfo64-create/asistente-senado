<?php

namespace App\Http\Resources;

use App\Models\Ticket;
use App\Models\TicketEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representación completa de un ticket (FASE 6.2 §33).
 *
 * Las fechas se serializan en ISO 8601 y la clave "events" solo aparece
 * cuando la relación está cargada (detalle), nunca en el listado.
 *
 * @property-read Ticket $resource
 */
class TicketResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Ticket $ticket */
        $ticket = $this->resource;

        $payload = [
            'id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'subject' => $ticket->subject,
            'description' => $ticket->description,
            'resolution_notes' => $ticket->resolution_notes,
            'channel' => $ticket->channel,
            'classification_source' => $ticket->classification_source,
            'type' => [
                'code' => $ticket->ticketType?->code,
                'name' => $ticket->ticketType?->name,
            ],
            'status' => [
                'code' => $ticket->status?->code,
                'name' => $ticket->status?->name,
                'color' => $ticket->status?->color,
                'is_terminal' => (bool) $ticket->status?->is_terminal,
            ],
            'priority' => [
                'code' => $ticket->priority?->code,
                'name' => $ticket->priority?->name,
                'color' => $ticket->priority?->color,
            ],
            'requester' => $this->userSummary($ticket->requester),
            'created_by' => $this->userSummary($ticket->createdBy),
            'assigned_technician' => $this->userSummary($ticket->assignedTechnician),
            'organizational_unit' => $ticket->organizationalUnit === null ? null : [
                'id' => $ticket->organizationalUnit->id,
                'code' => $ticket->organizationalUnit->code,
                'name' => $ticket->organizationalUnit->name,
            ],
            'category' => $ticket->category === null ? null : [
                'id' => $ticket->category->id,
                'name' => $ticket->category->name,
            ],
            'subcategory' => $ticket->subcategory === null ? null : [
                'id' => $ticket->subcategory->id,
                'name' => $ticket->subcategory->name,
            ],
            'asset' => $ticket->asset === null ? null : [
                'id' => $ticket->asset->id,
                'asset_code' => $ticket->asset->asset_code,
                'brand' => $ticket->asset->brand,
                'model' => $ticket->asset->model,
            ],
            'dates' => [
                'created_at' => $ticket->created_at?->toIso8601String(),
                'updated_at' => $ticket->updated_at?->toIso8601String(),
                'assigned_at' => $ticket->assigned_at?->toIso8601String(),
                'started_at' => $ticket->started_at?->toIso8601String(),
                'first_response_at' => $ticket->first_response_at?->toIso8601String(),
                'resolved_at' => $ticket->resolved_at?->toIso8601String(),
                'closed_at' => $ticket->closed_at?->toIso8601String(),
            ],
            'sla' => [
                'policy_id' => $ticket->sla_policy_id,
                'first_response_due_at' => $ticket->sla_first_response_due_at?->toIso8601String(),
                'resolution_due_at' => $ticket->sla_resolution_due_at?->toIso8601String(),
                'response_breached' => $ticket->sla_response_breached,
                'resolution_breached' => $ticket->sla_resolution_breached,
            ],
        ];

        if ($ticket->relationLoaded('events')) {
            $payload['events'] = $ticket->events
                ->map(fn (TicketEvent $event): array => $this->eventPayload($event))
                ->values()
                ->all();
        }

        return $payload;
    }

    /**
     * Resumen mínimo de usuario expuesto en el ticket.
     *
     * @return array{id: int, full_name: string, email: string}|null
     */
    private function userSummary(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'id' => $user->id,
            'full_name' => trim($user->first_name.' '.$user->last_name),
            'email' => $user->email,
        ];
    }

    /**
     * Evento del historial con los códigos de los estados/prioridades.
     *
     * @return array<string, mixed>
     */
    private function eventPayload(TicketEvent $event): array
    {
        return [
            'id' => $event->id,
            'event_type' => $event->event_type,
            'actor' => $event->actor === null ? null : [
                'id' => $event->actor->id,
                'full_name' => trim($event->actor->first_name.' '.$event->actor->last_name),
            ],
            'from_status' => $event->fromStatus?->code,
            'to_status' => $event->toStatus?->code,
            'from_priority' => $event->fromPriority?->code,
            'to_priority' => $event->toPriority?->code,
            'from_assignee' => $this->assigneeSummary($event->fromAssignee),
            'to_assignee' => $this->assigneeSummary($event->toAssignee),
            'from_category' => $event->fromCategory === null ? null : [
                'id' => $event->fromCategory->id,
                'name' => $event->fromCategory->name,
            ],
            'to_category' => $event->toCategory === null ? null : [
                'id' => $event->toCategory->id,
                'name' => $event->toCategory->name,
            ],
            'notes' => $event->notes,
            'data' => $event->data,
            'created_at' => $event->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array{id: int, full_name: string}|null
     */
    private function assigneeSummary(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'id' => $user->id,
            'full_name' => trim($user->first_name.' '.$user->last_name),
        ];
    }
}
