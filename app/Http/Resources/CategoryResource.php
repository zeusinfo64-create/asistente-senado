<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Categoría raíz con sus subcategorías activas (máximo 2 niveles).
 *
 * Las subcategorías se serializan con esta misma clase pero sin la clave
 * "subcategories", porque la relación children no está cargada en ellas.
 *
 * @property-read int $id
 * @property-read string|null $code
 * @property-read string $name
 * @property-read string|null $description
 * @property-read int $sort_order
 */
class CategoryResource extends JsonResource
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
            'sort_order' => $this->sort_order,
            'subcategories' => $this->when(
                $this->relationLoaded('children'),
                fn (): AnonymousResourceCollection => CategoryResource::collection($this->children),
            ),
        ];
    }
}
