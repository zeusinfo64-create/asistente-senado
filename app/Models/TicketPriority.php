<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketPriority extends Model
{
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'priority_id');
    }

    public function eventsFrom(): HasMany
    {
        return $this->hasMany(TicketEvent::class, 'from_priority_id');
    }

    public function eventsTo(): HasMany
    {
        return $this->hasMany(TicketEvent::class, 'to_priority_id');
    }

    public function aiAnalyses(): HasMany
    {
        return $this->hasMany(AiAnalysis::class, 'suggested_priority_id');
    }

    public function slaPolicies(): HasMany
    {
        return $this->hasMany(SlaPolicy::class, 'priority_id');
    }
}
