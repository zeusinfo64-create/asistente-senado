<?php

namespace Database\Seeders;

use App\Models\TicketSequence;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TicketSequenceSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Correlativo anual de tickets (§D.11): `TCK-{year}-{last_number + 1}`.
     *
     * Solo crea la fila del año en curso si no existe: nunca reinicia
     * `last_number`, de modo que repetir el seed no pierde correlativos
     * ya emitidos. La generación de números se implementa en una fase posterior
     * (SELECT ... FOR UPDATE dentro de una transacción).
     */
    public function run(): void
    {
        $year = (int) now()->format('Y');

        $exists = TicketSequence::query()->where('year', $year)->exists();

        if ($exists) {
            return;
        }

        $sequence = new TicketSequence;
        $sequence->year = $year;
        $sequence->last_number = 0;
        $sequence->save();
    }
}
