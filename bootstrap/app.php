<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        $middleware->appendToGroup('api', EnsureUserIsActive::class);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        // Esta aplicación es una SPA sin ruta web `login`: el destino por defecto
        // del framework (route('login')) no existe y convertía un 401 de la API en
        // un 500 "Route [login] not defined". Para /api/* y para cualquier
        // petición que espere JSON se devuelve null, de modo que la
        // AuthenticationException se renderice como 401 JSON. Las peticiones web
        // conservan el comportamiento previo.
        $middleware->redirectGuestsTo(
            fn (Request $request): ?string => $request->is('api/*') || $request->expectsJson()
                ? null
                : route('login')
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
