<?php

namespace Tests\Feature\Admin;

use App\Models\Challenge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminChallengeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    /**
     * @return array<string, mixed>
     */
    private function validData(array $overrides = []): array
    {
        return $overrides + [
            'title' => 'Tien squats',
            'description_extra' => 'Zak rustig door je knieën.',
            'img' => 'images/challenges/unsplash-1476480862126-209bfaa8edc8.jpg',
            'kcal_burned' => 8,
            'difficulty' => 'Beginner',
            'xp_reward' => 10,
        ];
    }

    public function test_index_lists_and_searches_challenges(): void
    {
        $challenge = Challenge::query()->orderBy('id')->firstOrFail();

        $this->get(route('admin.challenges.index'))->assertSee($challenge->title);
        $this->get(route('admin.challenges.index', ['q' => 'bestaatzekerniet']))->assertSee('Geen challenges gevonden.');
    }

    public function test_admin_can_add_a_challenge(): void
    {
        $this->post(route('admin.challenges.store'), $this->validData())
            ->assertRedirect(route('admin.challenges.index'));

        $this->assertDatabaseHas('challenges', ['title' => 'Tien squats', 'xp_reward' => 10, 'description_extra' => 'Zak rustig door je knieën.']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin_challenge_created']);
    }

    public function test_image_must_exist_in_public_or_be_a_url(): void
    {
        $this->post(route('admin.challenges.store'), $this->validData(['img' => 'images/bestaat-niet.jpg']))
            ->assertSessionHasErrors('img');
        $this->post(route('admin.challenges.store'), $this->validData(['img' => '../.env']))
            ->assertSessionHasErrors('img');
        $this->post(route('admin.challenges.store'), $this->validData(['img' => 'https://images.unsplash.com/photo-1?w=400']))
            ->assertSessionHasNoErrors();
    }

    public function test_admin_can_edit_a_challenge(): void
    {
        $challenge = Challenge::query()->firstOrFail();

        $this->put(route('admin.challenges.update', $challenge), $this->validData(['title' => 'Nieuwe titel', 'xp_reward' => 25]))
            ->assertRedirect(route('admin.challenges.index'));

        $this->assertSame('Nieuwe titel', $challenge->fresh()->title);
        $this->assertSame(25, $challenge->fresh()->xp_reward);
    }

    public function test_admin_can_delete_a_challenge_and_users_keep_their_xp(): void
    {
        $challenge = Challenge::query()->firstOrFail();
        $user = User::factory()->create(['xp' => 30]);
        $user->forceFill(['current_challenge_id' => $challenge->id])->save();
        DB::table('challenge_completions')->insert([
            'user_id' => $user->id, 'challenge_id' => $challenge->id, 'challenge_interval' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->delete(route('admin.challenges.destroy', $challenge))->assertRedirect(route('admin.challenges.index'));

        $this->assertModelMissing($challenge);
        $this->assertDatabaseMissing('challenge_completions', ['challenge_id' => $challenge->id]);
        $this->assertNull($user->fresh()->current_challenge_id);
        $this->assertSame(30, $user->fresh()->xp);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin_challenge_deleted']);
    }
}
