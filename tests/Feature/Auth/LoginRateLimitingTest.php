<?php

namespace Tests\Feature\Auth;

class LoginRateLimitingTest extends AuthTestCase
{
    public function test_login_is_rate_limited_after_five_attempts_per_minute(): void
    {
        $user = $this->createUserWithRole('REQUESTER');
        $payload = ['username' => $user->username, 'password' => 'password'];

        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/login', $payload, $this->spaHeaders())->assertOk();
        }

        $this->postJson('/api/login', $payload, $this->spaHeaders())->assertStatus(429);
    }

    public function test_rate_limit_is_scoped_by_username(): void
    {
        $blocked = $this->createUserWithRole('REQUESTER');
        $other = $this->createUserWithRole('TECH');

        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/login', [
                'username' => $blocked->username,
                'password' => 'password',
            ], $this->spaHeaders())->assertOk();
        }

        $this->postJson('/api/login', [
            'username' => $blocked->username,
            'password' => 'password',
        ], $this->spaHeaders())->assertStatus(429);

        $this->postJson('/api/login', [
            'username' => $other->username,
            'password' => 'password',
        ], $this->spaHeaders())->assertOk();
    }

    public function test_rate_limit_is_not_shared_between_different_usernames(): void
    {
        $first = $this->createUserWithRole('REQUESTER');
        $second = $this->createUserWithRole('TECH');

        // Agota el límite solo con el primer username.
        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/login', [
                'username' => $first->username,
                'password' => 'password',
            ], $this->spaHeaders())->assertOk();
        }

        // El segundo username conserva su propio margen de intentos.
        $this->postJson('/api/login', [
            'username' => $first->username,
            'password' => 'password',
        ], $this->spaHeaders())->assertStatus(429);

        $this->postJson('/api/login', [
            'username' => $second->username,
            'password' => 'password',
        ], $this->spaHeaders())->assertOk();
    }
}
