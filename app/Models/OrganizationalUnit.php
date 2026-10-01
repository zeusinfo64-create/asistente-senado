<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationalUnit extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnitType::class, 'organizational_unit_type_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'organizational_unit_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'organizational_unit_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'organizational_unit_id');
    }

    public function slaPolicies(): HasMany
    {
        return $this->hasMany(SlaPolicy::class, 'organizational_unit_id');
    }
}
