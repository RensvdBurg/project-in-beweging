<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\UserSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RemoveUnusedSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_removes_reminders_quiet_hours_and_sound_from_saved_settings(): void
    {
        $user = User::factory()->create();
        DB::table('users')->where('id', $user->id)->update([
            'settings' => json_encode(['frequency' => 30, 'reminders' => true, 'quietHours' => false, 'sound' => true, 'darkMode' => true]),
        ]);
        $withoutSettings = User::factory()->create(['settings' => null]);

        $migration = require database_path('migrations/2026_11_20_090000_remove_unused_settings_from_users.php');
        $migration->up();

        $this->assertSame(['frequency' => 30, 'darkMode' => true], $user->fresh()->settings);
        $this->assertNull($withoutSettings->fresh()->settings);
    }

    public function test_settings_no_longer_contain_the_removed_options(): void
    {
        foreach (['reminders', 'quietHours', 'sound'] as $name) {
            $this->assertArrayNotHasKey($name, UserSettings::DEFAULTS);
        }

        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson(route('settings.update'), UserSettings::DEFAULTS + ['sound' => true])
            ->assertOk()
            ->assertJsonMissingPath('settings.sound');

        $this->assertArrayNotHasKey('sound', $user->fresh()->settings);
    }
}
