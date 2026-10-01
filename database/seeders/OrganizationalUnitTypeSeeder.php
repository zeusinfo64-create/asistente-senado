<?php

namespace Database\Seeders;

use App\Models\OrganizationalUnitType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrganizationalUnitTypeSeeder extends Seeder
{
    use UpsertsMasterData;
    use WithoutModelEvents;

    /**
     * Tipos de unidad organizacional (§D.5 de la V1.1, más `camara` para la raíz).
     *
     * @return array<int, array<string, mixed>>
     */
    private function types(): array
    {
        return [
            ['code' => 'camara', 'name' => 'Cámara'],
            ['code' => 'direccion', 'name' => 'Dirección'],
            ['code' => 'unidad', 'name' => 'Unidad'],
            ['code' => 'area', 'name' => 'Área'],
            ['code' => 'comision', 'name' => 'Comisión'],
            ['code' => 'comite', 'name' => 'Comité'],
            ['code' => 'secretaria', 'name' => 'Secretaría'],
            ['code' => 'directorio', 'name' => 'Directorio'],
            ['code' => 'otro', 'name' => 'Otro'],
        ];
    }

    public function run(): void
    {
        foreach ($this->types() as $index => $type) {
            $this->updateOrCreateRecord(OrganizationalUnitType::class, ['code' => $type['code']], [
                'name' => $type['name'],
                'sort_order' => ($index + 1) * 10,
                'is_active' => true,
            ]);
        }
    }
}
