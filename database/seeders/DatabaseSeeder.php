<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Maakt een testaccount aan (inloggen met "test" / "password").
     * De challenges worden al door de migratie ingevoegd.
     */
    public function run(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'username' => 'test',
            'full_name' => 'Test Gebruiker',
        ]);
    }
}
