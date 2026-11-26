<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PhoneNotifications;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class AuthController extends Controller
{
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

    /** Maximaal 5 mislukte pogingen per account en 30 per IP-adres, per 15 minuten (zoals vroeger). */
    private const MAX_ATTEMPTS_PER_ACCOUNT = 5;

    private const MAX_ATTEMPTS_PER_IP = 30;

    private const ATTEMPT_WINDOW_SECONDS = 900;

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

        // Pogingen tellen via de cache (database-driver: werkt ook met SQLite op het toestel).
        $limits = [
            'login:account:'.sha1(mb_strtolower($identifier)) => self::MAX_ATTEMPTS_PER_ACCOUNT,
            'login:ip:'.$request->ip() => self::MAX_ATTEMPTS_PER_IP,
        ];

        foreach ($limits as $key => $maxAttempts) {
            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                $minutes = max(1, (int) ceil(RateLimiter::availableIn($key) / 60));
                $unit = $minutes === 1 ? 'minuut' : 'minuten';

                return $this->redirectWithMessage("Te veel inlogpogingen. Probeer het over {$minutes} {$unit} opnieuw.", 'error', 'login')
                    ->withInput($request->only('email'));
            }
        }

        // LOWER(): SQLite vergelijkt standaard hoofdlettergevoelig, de oude MySQL-database niet.
        $lowered = mb_strtolower($identifier);
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$lowered])
            ->orWhereRaw('LOWER(username) = ?', [$lowered])
            ->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            foreach (array_keys($limits) as $key) {
                RateLimiter::hit($key, self::ATTEMPT_WINDOW_SECONDS);
            }

            return $this->redirectWithMessage('E-mailadres/gebruikersnaam of wachtwoord is onjuist.', 'error', 'login')
                ->withInput($request->only('email'));
        }

        // Gelukt: de teller van dit account mag weer op nul.
        RateLimiter::clear(array_key_first($limits));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home');
    }

    public function register(Request $request): RedirectResponse
    {
        $passwordBytes = function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || strlen($value) < 8 || strlen($value) > 72) {
                $fail('Kies een wachtwoord van minimaal 8 en maximaal 72 letters.');
            }
        };

        $birthDate = function (string $attribute, mixed $value, Closure $fail) use ($request): void {
            $day = filter_var($request->input('day'), FILTER_VALIDATE_INT);
            $month = filter_var($request->input('month'), FILTER_VALIDATE_INT);
            $year = filter_var($request->input('year'), FILTER_VALIDATE_INT);

            if (
                $day === false || $month === false || $year === false
                || $year < (int) now()->format('Y') - 110
                || ! checkdate($month, $day, $year)
                || sprintf('%04d-%02d-%02d', $year, $month, $day) > now()->toDateString()
            ) {
                $fail('Kies een geldige geboortedatum.');
            }
        };

        $validator = Validator::make($request->all(), [
            'email' => ['bail', 'required', 'string', 'email', 'max:254', $this->uniqueIgnoringCase('email')],
            'username' => ['bail', 'required', 'string', 'regex:/\A[A-Za-z0-9_.-]{3,30}\z/', $this->uniqueIgnoringCase('username')],
            'fullName' => ['required', 'string', 'max:150'],
            'password' => ['bail', 'required', $passwordBytes],
            'confirmPassword' => ['same:password'],
            'day' => ['bail', 'required', $birthDate],
        ], [
            // Per regel (geen "email.*"): anders overschrijft die de melding "al in gebruik".
            'email.required' => 'Vul een geldig e-mailadres in.',
            'email.string' => 'Vul een geldig e-mailadres in.',
            'email.email' => 'Vul een geldig e-mailadres in.',
            'email.max' => 'Vul een geldig e-mailadres in.',
            'username.required' => 'Je gebruikersnaam moet 3 tot 30 tekens bevatten (letters, cijfers, punt, streepje of underscore).',
            'username.string' => 'Je gebruikersnaam moet 3 tot 30 tekens bevatten (letters, cijfers, punt, streepje of underscore).',
            'username.regex' => 'Je gebruikersnaam moet 3 tot 30 tekens bevatten (letters, cijfers, punt, streepje of underscore).',
            'fullName.*' => 'Vul een naam van maximaal 150 tekens in.',
            'password.required' => 'Kies een wachtwoord van minimaal 8 en maximaal 72 letters.',
            'confirmPassword.same' => 'Wachtwoorden komen niet overeen.',
            'day.required' => 'Kies een geldige geboortedatum.',
        ]);

        if ($validator->fails()) {
            // Wachtwoorden nooit terugzetten; een bezet of ongeldig e-mailadres of gebruikersnaam
            // ook niet, zodat de gebruiker direct ziet welk veld opnieuw moet (zoals vroeger).
            $failedField = $validator->errors()->keys()[0] ?? null;
            $except = ['password', 'confirmPassword'];

            if (in_array($failedField, ['email', 'username'], true)) {
                $except[] = $failedField;
            }

            return $this->redirectWithMessage($validator->errors()->first(), 'error', 'register')
                ->withInput($request->except($except));
        }

        $data = $validator->validated();

        $user = User::query()->create([
            'email' => trim($data['email']),
            'username' => trim($data['username']),
            'full_name' => trim($data['fullName']),
            'password' => $data['password'],
            'birth_date' => sprintf('%04d-%02d-%02d', $request->integer('year'), $request->integer('month'), $request->integer('day')),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home');
    }

    public function logout(Request $request, PhoneNotifications $notifications): RedirectResponse
    {
        // Geen meldingen meer van dit account op deze telefoon.
        $notifications->clear();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->redirectWithMessage('Je bent uitgelogd.', 'success', 'login');
    }

    /**
     * Uniek zonder op hoofdletters te letten: "Sanne" mag niet bestaan naast "sanne".
     * (SQLite vergelijkt standaard hoofdlettergevoelig, de oude MySQL-database niet.)
     */
    private function uniqueIgnoringCase(string $column): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($column): void {
            $taken = User::query()
                ->whereRaw("LOWER({$column}) = ?", [mb_strtolower(trim((string) $value))])
                ->exists();

            if ($taken) {
                $fail('Dit e-mailadres of deze gebruikersnaam is al in gebruik.');
            }
        };
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
