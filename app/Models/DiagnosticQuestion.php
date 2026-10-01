<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiagnosticQuestion extends Model
{
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(DiagnosticOption::class, 'question_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(TicketDiagnosticAnswer::class, 'question_id');
    }
}
