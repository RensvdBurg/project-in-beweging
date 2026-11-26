<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\User;
use App\Services\ChallengeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChallengeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_a_challenge_from_the_migration_data(): void
    {
        $user = User::factory()->create(['full_name' => 'Sanne']);

        $response = $this->actingAs($user)->get(route('home'))->assertOk();

        $challenge = Challenge::query()->findOrFail($user->fresh()->current_challenge_id);

        $response->assertSee('Hé, Sanne', false)
            ->assertSee($challenge->title)
            ->assertSee("{$challenge->xp_reward} XP te", false)
            ->assertSee('Je kunt vandaag nog 2 keer direct wisselen.');
    }

    public function test_completing_a_challenge_awards_xp_only_once_per_interval(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('home'));
        $challenge = Challenge::query()->findOrFail($user->fresh()->current_challenge_id);

        $this->post(route('challenges.complete'))
            ->assertRedirect(route('home'))
            ->assertSessionHas('challenge_completion_notice', "Opdracht voltooid! Je hebt {$challenge->xp_reward} XP verdiend.");

        $this->post(route('challenges.complete'))
            ->assertSessionHas('challenge_completion_notice', 'Je hebt deze opdracht al voltooid.');

        $this->assertSame($challenge->xp_reward, $user->fresh()->xp);
        $this->get(route('home'))->assertSee("Totaal: {$challenge->xp_reward} XP", false);
    }

    public function test_button_shows_lekker_bezig_after_completing_until_a_new_challenge(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('home'))
            ->assertSee('Voltooien')
            ->assertDontSee('Lekker bezig!');

        $this->post(route('challenges.complete'));

        // Direct na voltooien: knop + nette melding.
        $this->get(route('home'))
            ->assertSee('Lekker bezig!')
            ->assertSee('border-teal bg-teal-soft', false)
            ->assertSee('Opdracht voltooid!');

        // Na een andere pagina blijft de knop staan (de melding is eenmalig).
        $this->get(route('activities.index'))->assertOk();
        $this->get(route('home'))
            ->assertSee('Lekker bezig!')
            ->assertDontSee('Opdracht voltooid!');

        // Nieuw blok van 90 minuten = nieuwe opdracht: weer de gewone knop.
        $this->travel(ChallengeService::INTERVAL_SECONDS)->seconds();
        $this->get(route('home'))
            ->assertSee('Voltooien')
            ->assertDontSee('Lekker bezig!');
    }

    public function test_challenge_can_be_changed_twice_per_day(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('home'));
        $first = $user->fresh()->current_challenge_id;

        $this->post(route('challenges.next'))
            ->assertSessionHas('challenge_notice', 'Nieuwe opdracht geladen. Je kunt dit vandaag nog 1 keer doen.');
        $this->assertNotSame($first, $user->fresh()->current_challenge_id);

        $this->post(route('challenges.next'))
            ->assertSessionHas('challenge_notice', 'Nieuwe opdracht geladen. Je hebt vandaag geen directe opdrachtwissels meer.');

        $this->post(route('challenges.next'))
            ->assertSessionHas('challenge_notice', 'Je hebt je 2 directe opdrachtwissels voor vandaag al gebruikt.');
    }

    public function test_completion_fails_when_interval_has_passed(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('home'));

        $this->travel(ChallengeService::INTERVAL_SECONDS)->seconds();

        $this->post(route('challenges.complete'))
            ->assertSessionHas('challenge_completion_notice', 'Deze opdracht is niet meer actief. Ververs de pagina en probeer het opnieuw.');
        $this->assertSame(0, $user->fresh()->xp);
    }

    public function test_new_interval_picks_a_different_challenge(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('home'));
        $first = $user->fresh()->current_challenge_id;

        $this->travel(ChallengeService::INTERVAL_SECONDS)->seconds();
        $this->get(route('home'));

        $this->assertNotSame($first, $user->fresh()->current_challenge_id);
    }

    public function test_logging_out_and_in_does_not_reset_the_daily_changes(): void
    {
        $user = User::factory()->create(['username' => 'sanne']);
        $this->actingAs($user)->get(route('home'));

        $this->post(route('challenges.next'));
        $this->post(route('challenges.next'));

        $this->post(route('logout'));
        $this->post(route('login.store'), ['email' => 'sanne', 'password' => 'password']);

        $this->get(route('home'))
            ->assertSee('Je kunt vandaag nog 0 keer direct wisselen.')
            ->assertSee('id="btn-play"', false);

        $this->post(route('challenges.next'))
            ->assertSessionHas('challenge_notice', 'Je hebt je 2 directe opdrachtwissels voor vandaag al gebruikt.');
    }

    public function test_logging_out_and_in_keeps_the_same_challenge(): void
    {
        $user = User::factory()->create(['username' => 'sanne']);
        $this->actingAs($user)->get(route('home'));
        $challengeId = $user->fresh()->current_challenge_id;

        $this->post(route('logout'));
        $this->post(route('login.store'), ['email' => 'sanne', 'password' => 'password']);
        $this->get(route('home'));

        $this->assertSame($challengeId, $user->fresh()->current_challenge_id);
    }

    public function test_daily_changes_come_back_the_next_day(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('home'));

        $this->post(route('challenges.next'));
        $this->post(route('challenges.next'));
        $this->get(route('home'))->assertSee('Je kunt vandaag nog 0 keer direct wisselen.');

        $this->travelTo(now()->addDay()->startOfDay()->addHours(9));

        $this->get(route('home'))->assertSee('Je kunt vandaag nog 2 keer direct wisselen.');
    }
}
