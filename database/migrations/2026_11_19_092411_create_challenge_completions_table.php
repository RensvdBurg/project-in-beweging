<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registreert voltooide opdrachten, zodat dezelfde opdracht binnen hetzelfde
     * 90-minutenblok (challenge_interval) maar één keer XP oplevert.
     */
    public function up(): void
    {
        Schema::create('challenge_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('challenge_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('challenge_interval');
            $table->timestamps();

            $table->unique(['user_id', 'challenge_id', 'challenge_interval']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('challenge_completions');
    }
};
