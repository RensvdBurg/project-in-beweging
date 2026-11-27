<?php

namespace App\Services\Api;

use Illuminate\Contracts\Session\Session;
use Native\Mobile\Facades\SecureStorage;
use Throwable;

/**
 * Het API-token van de ingelogde gebruiker.
 *
 * - In de sessie, voor elk verzoek.
 * - Op de telefoon ook in SecureStorage (Keychain/Keystore), zodat je ingelogd blijft
 *   als de sessie verloopt of de app opnieuw start. Nooit in SQLite.
 */
class ApiToken
{
    public const SESSION_KEY = 'upmove.api_token';

    private const SECURE_STORAGE_KEY = 'upmove_api_token';

    public function __construct(private readonly Session $session) {}

    public function get(): ?string
    {
        $token = $this->session->get(self::SESSION_KEY);

        if (is_string($token) && $token !== '') {
            return $token;
        }

        return $this->storedOnPhone();
    }

    /**
     * Token dat nog op de telefoon staat van een eerdere sessie (alleen in de app).
     */
    public function storedOnPhone(): ?string
    {
        if (! $this->onPhone()) {
            return null;
        }

        try {
            $token = SecureStorage::get(self::SECURE_STORAGE_KEY);
        } catch (Throwable) {
            return null;
        }

        return is_string($token) && $token !== '' ? $token : null;
    }

    public function set(string $token): void
    {
        $this->session->put(self::SESSION_KEY, $token);

        if ($this->onPhone()) {
            try {
                SecureStorage::set(self::SECURE_STORAGE_KEY, $token);
            } catch (Throwable) {
                // Zonder SecureStorage blijft het token alleen in de sessie staan.
            }
        }
    }

    public function forget(): void
    {
        $this->session->forget(self::SESSION_KEY);

        if ($this->onPhone()) {
            try {
                SecureStorage::delete(self::SECURE_STORAGE_KEY);
            } catch (Throwable) {
                // Niets op te ruimen.
            }
        }
    }

    private function onPhone(): bool
    {
        return (bool) config('nativephp-internal.running', false);
    }
}
