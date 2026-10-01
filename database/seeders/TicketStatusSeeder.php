<?php

namespace Database\Seeders;

use App\Models\TicketStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TicketStatusSeeder extends Seeder
{
    use UpsertsMasterData;
    use WithoutModelEvents;

    /**
     * Estados funcionales (§D.8 / §G.1 de la V1.1).
     *
     * Flags según diseño:
     * - `is_initial`  → exactamente `REGISTERED`.
     * - `is_resolved` → `RESOLVED` y `CLOSED` (base del cierre automático).
     * - `is_terminal` → `CLOSED` y `CANCELLED`.
     *
     * Nota de diseño: la V1.1 NO define estado `REABIERTO`; la reapertura es un
     * evento (`ticket_events.event_type = reopened`) que devuelve el ticket a
     * `ASSIGNED` (o a `REGISTERED` si ya no hay técnico asignado).
     *
     * @return array<int, array<string, mixed>>
     */
    private function statuses(): array
    {
        return [
            [
                'code' => 'REGISTERED',
                'name' => 'Registrado',
                'description' => 'Ticket recién creado, pendiente de asignación.',
                'is_initial' => true,
                'is_resolved' => false,
                'is_terminal' => false,
            ],
            [
                'code' => 'ASSIGNED',
                'name' => 'Asignado',
                'description' => 'Asignado a un técnico, pendiente de iniciar la atención.',
                'is_initial' => false,
                'is_resolved' => false,
                'is_terminal' => false,
            ],
            [
                'code' => 'IN_PROGRESS',
                'name' => 'En atención',
                'description' => 'El técnico está atendiendo el ticket.',
                'is_initial' => false,
                'is_resolved' => false,
                'is_terminal' => false,
            ],
            [
                'code' => 'WAITING',
                'name' => 'En espera',
                'description' => 'Detenido por un motivo obligatorio (repuesto, usuario o tercero).',
                'is_initial' => false,
                'is_resolved' => false,
                'is_terminal' => false,
            ],
            [
                'code' => 'RESOLVED',
                'name' => 'Resuelto',
                'description' => 'Solución aplicada, pendiente de cierre por el solicitante.',
                'is_initial' => false,
                'is_resolved' => true,
                'is_terminal' => false,
            ],
            [
                'code' => 'CLOSED',
                'name' => 'Cerrado',
                'description' => 'Cerrado manual o automáticamente; habilita la calificación.',
                'is_initial' => false,
                'is_resolved' => true,
                'is_terminal' => true,
            ],
            [
                'code' => 'CANCELLED',
                'name' => 'Cancelado',
                'description' => 'Anulado con motivo; estado terminal.',
                'is_initial' => false,
                'is_resolved' => false,
                'is_terminal' => true,
            ],
        ];
    }

    public function run(): void
    {
        foreach ($this->statuses() as $index => $status) {
            $this->updateOrCreateRecord(TicketStatus::class, ['code' => $status['code']], [
                'name' => $status['name'],
                'description' => $status['description'],
                'sort_order' => ($index + 1) * 10,
                'is_initial' => $status['is_initial'],
                'is_resolved' => $status['is_resolved'],
                'is_terminal' => $status['is_terminal'],
                'is_active' => true,
            ]);
        }
    }
}
