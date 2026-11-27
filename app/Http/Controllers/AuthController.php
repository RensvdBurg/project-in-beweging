<?php

namespace App\Http\Controllers;

use App\Auth\ApiSessionGuard;
use App\Services\Api\ApiException;
use App\Services\Api\UpMoveApi;
use App\Services\PhoneNotifications;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Inloggen, registreren en uitloggen. De controle gebeurt door de API (op de server);
 * deze controller toont alleen de meldingen, zoals voorheen.
 */
class AuthController extends Controller
{
    public function __construct(private readonly UpMoveApi $api) {}

    /**
     * Login- en registratiescherm (één pagina met twee panelen).
     */
    public function showLogin(): View
    {
        return view('auth.login', [
            'authMessage' => session('auth_message', ''),
            'authMessageType' => session('auth_message_type', 'error'),
            'activeAuthMode' => session('auth_mode', 'login'),
        ]);
    }

    /**
     * Inloggen met e-mailadres óf gebruikersnaam (hoofdletters maken niet uit).
     */
    public function login(Request $request): RedirectResponse
    {
        $identifier = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');

        if ($identifier === '' || $password === '') {
            return $this->redirectWithMessage('Vul je e-mailadres of gebruikersnaam en je wachtwoord in.', 'error', 'login')
                ->withInput($request->only('email'));
        }

        try {
            $result = $this->api->login($identifier, $password, $this->deviceName());
        } catch (ApiException $exception) {
            return $this->redirectWithMessage($exception->firstError(), 'error', 'login')
                ->withInput($request->only('email'));
        }

        $this->guard()->login($result['token'], $result['user']);

        return redirect()->route('home');
    }

    public function register(Request $request): RedirectResponse
    {
        $day = filter_var($request->input('day'), FILTER_VALIDATE_INT);
        $month = filter_var($request->input('month'), FILTER_VALIDATE_INT);
        $year = filter_var($request->input('year'), FILTER_VALIDATE_INT);
        $birthDate = $day !== false && $month !== false && $year !== false && checkdate($month, $day, $year)
            ? sprintf('%04d-%02d-%02d', $year, $month, $day)
            : '';

        try {
            $result = $this->api->register([
                'email' => trim((string) $request->input('email', '')),
                'username' => trim((string) $request->input('username', '')),
                'full_name' => trim((string) $request->input('fullName', '')),
                'password' => (string) $request->input('password', ''),
                'password_confirmation' => (string) $request->input('confirmPassword', ''),
                'birth_date' => $birthDate,
                'device_name' => $this->deviceName(),
            ]);
        } catch (ApiException $exception) {
            // Wachtwoorden nooit terugzetten; een bezet of ongeldig e-mailadres of gebruikersnaam
            // ook niet, zodat de gebruiker direct ziet welk veld opnieuw moet (zoals vroeger).
            $except = ['password', 'confirmPassword'];

            if (in_array($exception->firstErrorField(), ['email', 'username'], true)) {
                $except[] = $exception->firstErrorField();
            }

            return $this->redirectWithMessage($exception->firstError(), 'error', 'register')
                ->withInput($request->except($except));
        }

        $this->guard()->login($result['token'], $result['user']);

        return redirect()->route('home');
    }

    public function logout(Request $request, PhoneNotifications $notifications): RedirectResponse
    {
        // Geen meldingen meer van dit account op deze telefoon.
        $notifications->clear();

        try {
            $this->api->logout();
        } catch (ApiException) {
            // Geen verbinding of token al ongeldig: lokaal toch uitloggen.
        }

        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->redirectWithMessage('Je bent uitgelogd.', 'success', 'login');
    }

    private function guard(): ApiSessionGuard
    {
        /** @var ApiSessionGuard $guard */
        $guard = Auth::guard();

        return $guard;
    }

    /**
     * Naam van het token op de server, zodat je ziet waar iemand is ingelogd.
     */
    private function deviceName(): string
    {
        return config('nativephp-internal.running', false) ? 'UpMove-app ('.config('nativephp-internal.platform', 'telefoon').')' : 'UpMove in de browser';
    }

    private function redirectWithMessage(string $message, string $type, string $mode): RedirectResponse
    {
        return redirect()->route('login')->with([
            'auth_message' => $message,
            'auth_message_type' => $type,
            'auth_mode' => $mode,
        ]);
    }
}
