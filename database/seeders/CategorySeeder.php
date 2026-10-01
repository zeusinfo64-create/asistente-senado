<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    use UpsertsMasterData;
    use WithoutModelEvents;

    /**
     * Categorías en máximo 2 niveles (§D.10 / §F.1 de la V1.1):
     * nivel 1 = categoría (`parent_id = NULL`), nivel 2 = subcategoría
     * (`parent_id = categoría`). No existe tercer nivel.
     *
     * Códigos de subcategorías dentro del límite de `categories.code` (30).
     *
     * @return array<int, array<string, mixed>>
     */
    private function categories(): array
    {
        return [
            [
                'code' => 'COMPUTERS',
                'name' => 'Computadoras',
                'description' => 'Problemas con equipos de cómputo (escritorio y portátiles).',
                'children' => [
                    ['code' => 'COMPUTERS_NO_ENCIENDE', 'name' => 'No enciende'],
                    ['code' => 'COMPUTERS_NO_IMAGEN', 'name' => 'No muestra imagen'],
                    ['code' => 'COMPUTERS_WINDOWS_NO_INICIA', 'name' => 'Windows no inicia'],
                    ['code' => 'COMPUTERS_LENTITUD', 'name' => 'Lentitud/rendimiento'],
                    ['code' => 'COMPUTERS_SE_REINICIA', 'name' => 'Se reinicia'],
                    ['code' => 'COMPUTERS_ERROR_SISTEMA', 'name' => 'Error del sistema'],
                    ['code' => 'COMPUTERS_HARDWARE', 'name' => 'Hardware'],
                    ['code' => 'COMPUTERS_SOFTWARE', 'name' => 'Software'],
                ],
            ],
            [
                'code' => 'PRINTERS',
                'name' => 'Impresoras',
                'description' => 'Problemas de impresión, conectividad y consumo de las impresoras.',
                'children' => [
                    ['code' => 'PRINTERS_NO_IMPRIME', 'name' => 'No imprime'],
                    ['code' => 'PRINTERS_CALIDAD', 'name' => 'Calidad de impresión'],
                    ['code' => 'PRINTERS_ATASCO', 'name' => 'Atasco de papel'],
                    ['code' => 'PRINTERS_CONECTIVIDAD', 'name' => 'Conectividad'],
                    ['code' => 'PRINTERS_CONFIGURACION', 'name' => 'Configuración'],
                    ['code' => 'PRINTERS_MANTENIMIENTO', 'name' => 'Mantenimiento'],
                ],
            ],
            [
                'code' => 'NETWORK',
                'name' => 'Redes/Internet',
                'description' => 'Conectividad de red cableada, inalámbrica y acceso a Internet.',
                'children' => [
                    ['code' => 'NETWORK_SIN_CONECTIVIDAD', 'name' => 'Sin conectividad'],
                    ['code' => 'NETWORK_INTERNET_LENTO', 'name' => 'Internet lento'],
                    ['code' => 'NETWORK_WIFI', 'name' => 'Wi-Fi'],
                    ['code' => 'NETWORK_CABLEADA', 'name' => 'Red cableada'],
                    ['code' => 'NETWORK_CONFIGURACION', 'name' => 'Configuración de red'],
                ],
            ],
            [
                'code' => 'SYSTEMS',
                'name' => 'Sistemas/Software',
                'description' => 'Problemas con el sistema operativo y las aplicaciones instaladas.',
                'children' => [
                    ['code' => 'SYSTEMS_ACCESO', 'name' => 'Problemas de acceso'],
                    ['code' => 'SYSTEMS_INSTALACION', 'name' => 'Instalación'],
                    ['code' => 'SYSTEMS_CONFIGURACION', 'name' => 'Configuración'],
                    ['code' => 'SYSTEMS_ERROR', 'name' => 'Error del sistema'],
                    ['code' => 'SYSTEMS_ACTUALIZACION', 'name' => 'Actualización'],
                ],
            ],
            [
                'code' => 'EMAIL',
                'name' => 'Correo electrónico',
                'description' => 'Acceso, envío, recepción y configuración del correo institucional.',
                'children' => [
                    ['code' => 'EMAIL_ACCESO', 'name' => 'Acceso'],
                    ['code' => 'EMAIL_ENVIO', 'name' => 'Envío'],
                    ['code' => 'EMAIL_RECEPCION', 'name' => 'Recepción'],
                    ['code' => 'EMAIL_CONFIGURACION', 'name' => 'Configuración'],
                    ['code' => 'EMAIL_CONTRASENA', 'name' => 'Contraseña'],
                ],
            ],
            [
                'code' => 'TELEPHONY',
                'name' => 'Telefonía',
                'description' => 'Extensiones telefónicas, llamadas y equipos telefónicos.',
                'children' => [
                    ['code' => 'TELEPHONY_EXTENSION', 'name' => 'Extensión'],
                    ['code' => 'TELEPHONY_LLAMADAS', 'name' => 'Llamadas'],
                    ['code' => 'TELEPHONY_CONFIGURACION', 'name' => 'Configuración'],
                    ['code' => 'TELEPHONY_EQUIPO', 'name' => 'Equipo telefónico'],
                ],
            ],
            [
                'code' => 'GENERAL_IT',
                'name' => 'General TI',
                'description' => 'Consultas y solicitudes de TI que no encajan en otra categoría.',
                'children' => [
                    ['code' => 'GENERAL_IT_CONSULTA', 'name' => 'Consulta'],
                    ['code' => 'GENERAL_IT_ASISTENCIA', 'name' => 'Asistencia general'],
                    ['code' => 'GENERAL_IT_OTRO', 'name' => 'Otro'],
                ],
            ],
            [
                'code' => 'MAINTENANCE',
                'name' => 'Reparaciones/Mantenimiento',
                'description' => 'Trabajos de mantenimiento y reparación de equipos.',
                'children' => [
                    ['code' => 'MAINTENANCE_PREVENTIVO', 'name' => 'Mantenimiento preventivo'],
                    ['code' => 'MAINTENANCE_CORRECTIVO', 'name' => 'Mantenimiento correctivo'],
                    ['code' => 'MAINTENANCE_REP_HARDWARE', 'name' => 'Reparación de hardware'],
                    ['code' => 'MAINTENANCE_REINSTALACION', 'name' => 'Reinstalación de sistema'],
                    ['code' => 'MAINTENANCE_LIMPIEZA', 'name' => 'Limpieza'],
                ],
            ],
            [
                'code' => 'ASSETS',
                'name' => 'Activos/Equipos',
                'description' => 'Gestión de activos: asignación, traslado, baja y consultas.',
                'children' => [
                    ['code' => 'ASSETS_ASIGNACION', 'name' => 'Asignación'],
                    ['code' => 'ASSETS_TRASLADO', 'name' => 'Traslado'],
                    ['code' => 'ASSETS_BAJA', 'name' => 'Baja'],
                    ['code' => 'ASSETS_CONSULTA', 'name' => 'Consulta de activo'],
                ],
            ],
        ];
    }

    public function run(): void
    {
        foreach ($this->categories() as $index => $category) {
            $parent = $this->updateOrCreateRecord(Category::class, ['code' => $category['code']], [
                'parent_id' => null,
                'name' => $category['name'],
                'description' => $category['description'],
                'sort_order' => ($index + 1) * 10,
                'is_active' => true,
            ]);

            foreach ($category['children'] as $childIndex => $child) {
                $this->updateOrCreateRecord(Category::class, ['code' => $child['code']], [
                    'parent_id' => $parent->id,
                    'name' => $child['name'],
                    'description' => null,
                    'sort_order' => ($childIndex + 1) * 10,
                    'is_active' => true,
                ]);
            }
        }
    }
}
