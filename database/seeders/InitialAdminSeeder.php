<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Env;
use RuntimeException;

/**
 * Crea o actualiza la cuenta administrativa institucional inicial.
 *
 * Las credenciales provienen del entorno (ADMIN_USERNAME / ADMIN_PASSWORD).
 * Este seeder nunca define una contraseña por defecto: si falta la variable
 * falla de forma explícita, para no dejar la cuenta con una clave conocida.
 *
 * Idempotente: se localiza por username y el rol ADMIN se sincroniza sin
 * duplicar filas en role_user.
 */
class InitialAdminSeeder extends Seeder
{
    use UpsertsMasterData;
    use WithoutModelEvents;

    /**
     * Dominio institucional usado solo si el usuario aún no tiene correo.
     * El correo sigue siendo dato institucional obligatorio y UNIQUE; aquí no
     * se derive nada de una contraseña.
     */
    private const INSTITUTIONAL_EMAIL_DOMAIN = 'senadores.gob.bo';

    public function run(): void
    {
        $username = $this->requiredEnv('ADMIN_USERNAME');
        $password = $this->requiredEnv('ADMIN_PASSWORD');

        $role = Role::query()->where('code', 'ADMIN')->first();

        if ($role === null) {
            throw new RuntimeException(
                'El rol ADMIN no existe: ejecute RoleSeeder antes de InitialAdminSeeder.'
            );
        }

        $user = User::query()->where('username', $username)->first() ?? new User;

        $user->username = $username;
        $user->is_active = true;
        $user->auth_provider = 'local';

        // El cast 'password' => 'hashed' del modelo aplica el hashereo.
        $user->password = $password;

        if ($user->email === null || $user->email === '') {
            $user->email = $username.'@'.self::INSTITUTIONAL_EMAIL_DOMAIN;
        }

        if ($user->first_name === null || $user->first_name === '') {
            $user->first_name = 'Administrador';
        }

        if ($user->last_name === null || $user->last_name === '') {
            $user->last_name = 'del Sistema';
        }

        // La unidad organizacional NO se asigna aquí a propósito: las unidades
        // existentes en desarrollo son ficticias. El campo es nullable y queda
        // para que la unidad real se configure por la administración del
        // sistema, sin inventar una pertenencia institucional.
        $user->save();

        $user->roles()->syncWithoutDetaching([$role->id]);
    }

    /**
     * Lee una variable de entorno obligatoria sin exponer su valor.
     *
     * Usa Env (no env()) porque sigue funcionando con la configuración cacheada.
     */
    private function requiredEnv(string $key): string
    {
        $value = Env::get($key);

        if (! is_string($value) || trim($value) === '') {
            throw new RuntimeException(
                sprintf(
                    'Falta la variable de entorno %s: defínala en .env antes de ejecutar este seeder. '
                    .'No se aplica ninguna contraseña por defecto.',
                    $key
                )
            );
        }

        return trim($value);
    }
}
