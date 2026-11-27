<?php

use App\Support\ServerOnlyMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    use ServerOnlyMigration;

    /**
     * Afbeeldingen van de vijf oude standaard-challenges die niet (meer) gebruikt worden.
     * De bestanden hebben nooit in de app gezeten.
     */
    private const IMAGES = [
        'images/challenges/squats.svg',
        'images/challenges/stretchen.svg',
        'images/challenges/traplopen.svg',
        'images/challenges/jumping-jacks.svg',
        'images/challenges/wandelen.svg',
    ];

    /**
     * Verwijdert de vijf oude standaard-challenges van toestellen waar ze al in de
     * database staan. Voltooiingen verdwijnen mee (cascade); gebruikers met zo'n
     * challenge als huidige krijgen bij het volgende bezoek een nieuwe.
     */
    public function up(): void
    {
        $ids = DB::table('challenges')->whereIn('img', self::IMAGES)->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        DB::table('challenge_completions')->whereIn('challenge_id', $ids)->delete();
        DB::table('users')->whereIn('current_challenge_id', $ids)->update(['current_challenge_id' => null]);
        DB::table('challenges')->whereIn('id', $ids)->delete();
    }

    public function down(): void
    {
        // Bewust leeg: de afbeeldingen bestaan niet, dus terugzetten heeft geen zin.
    }
};
