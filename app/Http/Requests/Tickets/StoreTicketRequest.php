<?php

namespace App\Http\Requests\Tickets;

use App\Models\Ticket;
use App\Models\User;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Creación de tickets (FASE 6.2 §35).
 *
 * El estado inicial, el canal y el número de ticket los fija el servidor.
 * REQUESTER no puede crear para otra unidad organizacional; ADMIN puede
 * crear en nombre de otro solicitante (requester_id).
 */
class StoreTicketRequest extends TicketFormRequest
{
    /**
     * El solicitante que no envía unidad usa la suya propia.
     */
    public function prepareForValidation(): void
    {
        $user = $this->user();

        if ($user !== null
            && $user->roles->contains('code', 'REQUESTER')
            && ! $user->roles->contains('code', 'ADMIN')
            && $user->organizational_unit_id !== null
            && ! $this->filled('organizational_unit_id')) {
            $this->merge(['organizational_unit_id' => $user->organizational_unit_id]);
        }
    }

    /**
     * 403: rol sin capacidad de creación (TECH), campos no permitidos al rol
     * o solicitante de otra unidad organizacional.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        if (! Gate::forUser($user)->allows('create', Ticket::class)) {
            throw new AuthorizationException('No tiene permisos para realizar esta acción.');
        }

        $this->assertFieldsAreAllowed($user, [
            'ADMIN' => [
                'subject',
                'description',
                'ticket_type',
                'priority',
                'category_id',
                'subcategory_id',
                'organizational_unit_id',
                'asset_id',
                'requester_id',
            ],
            'REQUESTER' => [
                'subject',
                'description',
                'ticket_type',
                'priority',
                'category_id',
                'subcategory_id',
                'organizational_unit_id',
                'asset_id',
            ],
            'TECH' => [],
        ]);

        if ($user->roles->contains('code', 'REQUESTER')
            && ! $user->roles->contains('code', 'ADMIN')
            && $user->organizational_unit_id !== null
            && $this->filled('organizational_unit_id')
            && (int) $this->input('organizational_unit_id') !== (int) $user->organizational_unit_id) {
            throw new AuthorizationException('No puede crear tickets para otra unidad organizacional.');
        }

        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:5000'],
            'ticket_type' => ['required', 'string', Rule::exists('ticket_types', 'code')->where('is_active', true)],
            'priority' => ['required', 'string', Rule::exists('ticket_priorities', 'code')->where('is_active', true)],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'subcategory_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'organizational_unit_id' => ['nullable', 'integer', Rule::exists('organizational_units', 'id')->where('is_active', true)],
            'asset_id' => ['nullable', 'integer', Rule::exists('assets', 'id')],
            'requester_id' => ['sometimes', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'subject.required' => 'El asunto es obligatorio.',
            'subject.string' => 'El asunto debe ser un texto.',
            'subject.max' => 'El asunto no debe superar los 200 caracteres.',
            'description.required' => 'La descripción es obligatoria.',
            'description.string' => 'La descripción debe ser un texto.',
            'description.max' => 'La descripción no debe superar los 5000 caracteres.',
            'ticket_type.required' => 'El tipo de ticket es obligatorio.',
            'ticket_type.exists' => 'El tipo de ticket indicado no existe o no está activo.',
            'priority.required' => 'La prioridad es obligatoria.',
            'priority.exists' => 'La prioridad indicada no existe o no está activa.',
            'category_id.integer' => 'La categoría debe ser un identificador numérico.',
            'category_id.exists' => 'La categoría indicada no existe o no está activa.',
            'subcategory_id.integer' => 'La subcategoría debe ser un identificador numérico.',
            'subcategory_id.exists' => 'La subcategoría indicada no existe o no está activa.',
            'organizational_unit_id.integer' => 'La unidad organizacional debe ser un identificador numérico.',
            'organizational_unit_id.exists' => 'La unidad organizacional indicada no existe o no está activa.',
            'asset_id.integer' => 'El activo debe ser un identificador numérico.',
            'asset_id.exists' => 'El activo indicado no existe.',
            'requester_id.integer' => 'El solicitante debe ser un identificador numérico.',
            'requester_id.exists' => 'El solicitante indicado no existe o no está activo.',
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
            $this->validateRequesterRole($validator);
            $this->validateOrganizationalUnitPresence($validator);
        };
    }

    /**
     * requester_id (solo ADMIN) debe apuntar a un usuario con rol REQUESTER.
     */
    private function validateRequesterRole(ValidatorContract $validator): void
    {
        if (! $this->filled('requester_id')) {
            return;
        }

        /** @var User|null $requester */
        $requester = User::query()->find($this->input('requester_id'));

        if ($requester === null || $requester->is($this->user())) {
            return;
        }

        if (! $requester->roles->contains('code', 'REQUESTER')) {
            $validator->errors()->add('requester_id', 'El usuario indicado no tiene el rol REQUESTER.');
        }
    }

    /**
     * La unidad es NOT NULL en tickets: si no se envía debe poder resolverse
     * con la del actor o la del solicitante.
     */
    private function validateOrganizationalUnitPresence(ValidatorContract $validator): void
    {
        if ($this->filled('organizational_unit_id')) {
            return;
        }

        $user = $this->user();

        if ($user !== null && $user->organizational_unit_id !== null) {
            return;
        }

        if ($this->filled('requester_id')) {
            $unitId = User::query()->whereKey($this->input('requester_id'))->value('organizational_unit_id');

            if ($unitId !== null) {
                return;
            }
        }

        $validator->errors()->add('organizational_unit_id', 'Debe indicar la unidad organizacional.');
    }
}
