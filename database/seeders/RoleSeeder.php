<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    use UpsertsMasterData;
    use WithoutModelEvents;

    /**
     * Roles funcionales (§D.2). La estructura organizacional no son roles.
     *
     * @return array<int, array<string, string>>
     */
    private function roles(): array
    {
        return [
            [
                'code' => 'ADMIN',
                'name' => 'Administrador/Supervisor',
                'description' => 'Administra usuarios, catálogos, asigna y supervisa tickets.',
            ],
            [
                'code' => 'TECH',
                'name' => 'Técnico',
                'description' => 'Atiende tickets asignados y registra intervenciones.',
            ],
            [
                'code' => 'REQUESTER',
                'name' => 'Solicitante',
                'description' => 'Crea tickets de soporte y califica su atención.',
            ],
        ];
    }

    public function run(): void
    {
        foreach ($this->roles() as $role) {
            $this->updateOrCreateRecord(Role::class, ['code' => $role['code']], [
                'name' => $role['name'],
                'description' => $role['description'],
                'is_active' => true,
            ]);
        }
    }
}
