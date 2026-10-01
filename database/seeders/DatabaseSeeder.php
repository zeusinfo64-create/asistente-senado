<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Siembra los datos maestros iniciales (V1.1).
     *
     * Orden respetando las claves foráneas: catálogos → categorías →
     * configuración/correlativos → usuarios de desarrollo → administrador
     * institucional → activos.
     *
     * Idempotente: puede ejecutarse repetidamente con `php artisan db:seed`
     * y con `php artisan migrate:fresh --seed` sin generar duplicados.
     * No crea tickets, comentarios, intervenciones, notificaciones ni auditorías.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->call([
                OrganizationalUnitTypeSeeder::class,
                RoleSeeder::class,
                TicketTypeSeeder::class,
                TicketStatusSeeder::class,
                TicketPrioritySeeder::class,
                CategorySeeder::class,
                InterventionTypeSeeder::class,
                AssetTypeSeeder::class,
                AssetStateSeeder::class,
                SettingSeeder::class,
                TicketSequenceSeeder::class,
                DevelopmentUserSeeder::class,
                InitialAdminSeeder::class,
                DevelopmentAssetSeeder::class,
            ]);
        });
    }
}
