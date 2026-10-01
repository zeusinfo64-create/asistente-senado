<?php

namespace App\Http\Requests\Catalogs;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrganizationalUnitCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'string', 'max:50', Rule::exists('organizational_unit_types', 'code')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.string' => 'El parámetro type debe ser un texto.',
            'type.max' => 'El parámetro type no debe superar los 50 caracteres.',
            'type.exists' => 'El tipo de unidad organizacional indicado no existe.',
        ];
    }
}
