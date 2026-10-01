<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SparePart extends Model
{
    protected function casts(): array
    {
        return [
            'unit_cost' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function interventionParts(): HasMany
    {
        return $this->hasMany(InterventionPart::class, 'spare_part_id');
    }
}
