<?php

namespace App\Http\Requests\Tickets;

use App\Models\Ticket;
use App\Models\TicketStatusTransition;
use App\Models\User;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Edición controlada de tickets vía PATCH (FASE 6.2 §28-§31).
 *
 * Orden de respuestas: 404 fuera de alcance (anti-IDOR), 409 sobre ticket
 * terminal (read-only), 403 por rol/campo y 422 por validación. La
 * transición de estado inválida se resuelve en el controlador (409) porque
 * depende del estado resultante de la misma petición (auto-asignación).
 */
class UpdateTicketRequest extends TicketFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        /** @var Ticket $ticket */
        $ticket = $this->route('ticket');

        if (! Gate::forUser($user)->allows('view', $ticket)) {
            abort(404, 'Ticket no encontrado.');
        }

        if ($ticket->status->is_terminal) {
            abort(409, 'Los tickets cerrados o cancelados no pueden modificarse.');
        }

        if (! Gate::forUser($user)->allows('update', $ticket)) {
            throw new AuthorizationException('No tiene permisos para realizar esta acción.');
        }

        $this->assertFieldsAreAllowed($user, [
            'ADMIN' => [
                'subject',
                'description',
                'asset_id',
                'organizational_unit_id',
                'priority',
                'ticket_type',
                'category_id',
                'subcategory_id',
                'assigned_technician_id',
                'status',
                'resolution_notes',
                'notes',
            ],
            'TECH' => ['status', 'resolution_notes', 'notes'],
            'REQUESTER' => ['subject', 'description', 'asset_id', 'status'],
        ]);

        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'subject' => ['sometimes', 'string', 'max:200'],
            'description' => ['sometimes', 'string', 'max:5000'],
            'asset_id' => ['nullable', 'integer', Rule::exists('assets', 'id')],
            'organizational_unit_id' => ['nullable', 'integer', Rule::exists('organizational_units', 'id')->where('is_active', true)],
            'priority' => ['sometimes', 'string', Rule::exists('ticket_priorities', 'code')->where('is_active', true)],
            'ticket_type' => ['sometimes', 'string', Rule::exists('ticket_types', 'code')->where('is_active', true)],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'subcategory_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'assigned_technician_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'status' => ['sometimes', 'string', Rule::exists('ticket_statuses', 'code')->where('is_active', true)],
            'resolution_notes' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'subject.string' => 'El asunto debe ser un texto.',
            'subject.max' => 'El asunto no debe superar los 200 caracteres.',
            'description.string' => 'La descripción debe ser un texto.',
            'description.max' => 'La descripción no debe superar los 5000 caracteres.',
            'asset_id.integer' => 'El activo debe ser un identificador numérico.',
            'asset_id.exists' => 'El activo indicado no existe.',
            'organizational_unit_id.integer' => 'La unidad organizacional debe ser un identificador numérico.',
            'organizational_unit_id.exists' => 'La unidad organizacional indicada no existe o no está activa.',
            'priority.exists' => 'La prioridad indicada no existe o no está activa.',
            'ticket_type.exists' => 'El tipo de ticket indicado no existe o no está activo.',
            'category_id.integer' => 'La categoría debe ser un identificador numérico.',
            'category_id.exists' => 'La categoría indicada no existe o no está activa.',
            'subcategory_id.integer' => 'La subcategoría debe ser un identificador numérico.',
            'subcategory_id.exists' => 'La subcategoría indicada no existe o no está activa.',
            'assigned_technician_id.integer' => 'El técnico asignado debe ser un identificador numérico.',
            'assigned_technician_id.exists' => 'El técnico asignado no existe o no está activo.',
            'status.exists' => 'El estado indicado no existe o no está activo.',
            'resolution_notes.string' => 'Las notas de resolución deben ser un texto.',
            'resolution_notes.max' => 'Las notas de resolución no deben superar los 5000 caracteres.',
            'notes.string' => 'Las notas deben ser un texto.',
            'notes.max' => 'Las notas no deben superar los 5000 caracteres.',
        ];
    }

    /**
     * @return Closure(ValidatorContract): void
     */
    public function after(): Closure
    {
        return function (ValidatorContract $validator): void {
            $this->validateCategoryPair($validator);
            $this->validateAssetAvailability($validator);
            $this->validateAssignedTechnicianRole($validator);
            $this->validateStatusChangeRequirements($validator);
        };
    }

    /**
     * assigned_technician_id (solo ADMIN) debe apuntar a un usuario con rol TECH.
     */
    private function validateAssignedTechnicianRole(ValidatorContract $validator): void
    {
        if (! $this->filled('assigned_technician_id')) {
            return;
        }

        /** @var User|null $technician */
        $technician = User::query()->find($this->input('assigned_technician_id'));

        if ($technician === null) {
            return;
        }

        if (! $technician->roles->contains('code', 'TECH')) {
            $validator->errors()->add('assigned_technician_id', 'El usuario indicado no tiene el rol TECH.');
        }
    }

    /**
     * Motivo (notes) y notas de resolución exigidos por la transición
     * resuelta (docs §G.3.4); notes solo viaja con cambio de estado.
     */
    private function validateStatusChangeRequirements(ValidatorContract $validator): void
    {
        if (! $this->filled('status')) {
            if ($this->filled('notes')) {
                $validator->errors()->add('notes', 'El campo notes solo puede enviarse junto con un cambio de estado.');
            }

            return;
        }

        /** @var Ticket $ticket */
        $ticket = $this->route('ticket');

        $transition = TicketStatusTransition::resolve($ticket->status->code, (string) $this->input('status'));

        if ($transition === null) {
            // Par de estados inválido: el controlador responde 409.
            return;
        }

        if ($transition['requires_reason'] && ! $this->filled('notes')) {
            $validator->errors()->add('notes', 'Debe indicar el motivo del cambio de estado.');
        }

        if ($this->input('status') === 'RESOLVED' && ! $this->filled('resolution_notes')) {
            $validator->errors()->add('resolution_notes', 'Debe indicar las notas de resolución al resolver el ticket.');
        }
    }
}
