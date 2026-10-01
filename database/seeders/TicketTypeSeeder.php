<?php

namespace Database\Seeders;

use App\Models\TicketType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use RuntimeException;

class TicketTypeSeeder extends Seeder
{
    use UpsertsMasterData;
    use WithoutModelEvents;

    /**
     * Códigos retirados del catálogo por decisión funcional (simplificación).
     *
     * `REPAIR` dejó de ser un tipo de ticket: el tipo de ticket es solo la
     * clasificación general de la atención (Soporte / Incidente / Solicitud /
     * Mantenimiento) y la reparación se registra como intervención técnica
     * (`intervention_types.code = REPAIR`), que no se modifica.
     */
    private const RETIRED_CODES = ['REPAIR'];

    /**
     * Tipos de ticket: clasificación general de la atención.
     *
     * El detalle técnico vive en categoría, subcategoría, activo, diagnóstico
     * e intervención; no genera tipos adicionales.
     *
     * @return array<int, array<string, string>>
     */
    private function types(): array
    {
        return [
            ['code' => 'SUPPORT', 'name' => 'Soporte', 'description' => 'Consulta o asistencia técnica general.'],
            ['code' => 'INCIDENT', 'name' => 'Incidente', 'description' => 'Fallo o interrupción imprevista de un equipo o servicio.'],
            ['code' => 'REQUEST', 'name' => 'Solicitud', 'description' => 'Solicitud de servicio, recurso o cambio.'],
            ['code' => 'MAINTENANCE', 'name' => 'Mantenimiento', 'description' => 'Trabajo técnico programado o correctivo sobre equipos o software.'],
        ];
    }

    public function run(): void
    {
        foreach ($this->types() as $index => $type) {
            $this->updateOrCreateRecord(TicketType::class, ['code' => $type['code']], [
                'name' => $type['name'],
                'description' => $type['description'],
                'sort_order' => ($index + 1) * 10,
                'is_active' => true,
            ]);
        }

        $this->removeRetiredTypes();
    }

    /**
     * Elimina del catálogo los códigos retirados, únicamente cuando ningún
     * registro dependiente los utiliza (`tickets`, `sla_policies`).
     *
     * Si existen dependencias no se modifica nada: se detiene el seed y se
     * reporta el conflicto para revisión manual.
     */
    private function removeRetiredTypes(): void
    {
        foreach (self::RETIRED_CODES as $code) {
            $type = TicketType::query()->where('code', $code)->first();

            if ($type === null) {
                continue;
            }

            $tickets = $type->tickets()->count();
            $slaPolicies = $type->slaPolicies()->count();

            if ($tickets > 0 || $slaPolicies > 0) {
                throw new RuntimeException(sprintf(
                    'Conflicto: no se puede retirar el tipo de ticket "%s" porque lo utilizan %d ticket(s) y %d política(s) SLA. Ningún registro fue modificado.',
                    $code,
                    $tickets,
                    $slaPolicies
                ));
            }

            $type->delete();

            $this->command?->info("Tipo de ticket retirado del catálogo: {$code}");
        }
    }
}
