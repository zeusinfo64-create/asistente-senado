<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

abstract class AuthTestCase extends TestCase
{
    private mixed $mysqlConnection;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql.database', 'helpdesk_v11');
        DB::purge('mysql');

        $this->mysqlConnection = DB::connection('mysql');
        $this->mysqlConnection->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->mysqlConnection->rollBack();
        DB::purge('mysql');

        parent::tearDown();
    }

    /**
     * Encabezados que hacen que Sanctum trate la petición como stateful (SPA).
     *
     * @param  array<string, string>  $headers
     * @return array<string, string>
     */
    protected function spaHeaders(array $headers = []): array
    {
        return array_merge(['referer' => 'http://localhost:8000/'], $headers);
    }

    /**
     * Crea un usuario con un rol funcional del seed y lo deja autenticado.
     */
    protected function createUserWithRole(string $roleCode, bool $isActive = true): User
    {
        $role = Role::query()->where('code', $roleCode)->firstOrFail();

        $user = User::factory()->create(['is_active' => $isActive]);
        $user->roles()->attach($role->id);

        return $user;
    }

    /**
     * Reenvía en la siguiente petición la cookie de sesión de la respuesta.
     */
    protected function withSessionCookie(TestResponse $response): static
    {
        $cookie = $response->getCookie((string) config('session.cookie'));

        $this->assertNotNull($cookie, 'La respuesta no incluyó la cookie de sesión.');

        return $this->withCookies([(string) config('session.cookie') => $cookie->getValue()]);
    }
}
