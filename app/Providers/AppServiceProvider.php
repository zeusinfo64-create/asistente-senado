<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Intentos de login por minuto por combinación username + IP.
     *
     * Valor de desarrollo: se documenta en el reporte de FASE 5 y debe
     * revisarse antes de producción.
     */
    private const LOGIN_RATE_LIMIT_ATTEMPTS = 5;

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('login', function (Request $request): Limit {
            return Limit::perMinute(self::LOGIN_RATE_LIMIT_ATTEMPTS)
                ->by(Str::lower((string) $request->input('username')).'|'.$request->ip());
        });
    }
}
