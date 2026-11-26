<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LearnTest extends TestCase
{
    use RefreshDatabase;

    public function test_leren_screen_lists_all_challenges_with_search(): void
    {
        $challenges = Challenge::query()->orderBy('id')->get();
        $this->assertNotEmpty($challenges, 'De migraties horen standaard-challenges te bevatten.');

        $response = $this->actingAs(User::factory()->create())
            ->get(route('home', ['screen' => 'leren']))
            ->assertOk()
            ->assertSee('id="screen-leren"', false)
            ->assertSee('Leren &amp; Groeien', false)
            ->assertSee('id="challenge-search"', false)
            ->assertSee(route('challenges.show', $challenges->first()))
            ->assertSee(route('challenges.show', $challenges->last()));

        // Home zelf is verborgen als Leren open staat.
        $this->assertMatchesRegularExpression('/class="screen [^"]* hidden"\s+id="screen-home"/', $response->getContent());
    }

    public function test_leren_screen_is_hidden_on_normal_home(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('home'))
            ->assertOk()
            ->assertSee('motion-reduce:scroll-auto sm:px-6 hidden"', false);
    }

    public function test_home_has_wat_is_dit_button_to_the_current_challenge(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('home'));
        $challenge = Challenge::query()->findOrFail($user->fresh()->current_challenge_id);

        $this->get(route('home'))
            ->assertSeeInOrder(['Voltooien', 'Wat is dit?'])
            ->assertSee('href="'.route('challenges.show', $challenge).'"', false);
    }

    public function test_description_page_shows_details_and_back_link(): void
    {
        $challenge = Challenge::query()->firstOrFail();
        $challenge->forceFill(['description_extra' => 'Uitgebreide uitleg over deze challenge.'])->save();

        $this->actingAs(User::factory()->create())
            ->get(route('challenges.show', $challenge))
            ->assertOk()
            ->assertSee('Alle challenges')
            ->assertSee(route('home', ['screen' => 'leren', 'transition' => 'back']))
            ->assertSeeInOrder([$challenge->title, $challenge->difficulty, $challenge->kcal_burned.' kcal', $challenge->xp_reward.' XP', 'Over deze challenge', 'Uitgebreide uitleg over deze challenge.']);
    }

    public function test_challenges_no_longer_have_a_description_column(): void
    {
        $this->assertFalse(Schema::hasColumn('challenges', 'description'));
        $this->assertTrue(Schema::hasColumn('challenges', 'description_extra'));
    }

    public function test_coming_back_from_description_sets_back_transition_class(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('home', ['screen' => 'leren', 'transition' => 'back']))
            ->assertSee('learn-page-transition-back', false);

        $this->get(route('home', ['screen' => 'leren']))
            ->assertDontSee('class="learn-page-transition-back"', false);
    }

    public function test_unsplash_images_are_requested_in_the_right_size(): void
    {
        $challenge = new Challenge(['img' => 'https://images.unsplash.com/photo-123?w=2000&q=50']);

        $this->assertSame('https://images.unsplash.com/photo-123?w=320&q=80&auto=format&fit=crop', $challenge->imageUrl(320));
        $this->assertSame('https://images.unsplash.com/photo-123?w=2000&q=50', $challenge->imageUrl());
        $this->assertSame(asset('images/x.jpg'), (new Challenge(['img' => 'images/x.jpg']))->imageUrl(320));
    }

    public function test_unknown_challenge_gives_404(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/leren/999999')
            ->assertNotFound();
    }
}
