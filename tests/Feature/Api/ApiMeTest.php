<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiMeTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_returns_private_fields_and_rank(): void
    {
        $user = User::factory()->create(['username' => 'sanne', 'xp' => 50]);
        User::factory()->create(['xp' => 100]);
        Sanctum::actingAs($user);

        $this->getJson(route('api.v1.me'))
            ->assertOk()
            ->assertJsonPath('user.username', 'sanne')
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('rank', 2);
    }

    public function test_profile_and_about_can_be_updated(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson(route('api.v1.me.profile'), ['full_name' => 'Nieuwe Naam', 'username' => 'nieuw', 'email' => 'nieuw@example.com'])
            ->assertOk()
            ->assertJsonPath('user.username', 'nieuw');
        $this->putJson(route('api.v1.me.about'), ['about_me' => 'Ik fiets graag.'])->assertOk();

        $this->assertSame('Ik fiets graag.', $user->fresh()->about_me);
    }

    public function test_username_must_be_unique_ignoring_case(): void
    {
        User::factory()->create(['username' => 'bram']);
        Sanctum::actingAs(User::factory()->create());

        $this->putJson(route('api.v1.me.profile'), ['full_name' => 'X', 'username' => 'BRAM', 'email' => 'x@example.com'])
            ->assertJsonValidationErrors('username');
    }

    public function test_password_change_logs_out_other_phones(): void
    {
        $user = User::factory()->create();
        $current = $user->createToken('deze')->plainTextToken;
        $user->createToken('andere');

        $this->withToken($current)->putJson(route('api.v1.me.password'), [
            'current_password' => 'password', 'new_password' => 'nieuwgeheim', 'new_password_confirmation' => 'nieuwgeheim',
        ])->assertOk();

        $this->assertTrue(Hash::check('nieuwgeheim', $user->fresh()->password));
        $this->assertSame(['deze'], $user->tokens()->pluck('name')->all());
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->putJson(route('api.v1.me.password'), ['current_password' => 'fout', 'new_password' => 'nieuwgeheim', 'new_password_confirmation' => 'nieuwgeheim'])
            ->assertJsonValidationErrors('current_password');
    }

    public function test_settings_are_saved(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson(route('api.v1.me.settings'), [
            'frequency' => 60, 'dailyGoal' => 5, 'darkMode' => true, 'workOnly' => false,
            'workStart' => '09:00', 'workEnd' => '17:00', 'workDays' => [1, 2, 3, 4, 5],
        ])->assertOk()->assertJsonPath('settings.darkMode', true);

        $this->assertTrue($user->fresh()->preferences()['darkMode']);
    }

    public function test_account_deletion_requires_confirmation_and_removes_tokens(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('app')->plainTextToken;

        $this->withToken($token)->deleteJson(route('api.v1.me.destroy'))->assertJsonValidationErrors('confirm_delete');
        $this->withToken($token)->deleteJson(route('api.v1.me.destroy'), ['confirm_delete' => true])->assertOk();

        $this->assertModelMissing($user);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
