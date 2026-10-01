<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Auth\Exceptions\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Autoriza la petición cuando el usuario autenticado posee el rol indicado.
     *
     * No existe jerarquía de roles: se exige coincidencia exacta del código
     * (ADMIN, TECH o REQUESTER).
     *
     * @param  string  $role  Código de rol requerido.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new AuthenticationException;
        }

        if (! $user->roles->contains('code', $role)) {
            return response()->json([
                'message' => 'No tiene permisos para realizar esta acción.',
            ], 403);
        }

        return $next($request);
    }
}
