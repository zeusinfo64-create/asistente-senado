<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Autentica con credenciales locales y crea la sesión (SPA stateful).
     *
     * El identificador es el username institucional. El correo NO participa
     * del login: solo se usa como dato de perfil.
     *
     * 401: credenciales inválidas. 403: credenciales válidas de un usuario
     * con is_active = false.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->safe()->only(['username', 'password']))) {
            return response()->json(['message' => 'Credenciales inválidas.'], 401);
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user->is_active) {
            Auth::logout();

            return response()->json(['message' => 'El usuario está inactivo.'], 403);
        }

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $user->last_login_at = now();
        $user->save();

        return response()->json([
            'message' => 'Autenticación exitosa.',
            'user' => $this->userPayload($user),
        ]);
    }

    /**
     * Cierra la sesión actual e invalida la cookie de sesión.
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    /**
     * Perfil y contexto del usuario autenticado (funciona como endpoint de
     * perfil de FASE 6.1): usuario, roles y unidad organizacional.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json(['user' => $this->userPayload($user)]);
    }

    /**
     * Contexto del usuario autenticado para el frontend (perfil en /api/me).
     *
     * No expone password, remember_token ni columnas internas.
     *
     * @return array{id: int, username: string, first_name: string, last_name: string, full_name: string, email: string, is_active: bool, roles: array<int, array{code: string, name: string}>, organizational_unit: array{id: int, code: string|null, name: string}|null}
     */
    private function userPayload(User $user): array
    {
        $user->loadMissing('roles', 'organizationalUnit');

        $unit = $user->organizationalUnit;

        return [
            'id' => $user->id,
            'username' => $user->username,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'full_name' => trim($user->first_name.' '.$user->last_name),
            'email' => $user->email,
            'is_active' => $user->is_active,
            'roles' => $user->roles
                ->sortBy('code')
                ->map(fn (Role $role): array => [
                    'code' => $role->code,
                    'name' => $role->name,
                ])
                ->values()
                ->all(),
            'organizational_unit' => $unit === null ? null : [
                'id' => $unit->id,
                'code' => $unit->code,
                'name' => $unit->name,
            ],
        ];
    }
}
