<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_normal_users_get_403_on_every_admin_page(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (['admin.dashboard', 'admin.users.index', 'admin.challenges.index', 'admin.activities.index', 'admin.logs.index'] as $route) {
            $this->get(route($route))->assertForbidden();
        }
    }

    public function test_admins_can_open_every_admin_page(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        foreach (['admin.dashboard', 'admin.users.index', 'admin.users.create', 'admin.challenges.index', 'admin.challenges.create', 'admin.activities.index', 'admin.logs.index'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('Admin dashboard');
        }
    }

    public function test_settings_page_links_to_dashboard_only_for_admins(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('settings'))
            ->assertDontSee(route('admin.dashboard'));

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('settings'))
            ->assertSee(route('admin.dashboard'));
    }

    public function test_is_admin_cannot_be_mass_assigned_via_registration_or_profile(): void
    {
        $this->post(route('register'), [
            'email' => 'nieuw@example.com', 'username' => 'nieuw', 'fullName' => 'Nieuw', 'password' => 'geheim123',
            'confirmPassword' => 'geheim123', 'day' => 1, 'month' => 1, 'year' => 2000, 'is_admin' => 1,
        ]);

        $this->assertFalse(User::query()->where('username', 'nieuw')->firstOrFail()->is_admin);
    }

    public function test_make_admin_command_grants_and_removes_rights(): void
    {
        $user = User::factory()->create(['username' => 'sanne']);

        $this->artisan('upmove:admin', ['login' => 'Sanne'])->assertSuccessful();
        $this->assertTrue($user->fresh()->is_admin);

        $this->artisan('upmove:admin', ['login' => 'sanne', '--remove' => true])->assertSuccessful();
        $this->assertFalse($user->fresh()->is_admin);

        $this->artisan('upmove:admin', ['login' => 'bestaatniet'])->assertFailed();
    }
}
