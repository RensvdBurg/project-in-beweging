<?php

use App\Support\ServerOnlyMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use ServerOnlyMigration;

    /**
     * Instellingen per gebruiker (frequentie, dagdoel, schakelaars, werk/school),
     * als JSON. Zie App\Support\UserSettings voor de standaardwaarden.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('settings')->nullable()->after('xp');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('settings');
        });
    }
};
