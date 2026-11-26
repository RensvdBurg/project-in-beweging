<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Registreren, inloggen en uitloggen via de API. Geeft een Sanctum-token terug
 * dat de app in SecureStorage bewaart en bij elk verzoek meestuurt.
 */
class AuthController extends Controller
{
    /** Maximaal 5 mislukte pogingen per account en 30 per IP-adres, per 15 minuten (zoals in de app). */
    private const MAX_ATTEMPTS_PER_ACCOUNT = 5;

    private const MAX_ATTEMPTS_PER_IP = 30;

    private const ATTEMPT_WINDOW_SECONDS = 900;

    public const USERNAME_MESSAGE = 'Je gebruikersnaam moet 3 tot 30 tekens bevatten (letters, cijfers, punt, streepje of underscore).';

    public const PASSWORD_MESSAGE = 'Kies een wachtwoord van minimaal 8 en maximaal 72 letters.';

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['bail', 'required', 'string', 'email', 'max:254', self::uniqueIgnoringCase('email')],
            'username' => ['bail', 'required', 'string', 'regex:/\A[A-Za-z0-9_.-]{3,30}\z/', self::uniqueIgnoringCase('username')],
            'full_name' => ['required', 'string', 'max:150'],
            'password' => ['bail', 'required', self::passwordBytes()],
            'password_confirmation' => ['same:password'],
            'birth_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after:'.now()->subYears(110)->toDateString()],
            'device_name' => ['nullable', 'string', 'max:100'],
        ], [
            'email.required' => 'Vul een geldig e-mailadres in.',
            'email.string' => 'Vul een geldig e-mailadres in.',
            'email.email' => 'Vul een geldig e-mailadres in.',
            'email.max' => 'Vul een geldig e-mailadres in.',
            'username.required' => self::USERNAME_MESSAGE,
            'username.string' => self::USERNAME_MESSAGE,
            'username.regex' => self::USERNAME_MESSAGE,
            'full_name.*' => 'Vul een naam van maximaal 150 tekens in.',
            'password.required' => self::PASSWORD_MESSAGE,
            'password_confirmation.same' => 'Wachtwoorden komen niet overeen.',
            'birth_date.*' => 'Kies een geldige geboortedatum.',
        ]);

        $user = User::query()->create([
            'email' => trim($data['email']),
            'username' => trim($data['username']),
            'full_name' => trim($data['full_name']),
            'password' => $data['password'],
            'birth_date' => $data['birth_date'],
        ]);

        AuditLog::record('register', "{$user->username} heeft een account aangemaakt.", $user);

        return $this->tokenResponse($request, $user, 201);
    }

    /**
     * Inloggen met e-mailadres óf gebruikersnaam (hoofdletters maken niet uit).
     */
    public function login(Request $request): JsonResponse
    {
        $identifier = trim((string) $request->input('login', ''));
        $password = (string) $request->input('password', '');

        if ($identifier === '' || $password === '') {
            throw ValidationException::withMessages(['login' => 'Vul je e-mailadres of gebruikersnaam en je wachtwoord in.']);
        }

        $limits = [
            'login:account:'.sha1(mb_strtolower($identifier)) => self::MAX_ATTEMPTS_PER_ACCOUNT,
            'login:ip:'.$request->ip() => self::MAX_ATTEMPTS_PER_IP,
        ];

        foreach ($limits as $key => $maxAttempts) {
            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                $seconds = RateLimiter::availableIn($key);
                $minutes = max(1, (int) ceil($seconds / 60));
                $unit = $minutes === 1 ? 'minuut' : 'minuten';

                return response()->json(['message' => "Te veel inlogpogingen. Probeer het over {$minutes} {$unit} opnieuw."], 429)
                    ->header('Retry-After', (string) $seconds);
            }
        }

        $lowered = mb_strtolower($identifier);
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$lowered])
            ->orWhereRaw('LOWER(username) = ?', [$lowered])
            ->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            foreach (array_keys($limits) as $key) {
                RateLimiter::hit($key, self::ATTEMPT_WINDOW_SECONDS);
            }

            AuditLog::record('login_failed', 'Mislukte inlogpoging voor "'.mb_substr($identifier, 0, 100).'".', $user);

            throw ValidationException::withMessages(['login' => 'E-mailadres/gebruikersnaam of wachtwoord is onjuist.']);
        }

        RateLimiter::clear(array_key_first($limits));
        AuditLog::record('login', "{$user->username} is ingelogd.", $user);

        return $this->tokenResponse($request, $user);
    }

    /**
     * Trekt het token in waarmee dit verzoek is gedaan (alleen deze telefoon).
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        AuditLog::record('logout', "{$user->username} is uitgelogd.", $user);
        $user->currentAccessToken()->delete();

        return response()->json(['message' => 'Je bent uitgelogd.']);
    }

    private function tokenResponse(Request $request, User $user, int $status = 200): JsonResponse
    {
        $deviceName = trim((string) $request->input('device_name', '')) ?: 'app';

        return response()->json([
            'token' => $user->createToken(mb_substr($deviceName, 0, 100))->plainTextToken,
            'user' => (new UserResource($user->refresh()))->withPrivateFields(),
        ], $status);
    }

    /**
     * Bcrypt kapt af na 72 bytes; daarom tellen we bytes, net als bij registreren in de app.
     */
    public static function passwordBytes(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || strlen($value) < 8 || strlen($value) > 72) {
                $fail(self::PASSWORD_MESSAGE);
            }
        };
    }

    /**
     * Uniek zonder op hoofdletters te letten: "Sanne" mag niet bestaan naast "sanne".
     */
    public static function uniqueIgnoringCase(string $column, ?int $ignoreId = null): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($column, $ignoreId): void {
            $taken = User::query()
                ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
                ->whereRaw("LOWER({$column}) = ?", [mb_strtolower(trim((string) $value))])
                ->exists();

            if ($taken) {
                $fail($column === 'email' ? 'Dit e-mailadres is al in gebruik.' : 'Deze gebruikersnaam is al in gebruik.');
            }
        };
    }
}
