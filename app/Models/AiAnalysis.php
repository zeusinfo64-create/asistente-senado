<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AiAnalysis extends Model
{
    protected function casts(): array
    {
        return [
            'confidence' => 'float',
            'applied_at' => 'datetime',
            'analyzed_at' => 'datetime',
            'raw_response' => 'array',
        ];
    }

    public function analyzable(): MorphTo
    {
        return $this->morphTo();
    }

    public function suggestedCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'suggested_category_id');
    }

    public function suggestedSubcategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'suggested_subcategory_id');
    }

    public function suggestedPriority(): BelongsTo
    {
        return $this->belongsTo(TicketPriority::class, 'suggested_priority_id');
    }

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by_id');
    }
}
