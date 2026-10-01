<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketDiagnosticAnswer extends Model
{
    protected function casts(): array
    {
        return [
            'answered_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(DiagnosticQuestion::class, 'question_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(DiagnosticOption::class, 'option_id');
    }

    public function answeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'answered_by_id');
    }
}
