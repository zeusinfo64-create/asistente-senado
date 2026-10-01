<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterventionPart extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'unit_cost' => 'float',
            'used_at' => 'date',
        ];
    }

    public function intervention(): BelongsTo
    {
        return $this->belongsTo(TicketIntervention::class, 'intervention_id');
    }

    public function sparePart(): BelongsTo
    {
        return $this->belongsTo(SparePart::class, 'spare_part_id');
    }
}
