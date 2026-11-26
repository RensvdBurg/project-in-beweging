<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Vervangt de Unsplash-links van de challenges uit `bewegingsapp` door lokale
     * kopieën in public/images/challenges, zodat de afbeeldingen ook offline werken.
     */
    public function up(): void
    {
        DB::table('challenges')
            ->where('img', 'like', 'https://images.unsplash.com/photo-%')
            ->get(['id', 'img'])
            ->each(function (object $challenge): void {
                if (preg_match('#^https://images\.unsplash\.com/photo-([0-9a-f-]+)#', $challenge->img, $match)) {
                    DB::table('challenges')
                        ->where('id', $challenge->id)
                        ->update(['img' => "images/challenges/unsplash-{$match[1]}.jpg"]);
                }
            });
    }

    public function down(): void
    {
        // Niet terugzetten: de lokale afbeeldingen blijven geldig.
    }
};
