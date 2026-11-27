<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabellen met app-gegevens die oudere versies van de app in de SQLite-database
     * van de telefoon zetten. Alles staat nu op de server (MySQL, via de API).
     */
    private const LOCAL_TABLES = [
        'activity_likes',
        'activity_comments',
        'activities',
        'challenge_completions',
        'audit_logs',
        'personal_access_tokens',
        'challenges',
        'sessions',
        'password_reset_tokens',
        'users',
    ];

    /**
     * Alleen op de telefoon: daar draait NativePHP `migrate --force` op SQLite.
     */
    public function shouldRun(): bool
    {
        return (bool) config('nativephp-internal.running', false);
    }

    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        foreach (self::LOCAL_TABLES as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Bewust leeg: de gegevens staan op de server.
    }
};
