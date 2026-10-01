<?php

namespace Tests\Feature\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

class AuthorizationTest extends AuthTestCase
{
    private const FORBIDDEN_MESSAGE = 'No tiene permisos para realizar esta acción.';

    private const INACTIVE_MESSAGE = 'El usuario está inactivo.';

    /**
     * Registra una ruta efímera (solo existe durante la prueba) protegida
     * con el grupo api, auth:sanctum y el middleware role:.
     */
    private function registerRoleRoute(string $uri, string $role): void
    {
        Route::middleware(['api', 'auth:sanctum', 'role:'.$role])
            ->get($uri, fn (): JsonResponse => response()->json(['ok' => true]));
    }

    public function test_admin_route_allows_admin_and_denies_other_roles(): void
    {
        $this->registerRoleRoute('/_testing/admin-only', 'ADMIN');

        Sanctum::actingAs($this->createUserWithRole('ADMIN'));
        $this->getJson('/_testing/admin-only')->assertOk()->assertJson(['ok' => true]);

        Sanctum::actingAs($this->createUserWithRole('TECH'));
        $this->getJson('/_testing/admin-only')
            ->assertForbidden()
            ->assertJson(['message' => self::FORBIDDEN_MESSAGE]);

        Sanctum::actingAs($this->createUserWithRole('REQUESTER'));
        $this->getJson('/_testing/admin-only')
            ->assertForbidden()
            ->assertJson(['message' => self::FORBIDDEN_MESSAGE]);
    }

    public function test_tech_route_allows_technician_and_denies_admin(): void
    {
        $this->registerRoleRoute('/_testing/tech-only', 'TECH');

        Sanctum::actingAs($this->createUserWithRole('TECH'));
        $this->getJson('/_testing/tech-only')->assertOk()->assertJson(['ok' => true]);

        Sanctum::actingAs($this->createUserWithRole('ADMIN'));
        $this->getJson('/_testing/tech-only')
            ->assertForbidden()
            ->assertJson(['message' => self::FORBIDDEN_MESSAGE]);
    }

    public function test_requester_route_allows_requester_and_denies_technician(): void
    {
        $this->registerRoleRoute('/_testing/requester-only', 'REQUESTER');

        Sanctum::actingAs($this->createUserWithRole('REQUESTER'));
        $this->getJson('/_testing/requester-only')->assertOk()->assertJson(['ok' => true]);

        Sanctum::actingAs($this->createUserWithRole('TECH'));
        $this->getJson('/_testing/requester-only')
            ->assertForbidden()
            ->assertJson(['message' => self::FORBIDDEN_MESSAGE]);
    }

    public function test_role_route_requires_authentication(): void
    {
        $this->registerRoleRoute('/_testing/admin-only', 'ADMIN');

        $this->getJson('/_testing/admin-only')->assertUnauthorized();
    }

    public function test_inactive_user_is_denied_even_with_matching_role(): void
    {
        $this->registerRoleRoute('/_testing/tech-only', 'TECH');

        Sanctum::actingAs($this->createUserWithRole('TECH', isActive: false));

        $this->getJson('/_testing/tech-only')
            ->assertForbidden()
            ->assertJson(['message' => self::INACTIVE_MESSAGE]);
    }

    public function test_inactive_user_gets_403_on_me_endpoint(): void
    {
        Sanctum::actingAs($this->createUserWithRole('ADMIN', isActive: false));

        $this->getJson('/api/me', $this->spaHeaders())
            ->assertForbidden()
            ->assertJson(['message' => self::INACTIVE_MESSAGE]);
    }
}
