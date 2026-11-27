<?php

namespace App\Auth;

use App\Models\User;
use App\Services\Api\ApiException;
use App\Services\Api\ApiHydrator;
use App\Services\Api\ApiToken;
use App\Services\Api\UpMoveApi;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Session\Session;

/**
 * Inloggen in de app via de API. Er wordt niets in een database opgezocht:
 * de sessie bewaart het API-token en de gegevens die de API over de gebruiker gaf.
 *
 * Op de telefoon staat het token ook in SecureStorage; is de sessie verlopen, dan
 * logt de guard met dat token automatisch weer in (GET /me).
 */
class ApiSessionGuard implements Guard
{
    use GuardHelpers;

    public const USER_KEY = 'upmove.api_user';

    private bool $restoreAttempted = false;

    public function __construct(
        private readonly Session $session,
        private readonly ApiToken $token,
        private readonly ApiHydrator $hydrator,
    ) {}

    public function user(): ?User
    {
        if ($this->user instanceof User) {
            return $this->user;
        }

        $data = $this->session->get(self::USER_KEY);

        if (is_array($data) && $this->token->get() !== null) {
            return $this->user = $this->hydrator->user($data);
        }

        return $this->restoreFromPhone();
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function validate(array $credentials = []): bool
    {
        return false;
    }

    /**
     * Na een geslaagde login of registratie via de API.
     *
     * @param  array<string, mixed>  $userData
     */
    public function login(string $token, array $userData): void
    {
        $this->session->migrate(true);
        $this->token->set($token);
        $this->remember($userData);
    }

    /**
     * Nieuwe gegevens van de gebruiker (na /me, /home of een wijziging) bewaren.
     *
     * @param  array<string, mixed>  $userData
     */
    public function remember(array $userData): User
    {
        $this->session->put(self::USER_KEY, $userData);

        return $this->user = $this->hydrator->user($userData);
    }

    /**
     * Alleen een paar velden bijwerken (bijv. de instellingen na het opslaan).
     *
     * @param  array<string, mixed>  $changes
     */
    public function merge(array $changes): ?User
    {
        $data = $this->session->get(self::USER_KEY);

        return is_array($data) ? $this->remember($changes + $data) : null;
    }

    public function logout(): void
    {
        $this->token->forget();
        $this->session->forget(self::USER_KEY);
        $this->user = null;
    }

    /**
     * Sessie verlopen, maar de telefoon heeft nog een token: gebruiker opnieuw ophalen.
     */
    private function restoreFromPhone(): ?User
    {
        if ($this->restoreAttempted || ($token = $this->token->storedOnPhone()) === null) {
            return null;
        }

        $this->restoreAttempted = true;
        $this->session->put(ApiToken::SESSION_KEY, $token);

        try {
            $me = app(UpMoveApi::class)->me();
        } catch (ApiException $exception) {
            if ($exception->isUnauthenticated()) {
                $this->logout();
            } else {
                $this->session->forget(ApiToken::SESSION_KEY);
            }

            return null;
        }

        return $this->remember($me['user']);
    }
}
