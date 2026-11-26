<?php

use App\Support\ServerOnlyMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * De challenge van het moment en het aantal directe wissels per dag stonden in de sessie.
 * Uitloggen en weer inloggen gaf dan een nieuwe challenge en weer 2 wissels.
 * Nu staan ze per gebruiker in de database.
 */
return new class extends Migration
{
    use ServerOnlyMigration;

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // Geen foreign key: verdwijnt de challenge, dan kiest ChallengeService gewoon een nieuwe.
            $table->unsignedBigInteger('current_challenge_id')->nullable();
            $table->unsignedBigInteger('current_challenge_interval')->nullable();
            $table->date('challenge_changes_date')->nullable();
            $table->unsignedTinyInteger('challenge_changes_count')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['current_challenge_id', 'current_challenge_interval', 'challenge_changes_date', 'challenge_changes_count']);
        });
    }
};
