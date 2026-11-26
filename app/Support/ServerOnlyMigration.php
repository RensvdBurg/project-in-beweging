<?php

namespace App\Support;

/**
 * Voor migraties met app-gegevens (gebruikers, challenges, activiteiten, …).
 *
 * Die staan alleen op de server in MySQL. NativePHP draait bij het opstarten van de app
 * `migrate --force` op de SQLite-database van de telefoon; daar slaan we deze migraties over.
 */
trait ServerOnlyMigration
{
    public function shouldRun(): bool
    {
        return ! config('nativephp-internal.running', false);
    }
}
