<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RemoveUnusedDefaultChallengesTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_installations_have_no_old_default_challenges(): void
    {
        $this->assertFalse(Challenge::query()->where('img', 'like', '%.svg')->exists());
    }

    public function test_migration_removes_old_default_challenges_on_existing_devices(): void
    {
        $now = now();
        DB::table('challenges')->insert([
            'id' => 1, 'title' => 'Squats', 'description' => 'Doe 15 squats.', 'img' => 'images/challenges/squats.svg',
            'kcal_burned' => 8, 'difficulty' => 'Gemiddeld', 'xp_reward' => 20, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $user = User::factory()->create();
        $user->forceFill(['current_challenge_id' => 1])->save();
        DB::table('challenge_completions')->insert([
            'user_id' => $user->id, 'challenge_id' => 1, 'challenge_interval' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);

        $migration = require database_path('migrations/2026_11_26_100000_remove_unused_default_challenges.php');
        $migration->up();

        $this->assertDatabaseMissing('challenges', ['id' => 1]);
        $this->assertDatabaseMissing('challenge_completions', ['challenge_id' => 1]);
        $this->assertNull($user->fresh()->current_challenge_id);
        $this->assertSame(50, Challenge::query()->count());
    }
}
