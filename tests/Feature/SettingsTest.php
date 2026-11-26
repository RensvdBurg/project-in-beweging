<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\UserSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge(UserSettings::DEFAULTS, $overrides);
    }

    public function test_settings_are_saved_per_user_in_the_database(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)
            ->putJson(route('settings.update'), $this->payload(['frequency' => 30, 'darkMode' => true, 'workDays' => [1, 3]]))
            ->assertOk()
            ->assertJsonPath('settings.frequency', 30);

        $this->assertSame(30, $user->fresh()->preferences()['frequency']);
        $this->assertTrue($user->fresh()->preferences()['darkMode']);
        $this->assertSame([1, 3], $user->fresh()->preferences()['workDays']);
        $this->assertSame(45, $other->fresh()->preferences()['frequency'], 'andere gebruiker houdt de standaard');
    }

    public function test_settings_page_loads_saved_settings_from_the_database(): void
    {
        $user = User::factory()->create(['settings' => $this->payload(['frequency' => 90])]);

        $this->actingAs($user)->get(route('settings'))
            ->assertOk()
            ->assertSee('data-settings=', false)
            ->assertSee('"frequency":90', false)
            ->assertSee(route('settings.update'), false);
    }

    public function test_invalid_settings_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson(route('settings.update'), $this->payload(['frequency' => 50, 'workDays' => [], 'workStart' => '25:00']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['frequency', 'workDays', 'workStart']);

        $this->assertNull($user->fresh()->settings);
    }

    public function test_home_alert_uses_the_reminder_frequency(): void
    {
        $user = User::factory()->create(['settings' => $this->payload(['frequency' => 30])]);

        $this->actingAs($user)->get(route('home'))
            ->assertSee('Te lang gezeten! (30 min)')
            ->assertSee('data-frequency="30"', false)
            ->assertSee('Melding sluiten voor 30 minuten');
    }

    public function test_home_alert_defaults_to_45_minutes_without_settings(): void
    {
        $this->actingAs(User::factory()->create())->get(route('home'))
            ->assertSee('Te lang gezeten! (45 min)')
            ->assertSee('data-frequency="45"', false);
    }

    public function test_dark_mode_from_the_database_is_applied_on_every_page(): void
    {
        $user = User::factory()->create(['settings' => $this->payload(['darkMode' => true])]);

        $this->actingAs($user)->get(route('activities.index'))
            ->assertSee('<html lang="nl"', false)
            ->assertSee('class="upmove-dark"', false)
            ->assertSee('data-dark-mode="true"', false);
    }

    public function test_without_saved_settings_local_settings_stay_in_charge(): void
    {
        $user = User::factory()->create(['settings' => null]);

        // Geen data-settings: de pagina neemt localStorage over en slaat dat op.
        $this->actingAs($user)->get(route('settings'))
            ->assertOk()
            ->assertDontSee('data-settings=', false);

        // Geen dark mode van de server die localStorage zou overschrijven.
        $this->get(route('home'))
            ->assertDontSee('data-dark-mode=', false)
            ->assertDontSee('class="upmove-dark"', false);
    }

    public function test_guests_cannot_save_settings(): void
    {
        $this->putJson(route('settings.update'), $this->payload())->assertUnauthorized();
    }

    public function test_settings_page_has_start_and_end_time_per_day(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('settings'))
            ->assertOk()
            ->assertSee('Werk- / schooldagen en tijden')
            ->assertSeeInOrder(['Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za', 'Zo'])
            ->assertSee('id="setting-work-start-1"', false)
            ->assertSee('id="setting-work-end-0"', false)
            ->assertSee('Starttijd woensdag');
    }

    public function test_work_schedule_per_day_is_saved(): void
    {
        $user = User::factory()->create();
        $schedule = UserSettings::DEFAULTS['workSchedule'];
        $schedule[3] = ['start' => '08:30', 'end' => '12:30'];
        $schedule[5] = ['start' => '22:00', 'end' => '06:00'];

        $this->actingAs($user)
            ->putJson(route('settings.update'), $this->payload(['workOnly' => true, 'workDays' => [1, 3, 5], 'workSchedule' => $schedule]))
            ->assertOk()
            ->assertJsonPath('settings.workSchedule.3.end', '12:30')
            ->assertJsonPath('settings.workSchedule.5.start', '22:00');

        $this->assertSame($schedule, $user->fresh()->preferences()['workSchedule']);

        // De layout geeft de tijden per dag door aan public/js/app-settings.js.
        $this->get(route('home'))->assertSee('"schedule":[', false);
    }

    public function test_invalid_time_in_schedule_is_rejected(): void
    {
        $user = User::factory()->create();
        $schedule = UserSettings::DEFAULTS['workSchedule'];
        $schedule[2] = ['start' => '25:00', 'end' => '17:00'];

        $this->actingAs($user)
            ->putJson(route('settings.update'), $this->payload(['workSchedule' => $schedule]))
            ->assertUnprocessable();

        $this->putJson(route('settings.update'), $this->payload(['workSchedule' => array_slice($schedule, 0, 6)]))
            ->assertUnprocessable();

        $this->assertNull($user->fresh()->settings);
    }

    public function test_older_settings_without_schedule_use_general_times_for_every_day(): void
    {
        $user = User::factory()->create(['settings' => ['workOnly' => true, 'workStart' => '09:00', 'workEnd' => '15:00', 'workDays' => [1, 2]]]);

        $schedule = $user->preferences()['workSchedule'];

        $this->assertCount(7, $schedule);
        $this->assertSame(['start' => '09:00', 'end' => '15:00'], $schedule[0]);
        $this->assertSame(['start' => '09:00', 'end' => '15:00'], $schedule[6]);
    }
}
