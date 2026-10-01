<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\UniqueConstraintViolationException;
use RuntimeException;

class TicketSequence extends Model
{
    /**
     * Tabla solo con updated_at (§D.11): no existe created_at.
     */
    const CREATED_AT = null;

    const UPDATED_AT = 'updated_at';

    /**
     * Genera el siguiente número de ticket del año en curso (docs §D.13:
     * `TCK-2026-000125`) incrementando `ticket_sequences` bajo bloqueo de fila.
     *
     * Debe invocarse dentro de una transacción (SELECT ... FOR UPDATE, docs
     * §K.9): dos creaciones simultáneas no pueden obtener el mismo correlativo.
     */
    public static function nextNumber(): string
    {
        $year = (int) now()->format('Y');

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $sequence = static::query()->where('year', $year)->lockForUpdate()->first();

            if ($sequence === null) {
                $sequence = new self;
                $sequence->year = $year;
                $sequence->last_number = 0;

                try {
                    $sequence->save();
                } catch (UniqueConstraintViolationException) {
                    // Otra transacción creó la fila del año: reintentar con bloqueo.
                    continue;
                }
            }

            $sequence->last_number++;
            $sequence->save();

            return sprintf('TCK-%d-%06d', $year, $sequence->last_number);
        }

        throw new RuntimeException("No fue posible obtener la secuencia de tickets del año {$year}.");
    }
}
