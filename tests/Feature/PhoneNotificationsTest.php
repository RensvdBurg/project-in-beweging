<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\UserSettings;
use Ikromjon\LocalNotifications\Contracts\LocalNotificationsInterface;
use Ikromjon\LocalNotifications\Data\NotificationOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Nep-plugin: onthoudt wat er gepland en gewist is in plaats van de telefoon aan te roepen.
 */
class FakeLocalNotifications implements LocalNotificationsInterface
{
    /** @var array<string, array<string, mixed>> */
    public array $scheduled = [];

    public int $cancelAllCalls = 0;

    public int $permissionRequests = 0;

    public string $permission = 'granted';

    public function schedule(NotificationOptions|array $options): array
    {
        $options = $options instanceof NotificationOptions ? $options->toArray() : $options;
        $this->scheduled[$options['id']] = $options;

        return ['success' => true, 'id' => $options['id']];
    }

    public function cancel(string $id): array
    {
        unset($this->scheduled[$id]);

        return ['success' => true, 'id' => $id];
    }

    public function cancelAll(): array
    {
        $this->cancelAllCalls++;
        $this->scheduled = [];

        return ['success' => true];
    }

    public function getPending(): array
    {
        return ['success' => true, 'notifications' => json_encode(array_values($this->scheduled)), 'count' => count($this->scheduled)];
    }

    public function requestPermission(bool $critical = false): array
    {
        $this->permissionRequests++;

        return $this->permission === 'granted' ? ['granted' => true] : ['granted' => false, 'status' => 'denied'];
    }

    public function checkPermission(): array
    {
        return ['status' => $this->permission];
    }

    public function update(string $id, NotificationOptions|array $options): array
    {
        return ['success' => true, 'id' => $id];
    }
}

class PhoneNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private FakeLocalNotifications $phone;

    protected function setUp(): void
    {
        parent::setUp();

        // Doen alsof we in de app op een telefoon draaien.
        config(['upmove.phone_notifications' => true]);
        $this->phone = new FakeLocalNotifications;
        $this->app->instance(LocalNotificationsInterface::class, $this->phone);

        // Maandag 09:00: nog een hele werkdag te gaan.
        $this->travelTo(now()->setTimezone('Europe/Amsterdam')->next('Monday')->setTime(9, 0));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function settings(array $overrides = []): array
    {
        return array_merge(UserSettings::DEFAULTS, $overrides);
    }

    public function test_turning_on_work_only_schedules_reminders_and_new_challenges(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson(route('settings.update'), $this->settings(['workOnly' => true, 'frequency' => 60]))
            ->assertOk();

        $kinds = array_count_values(array_map(fn (array $n): string => $n['data']['upmove_kind'], $this->phone->scheduled));

        $this->assertGreaterThan(0, $kinds['reminder'] ?? 0);
        $this->assertGreaterThan(0, $kinds['challenge'] ?? 0);
        $this->assertLessThanOrEqual(60, count($this->phone->scheduled));

        $first = collect($this->phone->scheduled)->sortBy('at')->first();
        $this->assertGreaterThan(now()->getTimestamp(), $first['at']);
        $this->assertContains($first['title'], ['Te lang gezeten!', 'Nieuwe opdracht beschikbaar!']);
    }

    public function test_turning_off_work_only_removes_all_notifications(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->putJson(route('settings.update'), $this->settings(['workOnly' => true]));
        $this->assertNotEmpty($this->phone->scheduled);

        $this->putJson(route('settings.update'), $this->settings(['workOnly' => false]))->assertOk();

        $this->assertSame([], $this->phone->scheduled);
    }

    public function test_changing_frequency_replans(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->putJson(route('settings.update'), $this->settings(['workOnly' => true, 'frequency' => 120]));
        $fewer = count($this->phone->scheduled);

        $this->putJson(route('settings.update'), $this->settings(['workOnly' => true, 'frequency' => 30]));

        $this->assertGreaterThan($fewer, count($this->phone->scheduled));
    }

    public function test_opening_home_does_not_replan_when_nothing_changed(): void
    {
        $user = User::factory()->create(['settings' => $this->settings(['workOnly' => true])]);
        $this->actingAs($user);

        $this->get(route('home'))->assertOk();
        $this->assertSame(1, $this->phone->cancelAllCalls);

        $this->get(route('home'))->assertOk();
        $this->assertSame(1, $this->phone->cancelAllCalls, 'tweede keer home: niets opnieuw gepland');

        // Na een paar dagen raakt het plan op: dan wordt het aangevuld.
        $this->travel(6)->days();
        $this->get(route('home'))->assertOk();
        $this->assertSame(2, $this->phone->cancelAllCalls);
    }

    public function test_permission_is_requested_when_missing_and_nothing_is_planned_when_denied(): void
    {
        $this->phone->permission = 'denied';
        $user = User::factory()->create();

        $this->actingAs($user)->putJson(route('settings.update'), $this->settings(['workOnly' => true]))->assertOk();

        $this->assertSame(1, $this->phone->permissionRequests);
        $this->assertSame([], $this->phone->scheduled);

        $this->get(route('settings'))->assertSee('Meldingen staan uit voor UpMove.');
    }

    public function test_logging_out_removes_notifications_from_this_phone(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->putJson(route('settings.update'), $this->settings(['workOnly' => true]));
        $this->assertNotEmpty($this->phone->scheduled);

        $this->post(route('logout'));

        $this->assertSame([], $this->phone->scheduled);
    }

    public function test_settings_page_explains_notifications(): void
    {
        $user = User::factory()->create(['settings' => $this->settings(['frequency' => 30])]);

        $this->actingAs($user)->get(route('settings'))
            ->assertSee('elke <span data-work-frequency>30</span> minuten een beweegherinnering', false);

        config(['upmove.phone_notifications' => false]);

        $this->get(route('settings'))->assertSee('Meldingen krijg je alleen in de UpMove-app op je telefoon.');
    }

    public function test_in_the_browser_nothing_is_sent_to_a_phone(): void
    {
        config(['upmove.phone_notifications' => false]);
        $user = User::factory()->create();

        $this->actingAs($user)->putJson(route('settings.update'), $this->settings(['workOnly' => true]))->assertOk();
        $this->get(route('home'))->assertOk();

        $this->assertSame(0, $this->phone->cancelAllCalls);
        $this->assertSame([], $this->phone->scheduled);
    }
}
