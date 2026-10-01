<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetState;
use App\Models\AssetType;
use App\Models\OrganizationalUnit;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DevelopmentAssetSeeder extends Seeder
{
    use UpsertsMasterData;
    use WithoutModelEvents;

    /**
     * Tres activos ficticios para probar relaciones (códigos, series y marcas
     * claramente de desarrollo; sin datos reales ni patrimoniales).
     *
     * @return array<int, array<string, string>>
     */
    private function assets(): array
    {
        return [
            [
                'asset_code' => 'TEST-PC-001',
                'serial_number' => 'TEST-SERIAL-PC-001',
                'asset_type_code' => 'DESKTOP_PC',
                'brand' => 'Genérico',
                'model' => 'Modelo de prueba',
                'assigned_user_email' => 'solicitante@helpdesk.local',
            ],
            [
                'asset_code' => 'TEST-LAPTOP-001',
                'serial_number' => 'TEST-SERIAL-LAPTOP-001',
                'asset_type_code' => 'LAPTOP',
                'brand' => 'Genérico',
                'model' => 'Modelo de prueba',
                'assigned_user_email' => 'tecnico@helpdesk.local',
            ],
            [
                'asset_code' => 'TEST-PRINTER-001',
                'serial_number' => 'TEST-SERIAL-PRINTER-001',
                'asset_type_code' => 'PRINTER',
                'brand' => 'Genérico',
                'model' => 'Modelo de prueba',
                'assigned_user_email' => null,
            ],
        ];
    }

    public function run(): void
    {
        $unitId = OrganizationalUnit::query()
            ->where('code', 'DEV-UNIT')
            ->firstOrFail()
            ->id;

        $activeStateId = AssetState::query()
            ->where('code', 'ACTIVE')
            ->firstOrFail()
            ->id;

        foreach ($this->assets() as $data) {
            $type = AssetType::query()
                ->where('code', $data['asset_type_code'])
                ->firstOrFail();

            $assignedUserId = null;

            if ($data['assigned_user_email'] !== null) {
                $assignedUserId = User::query()
                    ->where('email', $data['assigned_user_email'])
                    ->firstOrFail()
                    ->id;
            }

            $this->updateOrCreateRecord(Asset::class, ['asset_code' => $data['asset_code']], [
                'serial_number' => $data['serial_number'],
                'asset_type_id' => $type->id,
                'asset_state_id' => $activeStateId,
                'brand' => $data['brand'],
                'model' => $data['model'],
                'assigned_user_id' => $assignedUserId,
                'organizational_unit_id' => $unitId,
                'notes' => 'Activo ficticio de desarrollo; no corresponde a un equipo real.',
            ]);
        }
    }
}
