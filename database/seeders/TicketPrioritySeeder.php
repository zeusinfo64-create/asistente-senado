<?php

namespace Database\Seeders;

use App\Models\TicketPriority;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TicketPrioritySeeder extends Seeder
{
    use UpsertsMasterData;
    use WithoutModelEvents;

    /**
     * Prioridades (§D.9). Sin tiempos SLA: esos valores viven únicamente
     * en `sla_policies` y aún no tienen valores institucionales oficiales.
     *
     * @return array<int, array<string, string>>
     */
    private function priorities(): array
    {
        return [
            ['code' => 'LOW', 'name' => 'Baja'],
            ['code' => 'MEDIUM', 'name' => 'Media'],
            ['code' => 'HIGH', 'name' => 'Alta'],
            ['code' => 'CRITICAL', 'name' => 'Crítica'],
        ];
    }

    public function run(): void
    {
        foreach ($this->priorities() as $index => $priority) {
            $this->updateOrCreateRecord(TicketPriority::class, ['code' => $priority['code']], [
                'name' => $priority['name'],
                'sort_order' => ($index + 1) * 10,
                'is_active' => true,
            ]);
        }
    }
}
