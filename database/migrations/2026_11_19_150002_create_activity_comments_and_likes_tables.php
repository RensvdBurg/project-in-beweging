<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reacties en likes op activiteiten (overgenomen uit `activity_post_comments`
     * en `activity_post_likes` van de oude MySQL-database). Er is nog geen UI voor.
     */
    public function up(): void
    {
        Schema::create('activity_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('body', 1000);
            $table->timestamps();
            $table->index(['activity_id', 'created_at']);
        });

        Schema::create('activity_likes', function (Blueprint $table) {
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['activity_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_likes');
        Schema::dropIfExists('activity_comments');
    }
};
