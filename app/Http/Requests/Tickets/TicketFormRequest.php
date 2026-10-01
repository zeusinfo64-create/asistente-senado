<?php

namespace App\Http\Requests\Tickets;

use App\Models\Asset;
use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Base de los FormRequest de tickets (FASE 6.2).
 *
 * Centraliza los campos prohibidos por backend (§28), el control de campos
 * mutables por rol (403) y las comunes de negocio: par
 * categoría-subcategoría (docs §F.1) y disponibilidad del activo (docs §D.22).
 */
abstract class TicketFormRequest extends FormRequest
{
    /**
     * Columnas propiedad del backend: nunca aceptadas desde el cliente (403).
     *
     * @var array<int, string>
     */
    protected const FORBIDDEN_FIELDS = [
        'ticket_number',
        'status_id',
        'assigned_to_id',
        'created_by_id',
        'channel',
        'classification_source',
        'created_at',
        'updated_at',
        'assigned_at',
        'started_at',
        'first_response_at',
        'resolved_at',
        'closed_at',
        'sla_policy_id',
        'sla_first_response_due_at',
        'sla_resolution_due_at',
        'sla_response_breached',
        'sla_resolution_breached',
    ];

    /**
     * Campos mutables en algún momento del ciclo: si el rol del usuario no
     * los tiene declarados, también responden 403 (§30).
     *
     * @var array<int, string>
     */
    protected const MUTABLE_FIELDS = [
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
        'requester_id',
    ];

    /**
     * Rechaza con 403 los campos del backend y los campos mutables que ningún
     * rol del usuario tiene permitidos.
     *
     * @param  array<string, array<int, string>>  $fieldsByRole  Campos permitidos por código de rol.
     */
    protected function assertFieldsAreAllowed(User $user, array $fieldsByRole): void
    {
        $allowed = [];

        foreach ($user->roles as $role) {
            $allowed = array_merge($allowed, $fieldsByRole[$role->code] ?? []);
        }

        foreach (array_keys($this->all()) as $field) {
            if (in_array($field, self::FORBIDDEN_FIELDS, true)) {
                throw new AuthorizationException('No tiene permisos para modificar este campo.');
            }

            if (in_array($field, self::MUTABLE_FIELDS, true) && ! in_array($field, $allowed, true)) {
                throw new AuthorizationException('No tiene permisos para modificar este campo.');
            }
        }
    }

    /**
     * Par categoría-subcategoría (docs §F.1): se envían juntas y la
     * subcategoría debe ser hija exacta de la categoría.
     */
    protected function validateCategoryPair(ValidatorContract $validator): void
    {
        $categoryId = $this->input('category_id');
        $subcategoryId = $this->input('subcategory_id');

        if ($categoryId === null && $subcategoryId === null) {
            return;
        }

        if ($categoryId === null) {
            $validator->errors()->add('category_id', 'Debe indicar la categoría y la subcategoría juntas.');

            return;
        }

        if ($subcategoryId === null) {
            $validator->errors()->add('subcategory_id', 'Debe indicar la categoría y la subcategoría juntas.');

            return;
        }

        $subcategory = Category::query()->find($subcategoryId);

        if ($subcategory === null) {
            // La regla exists ya reportó el error de la subcategoría.
            return;
        }

        if ($subcategory->parent_id !== (int) $categoryId) {
            $validator->errors()->add('subcategory_id', 'La subcategoría seleccionada no pertenece a la categoría.');
        }
    }

    /**
     * El activo asociable no puede estar dado de baja (docs §D.22).
     */
    protected function validateAssetAvailability(ValidatorContract $validator): void
    {
        $assetId = $this->input('asset_id');

        if ($assetId === null) {
            return;
        }

        $asset = Asset::query()->find($assetId);

        if ($asset === null) {
            // La regla exists ya reportó el error del activo.
            return;
        }

        if ($asset->decommissioned_at !== null || $asset->assetState?->code === 'DECOMMISSIONED') {
            $validator->errors()->add('asset_id', 'El activo indicado está dado de baja y no puede asociarse al ticket.');
        }
    }
}
