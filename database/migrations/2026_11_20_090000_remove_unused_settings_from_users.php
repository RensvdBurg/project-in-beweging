<?php

use App\Support\ServerOnlyMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bewegingsherinneringen, stille uren en geluidseffecten zijn uit de instellingen
 * gehaald. Deze migratie haalt hun opgeslagen waarden uit users.settings (JSON).
 *
 * Bewust in PHP in plaats van SQL-JSON-functies, zodat het op SQLite (toestel)
 * en andere databases hetzelfde werkt.
 */
return new class extends Migration
{
    use ServerOnlyMigration;

    private const REMOVED = ['reminders', 'quietHours', 'sound'];

    public function up(): void
    {
        DB::table('users')->whereNotNull('settings')->orderBy('id')->each(function (object $user): void {
            $settings = json_decode($user->settings, true);

            if (! is_array($settings) || array_intersect_key($settings, array_flip(self::REMOVED)) === []) {
                return;
            }

            DB::table('users')->where('id', $user->id)->update([
                'settings' => json_encode(array_diff_key($settings, array_flip(self::REMOVED))),
            ]);
        });
    }

    public function down(): void
    {
        // Niets terug te zetten: de instellingen bestaan niet meer.
    }
};
