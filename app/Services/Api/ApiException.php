<?php

namespace App\Services\Api;

use RuntimeException;

/**
 * De API gaf een fout terug (of was niet bereikbaar). De melding is Nederlands en
 * kan zo aan de gebruiker worden getoond.
 */
class ApiException extends RuntimeException
{
    /**
     * @param  array<string, list<string>>  $errors  Veldfouten bij een 422 (veld => meldingen).
     */
    public function __construct(
        string $message,
        public readonly int $status = 0,
        public readonly array $errors = [],
    ) {
        parent::__construct($message, $status);
    }

    /** Geen verbinding met de server (time-out, geen internet, server uit). */
    public function isConnectionError(): bool
    {
        return $this->status === 0;
    }

    /** Het token is ongeldig of ingetrokken: opnieuw inloggen. */
    public function isUnauthenticated(): bool
    {
        return $this->status === 401;
    }

    public function isValidationError(): bool
    {
        return $this->status === 422;
    }

    /**
     * Eerste veldfout (of de algemene melding).
     */
    public function firstError(?string $field = null): string
    {
        if ($field !== null) {
            return $this->errors[$field][0] ?? '';
        }

        foreach ($this->errors as $messages) {
            if (($messages[0] ?? '') !== '') {
                return $messages[0];
            }
        }

        return $this->getMessage();
    }

    /**
     * Veld met de eerste fout, bijv. "username".
     */
    public function firstErrorField(): ?string
    {
        return array_key_first($this->errors);
    }
}
