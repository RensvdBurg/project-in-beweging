<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Over mij"-tekst, overgenomen uit de oude MySQL-database `bewegingsapp`.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('about_me', 500)->default('')->after('full_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('about_me');
        });
    }
};
