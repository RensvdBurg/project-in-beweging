<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_ranglijst_screen_is_hidden_on_home_and_shown_with_screen_parameter(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertSee('id="screen-ranglijst"', false)
            ->assertSee('class="screen mx-auto min-h-dvh w-full max-w-lg overflow-y-auto px-4 pb-28 pt-[max(1rem,env(safe-area-inset-top))] sm:px-6 hidden"', false);

        $this->get(route('home', ['screen' => 'ranglijst']))
            ->assertOk()
            ->assertSee('class="screen mx-auto min-h-dvh w-full max-w-lg overflow-y-auto px-4 pb-28 pt-[max(1rem,env(safe-area-inset-top))] sm:px-6"', false)
            ->assertSee('Bekijk wie de meeste XP heeft verdiend.');
    }

    public function test_podium_shows_second_first_third_and_list_shows_top_ten(): void
    {
        foreach (range(1, 12) as $i) {
            User::factory()->create(['full_name' => "Speler {$i}", 'username' => "speler{$i}", 'xp' => 1000 - $i * 10]);
        }

        $this->actingAs(User::factory()->create(['full_name' => 'Ikke Zelf', 'username' => 'ikke', 'xp' => 5]))
            ->get(route('home', ['screen' => 'ranglijst']))
            ->assertSeeInOrder(['#2', 'Speler 2', '#1', 'Speler 1', '#3', 'Speler 3', 'Top 10', 'Speler 4', 'Speler 10'])
            ->assertDontSee('Speler 11')
            ->assertSee('Je staat buiten de top 10')
            ->assertSee('#13');
    }

    public function test_current_user_in_top_ten_is_marked_and_has_no_separate_rank_card(): void
    {
        foreach (range(1, 4) as $i) {
            User::factory()->create(['xp' => 100 * $i]);
        }

        $this->actingAs(User::factory()->create(['full_name' => 'Ikke Zelf', 'xp' => 0]))
            ->get(route('home', ['screen' => 'ranglijst']))
            ->assertSee('(jij)')
            ->assertDontSee('Je staat buiten de top 10');
    }

    public function test_equal_xp_is_ordered_by_username_case_insensitive_then_id(): void
    {
        $bram = User::factory()->create(['username' => 'bram', 'xp' => 50]);
        $anna = User::factory()->create(['username' => 'Anna', 'xp' => 50]);
        $carl = User::factory()->create(['username' => 'carl', 'xp' => 50]);

        $this->assertSame(
            [$anna->id, $bram->id, $carl->id],
            User::query()->leaderboard()->pluck('id')->all(),
        );
        $this->assertSame(1, $anna->leaderboardRank());
        $this->assertSame(2, $bram->leaderboardRank());
        $this->assertSame(3, $carl->leaderboardRank());
    }

    public function test_profile_shows_xp_rank_about_me_and_activities(): void
    {
        $sanne = User::factory()->create(['full_name' => 'Sanne de Vries', 'username' => 'sanne', 'xp' => 300]);
        $sanne->forceFill(['about_me' => 'Ik fiets graag.'])->save();
        User::factory()->create(['xp' => 500]);
        Activity::factory()->for($sanne)->create(['title' => '5 km hardlopen']);

        $this->actingAs(User::factory()->create())
            ->get(route('profile.show', $sanne))
            ->assertOk()
            ->assertSee('Terug naar ranglijst')
            ->assertSeeInOrder(['Sanne de Vries', '@sanne', 'Over mij', 'Ik fiets graag.', 'Totaal XP', '300', 'Rang', '#2', '5 km hardlopen']);
    }

    public function test_profile_from_home_links_back_to_activities(): void
    {
        $sanne = User::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('profile.show', ['user' => $sanne, 'from' => 'home']))
            ->assertSee('Terug naar activiteiten')
            ->assertSee('heeft nog geen activiteiten gedeeld.');
    }

    public function test_profile_paginates_activities_per_five(): void
    {
        $sanne = User::factory()->create();
        foreach (range(1, 6) as $i) {
            Activity::factory()->for($sanne)->create(['title' => "Activiteit {$i}", 'created_at' => now()->subMinutes(10 - $i)]);
        }

        $this->actingAs(User::factory()->create());

        $this->get(route('profile.show', $sanne))
            ->assertSee('Activiteit 6')
            ->assertDontSee('Activiteit 1<', false)
            ->assertSee('activity_page=2', false);

        $this->get(route('profile.show', ['user' => $sanne, 'activity_page' => 2]))
            ->assertSee('Activiteit 1');

        $this->get(route('profile.show', ['user' => $sanne, 'activity_page' => 9]))
            ->assertRedirect(route('profile.show', ['user' => $sanne, 'from' => 'ranglijst', 'activity_page' => 2]));
    }

    public function test_unknown_profile_gives_404_and_guests_are_redirected(): void
    {
        $this->get(route('profile.show', 1))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get('/profiel/9999')
            ->assertNotFound();
    }
}
