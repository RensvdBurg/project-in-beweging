<?php

namespace Tests\Feature\Api;

use App\Models\Challenge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiChallengeTest extends TestCase
{
    use RefreshDatabase;

    public function test_challenges_are_listed_without_old_description(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson(route('api.v1.challenges.index'))
            ->assertOk()
            ->assertJsonCount(Challenge::query()->count(), 'data')
            ->assertJsonMissingPath('data.0.description')
            ->assertJsonStructure(['data' => [['id', 'title', 'description_extra', 'img', 'image_url', 'kcal_burned', 'difficulty', 'xp_reward']]]);
    }

    public function test_a_challenge_added_in_admin_is_visible_to_every_user(): void
    {
        $challenge = Challenge::query()->create([
            'title' => 'Nieuwe challenge', 'img' => 'https://images.unsplash.com/photo-1', 'kcal_burned' => 5, 'difficulty' => 'Beginner', 'xp_reward' => 10,
        ]);

        foreach (User::factory()->count(2)->create() as $user) {
            Sanctum::actingAs($user);
            $this->getJson(route('api.v1.challenges.show', $challenge))->assertOk()->assertJsonPath('data.title', 'Nieuwe challenge');
        }
    }

    public function test_current_next_and_complete(): void
    {
        $user = User::factory()->create(['xp' => 0]);
        Sanctum::actingAs($user);

        $current = $this->getJson(route('api.v1.challenges.current'))
            ->assertOk()
            ->assertJsonPath('completed', false)
            ->assertJsonPath('changes_remaining', 2);

        $this->postJson(route('api.v1.challenges.next'))
            ->assertOk()
            ->assertJsonPath('changes_remaining', 1)
            ->assertJsonPath('message', 'Nieuwe opdracht geladen. Je kunt dit vandaag nog 1 keer doen.');

        $completed = $this->postJson(route('api.v1.challenges.complete'))->assertOk()->assertJsonPath('completed', true);
        $reward = $completed->json('challenge.xp_reward');
        $this->assertSame($reward, $completed->json('xp'));
        $this->assertSame($reward, $user->fresh()->xp);
        $this->assertNotSame($current->json('challenge.id'), null);

        $this->postJson(route('api.v1.challenges.complete'))->assertJsonPath('message', 'Je hebt deze opdracht al voltooid.');
        $this->assertSame($reward, $user->fresh()->xp);
    }

    public function test_leaderboard_and_profiles(): void
    {
        $me = User::factory()->create(['username' => 'ik', 'xp' => 5]);
        $top = User::factory()->create(['username' => 'top', 'xp' => 500]);
        Sanctum::actingAs($me);

        $this->getJson(route('api.v1.leaderboard'))
            ->assertOk()
            ->assertJsonPath('rankings.0.username', 'top')
            ->assertJsonMissingPath('rankings.0.email')
            ->assertJsonPath('my_rank', 2);

        $this->getJson(route('api.v1.users.show', $top))
            ->assertOk()
            ->assertJsonPath('rank', 1)
            ->assertJsonMissingPath('user.email')
            ->assertJsonMissingPath('user.birth_date');
    }
}
