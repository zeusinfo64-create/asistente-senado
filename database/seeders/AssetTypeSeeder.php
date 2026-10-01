<?php

namespace Database\Seeders;

use App\Models\AssetType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AssetTypeSeeder extends Seeder
{
    use UpsertsMasterData;
    use WithoutModelEvents;

    /**
     * Tipos de activo (§D.21).
     *
     * @return array<int, array<string, string>>
     */
    private function types(): array
    {
        return [
            ['code' => 'DESKTOP_PC', 'name' => 'Computadora de escritorio'],
            ['code' => 'LAPTOP', 'name' => 'Laptop'],
            ['code' => 'MONITOR', 'name' => 'Monitor'],
            ['code' => 'PRINTER', 'name' => 'Impresora'],
            ['code' => 'SERVER', 'name' => 'Servidor'],
            ['code' => 'NETWORK_EQUIPMENT', 'name' => 'Equipo de red'],
            ['code' => 'PHONE', 'name' => 'Teléfono'],
            ['code' => 'OTHER', 'name' => 'Otro'],
        ];
    }

    public function run(): void
    {
        foreach ($this->types() as $index => $type) {
            $this->updateOrCreateRecord(AssetType::class, ['code' => $type['code']], [
                'name' => $type['name'],
                'sort_order' => ($index + 1) * 10,
                'is_active' => true,
            ]);
        }
    }
}
