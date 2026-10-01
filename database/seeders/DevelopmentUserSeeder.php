<?php

namespace Database\Seeders;

use App\Models\OrganizationalUnit;
use App\Models\OrganizationalUnitType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use RuntimeException;

class DevelopmentUserSeeder extends Seeder
{
    use UpsertsMasterData;
    use WithoutModelEvents;

    /**
     * Contraseña de las cuentas ficticias de desarrollo.
     *
     * Solo aplica a usuarios de prueba sin relación con personas reales. La
     * cuenta administrativa institucional NO se crea aquí: vive en
     * InitialAdminSeeder y toma su contraseña de ADMIN_PASSWORD.
     */
    private const DEVELOPMENT_PASSWORD = 'ChangeMe123!';

    /**
     * Usuarios ficticios de prueba (exclusivamente desarrollo local).
     *
     * No incluye ningún ADMIN: la cuenta administrativa institucional la crea
     * InitialAdminSeeder. TECH y REQUESTER se conservan porque las pruebas y
     * el flujo de tickets requieren ambos roles.
     *
     * @return array<int, array<string, string>>
     */
    private function users(): array
    {
        return [
            [
                'username' => 'tecnico',
                'first_name' => 'Técnico',
                'last_name' => 'Soporte',
                'email' => 'tecnico@helpdesk.local',
                'role_code' => 'TECH',
            ],
            [
                'username' => 'solicitante',
                'first_name' => 'Usuario',
                'last_name' => 'Prueba',
                'email' => 'solicitante@helpdesk.local',
                'role_code' => 'REQUESTER',
            ],
        ];
    }

    /**
     * Crea las cuentas ficticias de desarrollo.
     *
     * @throws RuntimeException Si se intenta ejecutar en producción.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException(
                'DevelopmentUserSeeder no debe ejecutarse en producción: sus usuarios son ficticios y su '
                .'contraseña es una constante conocida en el código.'
            );
        }

        $unitTypeId = OrganizationalUnitType::query()
            ->where('code', 'unidad')
            ->firstOrFail()
            ->id;

        $unit = $this->updateOrCreateRecord(OrganizationalUnit::class, ['code' => 'DEV-UNIT'], [
            'parent_id' => null,
            'organizational_unit_type_id' => $unitTypeId,
            'name' => 'Unidad de Pruebas TI',
            'description' => 'Unidad ficticia de desarrollo: no corresponde a ninguna unidad real de la Cámara de Senadores.',
            'is_active' => true,
        ]);

        foreach ($this->users() as $data) {
            $user = $this->updateOrCreateRecord(User::class, ['username' => $data['username']], [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => self::DEVELOPMENT_PASSWORD,
                'auth_provider' => 'local',
                'organizational_unit_id' => $unit->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $role = Role::query()->where('code', $data['role_code'])->firstOrFail();

            $user->roles()->sync([$role->id]);
        }
    }
}
