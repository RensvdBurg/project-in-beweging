<?php

namespace App\Providers;

use App\Auth\ApiSessionGuard;
use App\Services\Api\ApiHydrator;
use App\Services\Api\ApiToken;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Native\Mobile\JumpBridge;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->muteJumpBridgeWithoutDevice();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Inloggen in de app via de API (token + gebruiker in de sessie, geen database).
        Auth::extend('upmove', fn ($app) => new ApiSessionGuard($app['session.store'], $app->make(ApiToken::class), $app->make(ApiHydrator::class)));

        // API: 120 verzoeken per minuut per gebruiker (of per IP-adres zonder login).
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->id ?: $request->ip()));
    }

    /**
     * Op de ontwikkelcomputer (`php artisan serve`, zonder telefoon) probeert NativePHP bij
     * elke pagina een bericht naar een gekoppeld toestel te sturen (Jump-bridge op
     * 127.0.0.1:3002). Er luistert dan niets, en op Windows duurt een geweigerde verbinding
     * ~2 seconden: elke paginalading werd daardoor ~2 s trager.
     *
     * Zonder Jump-sessie (`php artisan native:jump` zet JUMP_BRIDGE_PORT) dempen we de bridge
     * daarom: bridge-aanroepen worden dan direct een lege, geslaagde no-op.
     * Op het toestel zelf is nativephp_call() een C-extensie (interne functie) en doen we niets.
     * (Op de computer is hij er ook al, maar als PHP-polyfill: NativePHP registreert vóór ons.)
     */
    private function muteJumpBridgeWithoutDevice(): void
    {
        $onDevice = function_exists('nativephp_call') && (new \ReflectionFunction('nativephp_call'))->isInternal();

        if ($onDevice || getenv('JUMP_BRIDGE_PORT') || ! class_exists(JumpBridge::class)) {
            return;
        }

        JumpBridge::instance()->mute();
    }
}
