<?php

namespace App\Providers;

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
        //
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
