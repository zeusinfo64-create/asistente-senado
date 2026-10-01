<?php

namespace App\Http\Requests\Tickets;

use Closure;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Validation\Rule;

/**
 * Filtros y paginación del listado de tickets (FASE 6.2 §34-§37).
 *
 * Solo valida parámetros: el alcance por rol lo aplica el controlador para
 * que ningún filtro pueda ampliar lo que el usuario puede ver.
 */
class TicketQueryRequest extends TicketFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', 'string', Rule::exists('ticket_statuses', 'code')->where('is_active', true)],
            'priority' => ['sometimes', 'string', Rule::exists('ticket_priorities', 'code')->where('is_active', true)],
            'ticket_type' => ['sometimes', 'string', Rule::exists('ticket_types', 'code')->where('is_active', true)],
            'category' => ['sometimes', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'subcategory' => ['sometimes', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'assigned_technician' => ['sometimes', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'organizational_unit' => ['sometimes', 'integer', Rule::exists('organizational_units', 'id')->where('is_active', true)],
            'date_from' => ['sometimes', 'string', 'date'],
            'date_to' => ['sometimes', 'string', 'date'],
            'search' => ['sometimes', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'page.integer' => 'El parámetro page debe ser un número entero.',
            'page.min' => 'El parámetro page debe ser al menos 1.',
            'per_page.integer' => 'El parámetro per_page debe ser un número entero.',
            'per_page.min' => 'El parámetro per_page debe ser al menos 1.',
            'per_page.max' => 'El parámetro per_page no puede superar 100.',
            'status.exists' => 'El estado indicado en el filtro no existe o no está activo.',
            'priority.exists' => 'La prioridad indicada en el filtro no existe o no está activa.',
            'ticket_type.exists' => 'El tipo de ticket indicado en el filtro no existe o no está activo.',
            'category.integer' => 'El filtro category debe ser un identificador numérico.',
            'category.exists' => 'La categoría indicada en el filtro no existe o no está activa.',
            'subcategory.integer' => 'El filtro subcategory debe ser un identificador numérico.',
            'subcategory.exists' => 'La subcategoría indicada en el filtro no existe o no está activa.',
            'assigned_technician.integer' => 'El filtro assigned_technician debe ser un identificador numérico.',
            'assigned_technician.exists' => 'El técnico indicado en el filtro no existe o no está activo.',
            'organizational_unit.integer' => 'El filtro organizational_unit debe ser un identificador numérico.',
            'organizational_unit.exists' => 'La unidad organizacional indicada en el filtro no existe o no está activa.',
            'date_from.date' => 'El parámetro date_from debe ser una fecha válida.',
            'date_to.date' => 'El parámetro date_to debe ser una fecha válida.',
            'search.string' => 'El parámetro search debe ser un texto.',
            'search.max' => 'El parámetro search no debe superar los 100 caracteres.',
        ];
    }

    /**
     * date_from no puede ser posterior a date_to.
     *
     * @return Closure(ValidatorContract): void
     */
    public function after(): Closure
    {
        return function (ValidatorContract $validator): void {
            $from = $this->input('date_from');
            $to = $this->input('date_to');

            if ($from !== null && $to !== null && strtotime((string) $from) > strtotime((string) $to)) {
                $validator->errors()->add('date_from', 'La fecha desde no puede ser posterior a la fecha hasta.');
            }
        };
    }
}
