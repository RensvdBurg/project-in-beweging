<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Maakt de challenges-tabel aan. De challenges zelf worden toegevoegd in
     * `2026_11_19_150000_add_bewegingsapp_challenges`.
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
    }

    public function down(): void
    {
        Schema::dropIfExists('challenges');
    }
};
