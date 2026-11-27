<?php

namespace Tests\Feature\Admin;

use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminActivityAndLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_activities_of_all_users_and_can_search(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $sanne = User::factory()->create(['username' => 'sanne']);
        $piet = User::factory()->create(['username' => 'piet']);
        $sanne->activities()->create(['title' => 'Fietsen', 'description' => 'Naar school']);
        $piet->activities()->create(['title' => 'Zwemmen', 'description' => 'Baantjes']);

        $this->get(route('admin.activities.index'))->assertSee('Fietsen')->assertSee('Zwemmen');
        $this->get(route('admin.activities.index', ['q' => 'piet']))->assertSee('Zwemmen')->assertDontSee('Fietsen');
    }

    public function test_admin_can_delete_an_activity_with_its_image(): void
    {
        Storage::fake(Activity::IMAGE_DISK);
        $this->actingAs(User::factory()->admin()->create());
        $owner = User::factory()->create();
        $path = UploadedFile::fake()->image('foto.jpg')->store("activities/{$owner->id}", Activity::IMAGE_DISK);
        $activity = $owner->activities()->create(['title' => 'Hardlopen', 'description' => '5 km', 'image_path' => $path]);

        $this->from(route('admin.activities.index'))
            ->delete(route('admin.activities.destroy', $activity))
            ->assertRedirect(route('admin.activities.index'));

        $this->assertModelMissing($activity);
        Storage::disk(Activity::IMAGE_DISK)->assertMissing($path);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin_activity_deleted']);
    }

    public function test_normal_users_cannot_delete_activities_via_admin(): void
    {
        $owner = User::factory()->create();
        $activity = $owner->activities()->create(['title' => 'Hardlopen', 'description' => '5 km']);

        $this->actingAs(User::factory()->create())
            ->delete(route('admin.activities.destroy', $activity))
            ->assertForbidden();

        $this->assertModelExists($activity);
    }

    public function test_app_events_are_logged(): void
    {
        $user = User::factory()->create(['username' => 'sanne', 'email' => 'sanne@example.com']);

        $this->post(route('login.store'), ['email' => 'sanne', 'password' => 'fout-wachtwoord']);
        $this->post(route('login.store'), ['email' => 'sanne', 'password' => 'password']);
        $this->post(route('activities.store'), ['title' => 'Wandelen', 'description' => 'Rondje park']);
        $this->post(route('logout'));

        $this->assertSame(
            ['login_failed', 'login', 'activity_created', 'logout'],
            AuditLog::query()->orderBy('id')->pluck('action')->all(),
        );
        $this->assertSame($user->id, AuditLog::query()->where('action', 'login')->value('user_id'));
    }

    public function test_logs_page_filters_by_action_and_search(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        AuditLog::record('login', 'sanne is ingelogd.');
        AuditLog::record('login_failed', 'Mislukte inlogpoging voor "piet".');

        $this->get(route('admin.logs.index'))->assertSee('sanne is ingelogd.')->assertSee('Mislukte inlogpoging');
        $this->get(route('admin.logs.index', ['action' => 'login_failed']))->assertSee('Mislukte inlogpoging')->assertDontSee('sanne is ingelogd.');
        $this->get(route('admin.logs.index', ['q' => 'SANNE']))->assertSee('sanne is ingelogd.')->assertDontSee('Mislukte inlogpoging');
    }

    public function test_logs_keep_existing_after_user_is_deleted(): void
    {
        $user = User::factory()->create();
        AuditLog::record('login', 'iemand is ingelogd.', $user);

        $user->deleteAccount();

        $this->assertDatabaseHas('audit_logs', ['description' => 'iemand is ingelogd.', 'user_id' => null]);
    }
}
