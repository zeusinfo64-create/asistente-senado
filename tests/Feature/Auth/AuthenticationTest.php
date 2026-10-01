<?php

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\Auth;

class AuthenticationTest extends AuthTestCase
{
    public function test_login_returns_session_cookie_and_user_with_roles(): void
    {
        $user = $this->createUserWithRole('ADMIN');

        $response = $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'password',
        ], $this->spaHeaders());

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'username', 'first_name', 'last_name', 'email', 'is_active', 'roles'],
            ])
            ->assertJsonPath('user.username', $user->username)
            ->assertJsonPath('user.email', $user->email)
            ->assertCookie((string) config('session.cookie'));

        $this->assertSame(['ADMIN'], array_column($response->json('user.roles'), 'code'));

        $this->withSessionCookie($response)
            ->getJson('/api/me', $this->spaHeaders())
            ->assertOk()
            ->assertJsonPath('user.username', $user->username)
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_login_accepts_username_and_never_uses_email_as_credential(): void
    {
        $user = $this->createUserWithRole('TECH');

        // El correo institucional NO es el identificador de acceso.
        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ], $this->spaHeaders())->assertStatus(422);

        $this->postJson('/api/login', [
            'username' => $user->email,
            'password' => 'password',
        ], $this->spaHeaders())->assertUnauthorized();
    }

    public function test_me_returns_401_for_guest(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();

        $this->getJson('/api/me', $this->spaHeaders())->assertUnauthorized();
    }

    public function test_login_returns_401_for_wrong_password(): void
    {
        $user = $this->createUserWithRole('REQUESTER');

        $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'clave-incorrecta',
        ], $this->spaHeaders())->assertUnauthorized();
    }

    public function test_login_returns_401_for_unknown_username(): void
    {
        $this->postJson('/api/login', [
            'username' => 'no-existe',
            'password' => 'password',
        ], $this->spaHeaders())->assertUnauthorized();
    }

    public function test_login_returns_422_when_payload_is_invalid(): void
    {
        $this->postJson('/api/login', [], $this->spaHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['username', 'password']);
    }

    public function test_login_returns_422_when_username_is_missing(): void
    {
        $this->postJson('/api/login', ['password' => 'password'], $this->spaHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function test_login_returns_403_for_inactive_user(): void
    {
        $user = $this->createUserWithRole('TECH', isActive: false);

        $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'password',
        ], $this->spaHeaders())
            ->assertForbidden()
            ->assertJson(['message' => 'El usuario está inactivo.']);
    }

    public function test_logout_closes_session(): void
    {
        $user = $this->createUserWithRole('REQUESTER');

        $login = $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'password',
        ], $this->spaHeaders());

        $login->assertOk();

        $this->withSessionCookie($login)
            ->postJson('/api/logout', [], $this->spaHeaders())
            ->assertOk()
            ->assertJson(['message' => 'Sesión cerrada correctamente.']);

        // En producción cada petición arranca con una aplicación nueva (guards
        // vacíos); aquí el contenedor se reutiliza, así que se limpian a mano.
        Auth::forgetGuards();

        $this->withSessionCookie($login)
            ->getJson('/api/me', $this->spaHeaders())
            ->assertUnauthorized();
    }

    public function test_logout_returns_401_for_guest(): void
    {
        $this->postJson('/api/logout')->assertUnauthorized();
    }

    public function test_csrf_cookie_endpoint_is_available(): void
    {
        $this->getJson('/sanctum/csrf-cookie')
            ->assertNoContent()
            ->assertCookie('XSRF-TOKEN');
    }

    public function test_login_response_never_exposes_the_password(): void
    {
        $user = $this->createUserWithRole('ADMIN');

        $response = $this->postJson('/api/login', [
            'username' => $user->username,
            'password' => 'password',
        ], $this->spaHeaders());

        $response->assertOk();

        $payload = $response->json('user');

        $this->assertArrayNotHasKey('password', $payload);
        $this->assertArrayNotHasKey('remember_token', $payload);
        $this->assertStringNotContainsString('password', $response->getContent());
    }
}
