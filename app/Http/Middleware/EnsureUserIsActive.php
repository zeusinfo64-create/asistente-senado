<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Bloquea toda petición de un usuario autenticado con is_active = false.
     *
     * Se aplica al grupo api completo (falla por defecto) salvo a los puntos de
     * entrada de sesión: login valida is_active sobre las credenciales enviadas
     * y logout debe poder cerrar una sesión ya inactiva.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('auth.login', 'auth.logout')) {
            return $next($request);
        }

        $user = Auth::guard('sanctum')->user();

        if ($user instanceof User && ! $user->is_active) {
            return response()->json([
                'message' => 'El usuario está inactivo.',
            ], 403);
        }

        return $next($request);
    }
}
