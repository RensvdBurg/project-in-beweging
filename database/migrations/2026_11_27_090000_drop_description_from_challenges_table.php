<?php

use App\Support\ServerOnlyMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use ServerOnlyMigration;

    /**
     * Verwijdert de ongebruikte kolom `description` uit challenges; de uitleg staat
     * in `description_extra`. Ook op bestaande toestellen (NativePHP draait `migrate --force`).
     */
    public function up(): void
    {
        if (Schema::hasColumn('challenges', 'description')) {
            Schema::table('challenges', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
    }

    public function down(): void
    {
        Schema::table('challenges', function (Blueprint $table) {
            $table->text('description')->nullable()->after('title');
        });
    }
};
