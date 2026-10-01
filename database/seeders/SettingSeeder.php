<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    use UpsertsMasterData;
    use WithoutModelEvents;

    /**
     * Configuración inicial oficial de la V1.1 (§D.4 y §M.2).
     *
     * No se crean claves de funcionalidades aún no implementadas (p. ej. IA)
     * ni secretos: nunca se almacenan credenciales en `settings`.
     *
     * @return array<int, array<string, string>>
     */
    private function settings(): array
    {
        return [
            [
                'key' => 'auto_close_days',
                'value' => '5',
                'type' => 'int',
                'group_name' => 'general',
                'description' => 'Días en estado Resuelto antes del cierre automático del ticket.',
            ],
            [
                'key' => 'max_upload_mb',
                'value' => '10',
                'type' => 'int',
                'group_name' => 'archivos',
                'description' => 'Tamaño máximo permitido por archivo adjunto (MB).',
            ],
            [
                'key' => 'storage_disk',
                'value' => 'local',
                'type' => 'string',
                'group_name' => 'archivos',
                'description' => 'Disco de almacenamiento de los archivos adjuntos.',
            ],
        ];
    }

    public function run(): void
    {
        foreach ($this->settings() as $setting) {
            $this->updateOrCreateRecord(Setting::class, ['key' => $setting['key']], [
                'value' => $setting['value'],
                'type' => $setting['type'],
                'group_name' => $setting['group_name'],
                'description' => $setting['description'],
            ]);
        }
    }
}
