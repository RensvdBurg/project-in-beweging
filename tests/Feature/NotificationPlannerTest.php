<?php

namespace Tests\Feature;

use App\Services\NotificationPlanner;
use App\Support\UserSettings;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class NotificationPlannerTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function preferences(array $overrides = []): array
    {
        return UserSettings::normalize(array_merge(['workOnly' => true, 'frequency' => 45], $overrides));
    }

    /**
     * @return list<string> "dag HH:MM soort", in Nederlandse tijd
     */
    private function times(array $plan): array
    {
        return array_map(
            fn (array $item): string => CarbonImmutable::createFromTimestamp($item['at'], 'Europe/Amsterdam')->format('D H:i').' '.$item['kind'],
            $plan,
        );
    }

    private function monday(string $time): CarbonImmutable
    {
        // Maandag 16 november 2026 (wintertijd, UTC+1).
        return CarbonImmutable::parse("2026-11-16 {$time}", 'Europe/Amsterdam');
    }

    public function test_nothing_is_planned_when_work_only_is_off(): void
    {
        $plan = (new NotificationPlanner)->plan($this->preferences(['workOnly' => false]), $this->monday('07:00'));

        $this->assertSame([], $plan);
    }

    public function test_reminders_every_frequency_and_new_challenges_within_work_hours(): void
    {
        $preferences = $this->preferences(['workDays' => [1], 'frequency' => 60]);
        $plan = (new NotificationPlanner)->plan($preferences, $this->monday('07:00'));

        // Blokken van 90 min (vanaf middernacht UTC) beginnen in wintertijd om
        // 08:30, 10:00, 11:30, 13:00, 14:30 en 16:00 Nederlandse tijd.
        $this->assertSame([
            'Mon 08:30 challenge',
            'Mon 09:30 reminder',
            'Mon 10:00 challenge', // herinnering van 10:30 valt niet weg: 30 min verschil
            'Mon 10:30 reminder',
            'Mon 11:30 challenge', // herinnering van 11:30 vervalt (zelfde moment)
            'Mon 12:30 reminder',
            'Mon 13:00 challenge',
            'Mon 13:30 reminder',
            'Mon 14:30 challenge', // herinnering van 14:30 vervalt
            'Mon 15:30 reminder',
            'Mon 16:00 challenge',
            'Mon 16:30 reminder',
        ], array_slice($this->times($plan), 0, 12));

        $this->assertSame(NotificationPlanner::REMINDER_TITLE, $plan[1]['title']);
        $this->assertSame(NotificationPlanner::CHALLENGE_TITLE, $plan[0]['title']);
        $this->assertStringStartsWith('upmove-challenge-', $plan[0]['id']);
    }

    public function test_nothing_outside_work_hours_or_on_other_days(): void
    {
        $schedule = UserSettings::DEFAULTS['workSchedule'];
        $schedule[3] = ['start' => '09:00', 'end' => '12:00']; // woensdag korter

        $plan = (new NotificationPlanner)->plan(
            $this->preferences(['workDays' => [3], 'workSchedule' => $schedule, 'frequency' => 60]),
            $this->monday('07:00'),
        );

        $this->assertSame(['Wed 10:00 challenge', 'Wed 11:00 reminder', 'Wed 11:30 challenge'], array_slice($this->times($plan), 0, 3));

        foreach ($plan as $item) {
            $local = CarbonImmutable::createFromTimestamp($item['at'], 'Europe/Amsterdam');
            $this->assertSame(3, $local->dayOfWeek, 'alleen op woensdag');
            $this->assertTrue($local->format('H:i') >= '09:00' && $local->format('H:i') <= '12:00', 'binnen 09:00-12:00');
        }
    }

    public function test_only_future_notifications_are_planned(): void
    {
        $plan = (new NotificationPlanner)->plan($this->preferences(['workDays' => [1], 'frequency' => 60]), $this->monday('12:00'));

        $this->assertSame('Mon 12:30 reminder', $this->times($plan)[0]);
    }

    public function test_night_shift_continues_after_midnight(): void
    {
        $schedule = UserSettings::DEFAULTS['workSchedule'];
        $schedule[5] = ['start' => '22:00', 'end' => '02:00']; // vrijdagnacht

        $plan = (new NotificationPlanner)->plan(
            $this->preferences(['workDays' => [5], 'workSchedule' => $schedule, 'frequency' => 120]),
            $this->monday('07:00'),
        );

        $this->assertContains('Sat 00:00 reminder', $this->times($plan));
        $this->assertContains('Sat 02:00 reminder', $this->times($plan));
        $this->assertNotContains('Sat 04:00 reminder', $this->times($plan));
    }

    public function test_at_most_sixty_notifications_because_of_the_ios_limit(): void
    {
        $plan = (new NotificationPlanner)->plan(
            $this->preferences(['workDays' => [0, 1, 2, 3, 4, 5, 6], 'frequency' => 15]),
            $this->monday('07:00'),
        );

        $this->assertCount(NotificationPlanner::MAX_PLANNED, $plan);
        $this->assertSame(count($plan), count(array_unique(array_column($plan, 'id'))), 'unieke ids');

        $ats = array_column($plan, 'at');
        $sorted = $ats;
        sort($sorted);
        $this->assertSame($sorted, $ats, 'op tijdvolgorde');
    }

    public function test_times_stay_correct_around_daylight_saving_time(): void
    {
        // Zondag 28 maart 2027 gaat de klok vooruit; maandag 29 maart is zomertijd (UTC+2).
        $plan = (new NotificationPlanner)->plan(
            $this->preferences(['workDays' => [1], 'frequency' => 60]),
            CarbonImmutable::parse('2027-03-26 12:00', 'Europe/Amsterdam'),
        );

        $reminders = array_values(array_filter($this->times($plan), fn (string $t): bool => str_ends_with($t, 'reminder')));

        // Blokken volgen UTC: in zomertijd beginnen ze om 08:00, 09:30, 11:00 … Nederlandse tijd.
        $this->assertContains('Mon 09:30 challenge', $this->times($plan));
        $this->assertContains('Mon 11:00 challenge', $this->times($plan));
        // Herinneringen blijven op hele uren na 08:30 in Nederlandse tijd; die van 09:30
        // vervalt omdat er dan een nieuwe opdracht komt.
        $this->assertSame(['Mon 10:30 reminder', 'Mon 11:30 reminder'], array_slice($reminders, 0, 2));
    }
}
