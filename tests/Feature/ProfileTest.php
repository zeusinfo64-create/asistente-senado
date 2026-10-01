<?php

namespace Tests\Feature;

use App\Models\OrganizationalUnit;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Auth\AuthTestCase;

/**
 * Pruebas del perfil/contexto del usuario autenticado (GET /api/me) - FASE 6.1.
 *
 * Reutiliza la base de pruebas de Fase 5 (transacción MySQL sobre helpdesk_v11).
 */
class ProfileTest extends AuthTestCase
{
    public function test_me_returns_the_full_profile_context(): void
    {
        $unit = OrganizationalUnit::query()->where('code', 'DEV-UNIT')->firstOrFail();

        $user = $this->createUserWithRole('ADMIN');
        $user->organizational_unit_id = $unit->id;
        $user->save();

        Sanctum::actingAs($user);

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.username', $user->username)
            ->assertJsonPath('user.first_name', $user->first_name)
            ->assertJsonPath('user.last_name', $user->last_name)
            ->assertJsonPath('user.full_name', trim($user->first_name.' '.$user->last_name))
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('user.is_active', true)
            ->assertJsonPath('user.roles.0.code', 'ADMIN')
            ->assertJsonPath('user.organizational_unit.id', $unit->id)
            ->assertJsonPath('user.organizational_unit.code', 'DEV-UNIT')
            ->assertJsonPath('user.organizational_unit.name', $unit->name);
    }

    public function test_me_returns_null_organizational_unit_when_the_user_has_none(): void
    {
        Sanctum::actingAs($this->createUserWithRole('TECH'));

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('user.organizational_unit', null);
    }

    public function test_me_does_not_expose_credentials(): void
    {
        Sanctum::actingAs($this->createUserWithRole('REQUESTER'));

        $response = $this->getJson('/api/me')->assertOk();

        $payload = $response->json('user');

        $this->assertArrayNotHasKey('password', $payload);
        $this->assertArrayNotHasKey('remember_token', $payload);
        $response->assertDontSee('password');
        $response->assertDontSee('remember_token');
    }
}
