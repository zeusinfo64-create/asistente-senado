<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketStatus extends Model
{
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_initial' => 'boolean',
            'is_resolved' => 'boolean',
            'is_terminal' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'status_id');
    }

    public function eventsFrom(): HasMany
    {
        return $this->hasMany(TicketEvent::class, 'from_status_id');
    }

    public function eventsTo(): HasMany
    {
        return $this->hasMany(TicketEvent::class, 'to_status_id');
    }

    public function transitionsFrom(): HasMany
    {
        return $this->hasMany(TicketStatusTransition::class, 'from_status_id');
    }

    public function transitionsTo(): HasMany
    {
        return $this->hasMany(TicketStatusTransition::class, 'to_status_id');
    }
}
