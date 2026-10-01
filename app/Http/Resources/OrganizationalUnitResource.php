<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Unidad organizacional con su tipo y, si existe, el padre inmediato.
 *
 * @property-read int $id
 * @property-read string|null $code
 * @property-read string $name
 * @property-read string|null $description
 * @property-read int|null $parent_id
 */
class OrganizationalUnitResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'parent_id' => $this->parent_id,
            'type' => $this->type === null ? null : [
                'id' => $this->type->id,
                'code' => $this->type->code,
                'name' => $this->type->name,
            ],
            'parent' => $this->parent === null ? null : [
                'id' => $this->parent->id,
                'code' => $this->parent->code,
                'name' => $this->parent->name,
            ],
        ];
    }
}
