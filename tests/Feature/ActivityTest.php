<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Activity::IMAGE_DISK);
    }

    public function test_home_links_to_activities_instead_of_logout(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('home'))
            ->assertSee(route('activities.index'), false)
            ->assertSee('Activiteiten')
            ->assertDontSee('Uitloggen');
    }

    public function test_activities_page_shows_form_and_empty_state(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('activities.index'))
            ->assertOk()
            ->assertSeeInOrder(['Deel wat je hebt gedaan, van een hardlooprondje tot een nieuwe sportieve mijlpaal.', 'Activiteit toevoegen'])
            ->assertSee('type="file"', false)
            ->assertSee('Nog geen activiteiten');
    }

    public function test_user_can_add_activity_with_image(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('activities.store'), [
            'title' => 'Rondje fietsen',
            'description' => '10 km door het park.',
            'image' => UploadedFile::fake()->image('fiets.jpg', 800, 600),
        ])->assertRedirect(route('activities.index'))
            ->assertSessionHas('status_message', 'Je activiteit is toegevoegd.');

        $activity = $user->activities()->sole();
        $this->assertSame('Rondje fietsen', $activity->title);
        Storage::disk(Activity::IMAGE_DISK)->assertExists($activity->image_path);

        $this->get(URL::signedRoute('activities.image', $activity))->assertOk();
        $this->get(route('activities.index'))->assertSee('Rondje fietsen')->assertSee('10 km door het park.');
    }

    public function test_image_is_optional_and_title_and_description_are_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('activities.store'), ['title' => 'Wandelen', 'description' => 'Rondje.'])
            ->assertSessionHas('status_type', 'success');
        $this->assertNull($user->activities()->sole()->image_path);

        $this->post(route('activities.store'), ['title' => '', 'description' => 'x'])
            ->assertSessionHas('status_message', 'Vul een titel in.');
        $this->post(route('activities.store'), ['title' => 'x', 'description' => ''])
            ->assertSessionHas('status_message', 'Vul een beschrijving in.');
        $this->post(route('activities.store'), [
            'title' => 'x',
            'description' => 'y',
            'image' => UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf'),
        ])->assertSessionHas('status_message', 'Kies een afbeelding (JPG, PNG, WebP, GIF of HEIC).');

        $this->assertSame(1, $user->activities()->count());
    }

    public function test_native_image_path_is_rejected_outside_the_app(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('activities.store'), [
            'title' => 'x',
            'description' => 'y',
            'native_image_path' => base_path('.env'),
        ])->assertSessionHas('status_type', 'error');

        $this->assertSame(0, $user->activities()->count());
    }

    public function test_overview_shows_only_latest_five_and_link_appears_after_five(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $i) {
            Activity::factory()->for($user)->create(['title' => "Activiteit {$i}", 'created_at' => now()->addMinutes($i)]);
        }

        $this->actingAs($user)->get(route('activities.index'))
            ->assertDontSee('Alle activiteiten bekijken');
        $this->get(route('activities.all'))->assertRedirect(route('activities.index'));

        Activity::factory()->for($user)->create(['title' => 'Activiteit 6', 'created_at' => now()->addMinutes(6)]);

        $this->get(route('activities.index'))
            ->assertSee('Alle activiteiten bekijken')
            ->assertSee('Activiteit 6')
            ->assertDontSee('Activiteit 1<', false);
    }

    public function test_detail_page_is_paginated_with_five_per_page(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 12) as $i) {
            Activity::factory()->for($user)->create(['title' => "Activiteit {$i}", 'created_at' => now()->addMinutes($i)]);
        }

        $this->actingAs($user)->get(route('activities.all'))
            ->assertOk()
            ->assertSee('Pagina 1 van 3')
            ->assertSeeInOrder(['Activiteit 12', 'Activiteit 8'])
            ->assertDontSee('Activiteit 7<', false);

        $this->get(route('activities.all', ['page' => 3]))
            ->assertSee('Pagina 3 van 3')
            ->assertSee('Activiteit 2')
            ->assertSee('Activiteit 1<', false);
    }

    public function test_own_activities_page_only_lists_own_activities_but_images_are_shared_with_logged_in_users(): void
    {
        $owner = User::factory()->create();
        $activity = Activity::factory()->for($owner)->create(['title' => 'Van een ander', 'image_path' => 'activities/1/x.jpg']);
        Storage::disk(Activity::IMAGE_DISK)->put('activities/1/x.jpg', 'img');

        // Zonder geldige handtekening geen afbeelding.
        $this->get(route('activities.image', $activity))->assertForbidden();

        $this->actingAs(User::factory()->create())
            ->get(route('activities.index'))->assertDontSee('Van een ander');

        // Met de ondertekende URL wel (community-overzicht op home), zonder sessie.
        $this->get(URL::signedRoute('activities.image', $activity))
            ->assertOk()
            ->assertHeaderMissing('Set-Cookie');
    }

    public function test_deleting_account_removes_activity_images(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('activities.store'), [
            'title' => 'x',
            'description' => 'y',
            'image' => UploadedFile::fake()->image('a.png'),
        ]);
        $path = $user->activities()->sole()->image_path;

        $this->delete(route('account.destroy'), ['confirm_delete' => '1']);

        Storage::disk(Activity::IMAGE_DISK)->assertMissing($path);
        $this->assertSame(0, Activity::query()->count());
    }
}
