<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunityTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_activities_of_everyone_below_the_next_challenge_card(): void
    {
        $sanne = User::factory()->create(['username' => 'sanne']);
        Activity::factory()->for($sanne)->create(['title' => '5 km hardlopen', 'description' => 'Ochtendrondje van 5 km.']);

        $this->actingAs(User::factory()->create())
            ->get(route('home'))
            ->assertOk()
            ->assertSeeInOrder([
                'Direct de volgende opdracht starten?',
                'Samen in beweging',
                'Activiteiten van iedereen',
                'Bekijk wat de UpMove-community allemaal heeft gedaan.',
                'sanne',
                '5 km hardlopen',
                'Ochtendrondje van 5 km.',
            ])
            ->assertDontSee(route('community.index'));
    }

    public function test_home_shows_latest_five_and_links_to_community_page_after_five(): void
    {
        $user = User::factory()->create();
        foreach (range(1, 6) as $i) {
            Activity::factory()->for($user)->create(['description' => "Beschrijving {$i}", 'created_at' => now()->subMinutes(10 - $i)]);
        }

        $this->actingAs($user)->get(route('home'))
            ->assertSee('Beschrijving 6</p>', false)
            ->assertSee('Beschrijving 2</p>', false)
            ->assertDontSee('Beschrijving 1</p>', false)
            ->assertSee(route('community.index'));

        $this->get(route('community.index'))
            ->assertOk()
            ->assertSee('Pagina 1 van 2');

        $this->get(route('community.index', ['page' => 2]))
            ->assertSee('Beschrijving 1</p>', false);
    }

    public function test_community_page_redirects_home_with_five_or_fewer_activities(): void
    {
        Activity::factory()->count(5)->create();

        $this->actingAs(User::factory()->create())
            ->get(route('community.index'))
            ->assertRedirect(route('home'));
    }

    public function test_like_button_toggles_a_like(): void
    {
        $activity = Activity::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->from(route('home'))
            ->post(route('activities.like', $activity))
            ->assertRedirect(route('home').'#activiteit-'.$activity->id);
        $this->assertDatabaseHas('activity_likes', ['activity_id' => $activity->id, 'user_id' => $user->id]);

        $this->get(route('home'))->assertSee('aria-pressed="true"', false);

        $this->post(route('activities.like', $activity));
        $this->assertDatabaseMissing('activity_likes', ['activity_id' => $activity->id, 'user_id' => $user->id]);
    }

    public function test_user_can_comment_and_comment_is_shown_with_username(): void
    {
        $activity = Activity::factory()->create();
        $user = User::factory()->create(['username' => 'reageerder']);

        $this->actingAs($user)->from(route('home'))
            ->post(route('activities.comment', $activity), ['body' => '  Goed bezig!  '])
            ->assertRedirect(route('home').'#activiteit-'.$activity->id);

        $this->assertDatabaseHas('activity_comments', ['activity_id' => $activity->id, 'user_id' => $user->id, 'body' => 'Goed bezig!']);

        $this->get(route('home'))->assertSeeInOrder(['reageerder', 'Goed bezig!']);
    }

    public function test_empty_comment_is_rejected(): void
    {
        $activity = Activity::factory()->create();

        $this->actingAs(User::factory()->create())->from(route('home'))
            ->post(route('activities.comment', $activity), ['body' => '   '])
            ->assertSessionHas('comment_error', 'Schrijf eerst een reactie.');

        $this->assertDatabaseCount('activity_comments', 0);
    }

    public function test_user_can_delete_own_comment_but_not_someone_elses(): void
    {
        $activity = Activity::factory()->create();
        $writer = User::factory()->create();
        $other = User::factory()->create();
        $comment = $activity->comments()->create(['user_id' => $writer->id, 'body' => 'Mijn reactie']);

        // Alleen de schrijver ziet het kruisje.
        $this->actingAs($other)->get(route('home'))->assertSee('Mijn reactie')->assertDontSee('Reactie verwijderen');
        $this->delete(route('comments.destroy', $comment))->assertForbidden();
        $this->assertDatabaseHas('activity_comments', ['id' => $comment->id]);

        $this->actingAs($writer)->get(route('home'))->assertSee('Reactie verwijderen');
        $this->from(route('home'))->delete(route('comments.destroy', $comment))
            ->assertRedirect(route('home').'#activiteit-'.$activity->id);
        $this->assertDatabaseMissing('activity_comments', ['id' => $comment->id]);
    }

    public function test_every_user_sees_the_same_like_and_comment_counts(): void
    {
        $activity = Activity::factory()->create();
        $users = User::factory()->count(3)->create();

        foreach ($users as $user) {
            $this->actingAs($user)->post(route('activities.like', $activity));
            $this->post(route('activities.comment', $activity), ['body' => "Reactie van {$user->username}"]);
        }

        foreach ([...$users, User::factory()->create()] as $viewer) {
            $this->actingAs($viewer)->get(route('home'))
                ->assertSee('aria-label="Reacties (3)"', false)
                ->assertSee('<span>3</span>', false);
        }
    }

    public function test_guests_cannot_like_or_comment(): void
    {
        $activity = Activity::factory()->create();

        $this->post(route('activities.like', $activity))->assertRedirect(route('login'));
        $this->post(route('activities.comment', $activity), ['body' => 'Hoi'])->assertRedirect(route('login'));
    }

    public function test_like_without_reload_returns_updated_card(): void
    {
        $activity = Activity::factory()->create();
        $user = User::factory()->create();

        $html = $this->actingAs($user)
            ->postJson(route('activities.like', $activity))
            ->assertOk()
            ->assertJsonPath('error', '')
            ->json('html');

        $this->assertTrue($activity->likes()->whereKey($user->id)->exists());
        $this->assertStringContainsString('id="activiteit-'.$activity->id.'"', $html);
        $this->assertStringContainsString('aria-pressed="true"', $html);
        $this->assertMatchesRegularExpression('/id="reacties-'.$activity->id.'" class="mt-3"\s+hidden/', $html);
    }

    public function test_comment_without_reload_returns_card_with_open_panel(): void
    {
        $activity = Activity::factory()->create();

        $html = $this->actingAs(User::factory()->create())
            ->postJson(route('activities.comment', $activity), ['body' => 'Goed bezig!'])
            ->assertOk()
            ->json('html');

        $this->assertStringContainsString('Goed bezig!', $html);
        $this->assertDoesNotMatchRegularExpression('/id="reacties-'.$activity->id.'" class="mt-3"\s+hidden/', $html);

        $this->postJson(route('activities.comment', $activity), ['body' => '   '])
            ->assertStatus(422)
            ->assertJsonPath('error', 'Schrijf eerst een reactie.');
    }

    public function test_delete_comment_without_reload_returns_updated_card(): void
    {
        $activity = Activity::factory()->create();
        $user = User::factory()->create();
        $comment = $activity->comments()->create(['user_id' => $user->id, 'body' => 'Weg ermee']);

        $html = $this->actingAs($user)
            ->deleteJson(route('comments.destroy', $comment))
            ->assertOk()
            ->json('html');

        $this->assertStringNotContainsString('Weg ermee', $html);
        $this->assertModelMissing($comment);
    }
}
