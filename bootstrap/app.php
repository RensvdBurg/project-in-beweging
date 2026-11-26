<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // Admin-dashboard: eigen routebestand, alleen voor beheerders.
        then: function (): void {
            Route::middleware(['web', 'auth', 'admin'])
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['admin' => EnsureUserIsAdmin::class]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('home'));

        // Wachtwoordvelden nooit trimmen (spaties tellen mee in een wachtwoord).
        $middleware->trimStrings(except: ['confirmPassword', 'new_password', 'confirm_password']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Verlopen CSRF-token: geen kale 419-pagina, maar terug met een duidelijke melding
        // (zelfde meldingen als in de vanilla PHP-versie). Laravel zet de
        // TokenMismatchException vóór deze callback om naar een HttpException (419).
        $exceptions->render(function (HttpException $exception, Request $request) {
            if (! $exception->getPrevious() instanceof TokenMismatchException || $request->expectsJson()) {
                return null;
            }

            return match (true) {
                $request->routeIs('challenges.next') => redirect()->route('home')
                    ->with('challenge_notice', 'Je sessie is verlopen. Ververs de pagina en probeer het opnieuw.'),
                $request->routeIs('challenges.complete') => redirect()->route('home')
                    ->with('challenge_completion_notice', 'Je sessie is verlopen. Ververs de pagina en probeer het opnieuw.'),
                $request->routeIs('account.*') => redirect()->route('account')->with([
                    'status_message' => 'Je sessie is verlopen. Vernieuw de pagina en probeer het opnieuw.',
                    'status_type' => 'error',
                ]),
                default => redirect()->route('login')->with([
                    'auth_message' => 'Je sessie is verlopen. Probeer het opnieuw.',
                    'auth_message_type' => 'error',
                    'auth_mode' => $request->routeIs('register') ? 'register' : 'login',
                ]),
            };
        });
    })->create();
