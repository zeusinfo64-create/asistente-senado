<?php

namespace Database\Seeders;

use App\Models\InterventionType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InterventionTypeSeeder extends Seeder
{
    use UpsertsMasterData;
    use WithoutModelEvents;

    /**
     * Tipos de intervención técnica (§D.15).
     *
     * @return array<int, array<string, string>>
     */
    private function types(): array
    {
        return [
            ['code' => 'DIAGNOSIS', 'name' => 'Diagnóstico'],
            ['code' => 'PREVENTIVE_MAINTENANCE', 'name' => 'Mantenimiento preventivo'],
            ['code' => 'CORRECTIVE_MAINTENANCE', 'name' => 'Mantenimiento correctivo'],
            ['code' => 'REPAIR', 'name' => 'Reparación'],
            ['code' => 'INSTALLATION', 'name' => 'Instalación'],
            ['code' => 'CONFIGURATION', 'name' => 'Configuración'],
            ['code' => 'COMPONENT_REPLACEMENT', 'name' => 'Cambio de componente'],
            ['code' => 'SYSTEM_REINSTALL', 'name' => 'Reinstalación de sistema'],
            ['code' => 'TESTING', 'name' => 'Pruebas'],
            ['code' => 'OTHER', 'name' => 'Otro'],
        ];
    }

    public function run(): void
    {
        foreach ($this->types() as $index => $type) {
            $this->updateOrCreateRecord(InterventionType::class, ['code' => $type['code']], [
                'name' => $type['name'],
                'description' => null,
                'sort_order' => ($index + 1) * 10,
                'is_active' => true,
            ]);
        }
    }
}
