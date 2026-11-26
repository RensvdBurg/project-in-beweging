<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Maakt de challenges-tabel aan en vult hem met de standaardopdrachten.
     *
     * De data staat bewust in de migratie (en niet in een seeder): NativePHP voert
     * op het toestel alleen `migrate --force` uit, geen `db:seed`.
     */
    public function up(): void
    {
        Schema::create('challenges', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('img');
            $table->unsignedInteger('kcal_burned');
            $table->string('difficulty', 50);
            $table->unsignedInteger('xp_reward');
            $table->timestamps();
        });

        $now = now();

        DB::table('challenges')->insert(array_map(
            fn (array $challenge): array => $challenge + ['created_at' => $now, 'updated_at' => $now],
            [
                ['title' => 'Squats', 'description' => 'Doe 15 squats naast je bureau.', 'img' => 'images/challenges/squats.svg', 'kcal_burned' => 8, 'difficulty' => 'Gemiddeld', 'xp_reward' => 20],
                ['title' => 'Stretchen', 'description' => 'Rek 2 minuten je nek, schouders en rug.', 'img' => 'images/challenges/stretchen.svg', 'kcal_burned' => 5, 'difficulty' => 'Makkelijk', 'xp_reward' => 10],
                ['title' => 'Traplopen', 'description' => 'Loop twee keer een trap op en af.', 'img' => 'images/challenges/traplopen.svg', 'kcal_burned' => 15, 'difficulty' => 'Gemiddeld', 'xp_reward' => 25],
                ['title' => 'Jumping jacks', 'description' => 'Doe 30 jumping jacks.', 'img' => 'images/challenges/jumping-jacks.svg', 'kcal_burned' => 12, 'difficulty' => 'Moeilijk', 'xp_reward' => 30],
                ['title' => 'Wandelen', 'description' => 'Maak een rondje van 5 minuten.', 'img' => 'images/challenges/wandelen.svg', 'kcal_burned' => 20, 'difficulty' => 'Makkelijk', 'xp_reward' => 15],
            ],
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('challenges');
    }
};
