<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected function casts(): array
    {
        return [
            'parent_key' => 'integer',
            'sort_order' => 'integer',
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

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'category_id');
    }

    public function subcategoryTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'subcategory_id');
    }

    public function eventsFrom(): HasMany
    {
        return $this->hasMany(TicketEvent::class, 'from_category_id');
    }

    public function eventsTo(): HasMany
    {
        return $this->hasMany(TicketEvent::class, 'to_category_id');
    }

    public function slaPolicies(): HasMany
    {
        return $this->hasMany(SlaPolicy::class, 'category_id');
    }

    public function diagnosticQuestions(): HasMany
    {
        return $this->hasMany(DiagnosticQuestion::class, 'category_id');
    }

    public function diagnosticOptions(): HasMany
    {
        return $this->hasMany(DiagnosticOption::class, 'suggested_category_id');
    }

    public function aiAnalyses(): HasMany
    {
        return $this->hasMany(AiAnalysis::class, 'suggested_category_id');
    }

    public function aiAnalysesSubcategory(): HasMany
    {
        return $this->hasMany(AiAnalysis::class, 'suggested_subcategory_id');
    }
}
