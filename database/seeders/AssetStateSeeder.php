<?php

namespace Database\Seeders;

use App\Models\AssetState;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AssetStateSeeder extends Seeder
{
    use UpsertsMasterData;
    use WithoutModelEvents;

    /**
     * Estados operativos de activos (§D.22). Sin estados patrimoniales/financieros.
     *
     * @return array<int, array<string, string>>
     */
    private function states(): array
    {
        return [
            ['code' => 'ACTIVE', 'name' => 'Activo'],
            ['code' => 'IN_REPAIR', 'name' => 'En reparación'],
            ['code' => 'IN_MAINTENANCE', 'name' => 'En mantenimiento'],
            ['code' => 'IN_STORAGE', 'name' => 'En almacén'],
            ['code' => 'DECOMMISSIONED', 'name' => 'Dado de baja'],
        ];
    }

    public function run(): void
    {
        foreach ($this->states() as $index => $state) {
            $this->updateOrCreateRecord(AssetState::class, ['code' => $state['code']], [
                'name' => $state['name'],
                'sort_order' => ($index + 1) * 10,
                'is_active' => true,
            ]);
        }
    }
}
