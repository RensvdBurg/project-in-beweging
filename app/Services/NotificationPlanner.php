<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Berekent welke meldingen de telefoon moet krijgen (alleen een plan; versturen doet
 * PhoneNotifications). Alleen als "Alleen tijdens werk/school" aan staat, en alleen
 * binnen de ingestelde dagen en tijden (`workDays` + `workSchedule`):
 *
 * - beweegherinnering: elke `frequency` minuten, gerekend vanaf de starttijd van die dag;
 * - nieuwe opdracht: aan het begin van elk challenge-blok (ChallengeService, 90 minuten).
 *
 * Valt een herinnering binnen 5 minuten van een nieuwe opdracht, dan vervalt die
 * herinnering: de melding van de nieuwe opdracht zegt dan al dat het tijd is om te bewegen.
 *
 * iOS bewaart maximaal 64 geplande meldingen; daarom plannen we er hooguit MAX_PLANNED,
 * over hooguit HORIZON_DAYS dagen. Bij elk openen van de app wordt het plan aangevuld.
 */
class NotificationPlanner
{
    public const MAX_PLANNED = 60;

    public const HORIZON_DAYS = 7;

    /** Herinnering vervalt als er binnen zoveel seconden een nieuwe opdracht komt. */
    private const MERGE_SECONDS = 5 * 60;

    public const REMINDER_TITLE = 'Te lang gezeten!';

    public const REMINDER_BODY = 'Tijd om op te staan en een snelle 2 min stretch te doen.';

    public const CHALLENGE_TITLE = 'Nieuwe opdracht beschikbaar!';

    public const CHALLENGE_BODY = 'Er staat een nieuwe beweegopdracht voor je klaar in UpMove.';

    /**
     * @param  array{frequency: int, workOnly: bool, workDays: list<int>, workSchedule: list<array{start: string, end: string}>}  $preferences  zie UserSettings
     * @return list<array{id: string, at: int, kind: 'reminder'|'challenge', title: string, body: string}>
     */
    public function plan(array $preferences, CarbonInterface $now): array
    {
        if (! $preferences['workOnly']) {
            return [];
        }

        $now = CarbonImmutable::instance($now);
        $reminders = [];
        $challenges = [];

        foreach ($this->workPeriods($preferences, $now) as [$start, $end]) {
            // Beweegherinneringen: start + f, start + 2f, … tot en met de eindtijd.
            $reminder = $start->addMinutes($preferences['frequency']);

            while ($reminder <= $end) {
                $reminders[$reminder->getTimestamp()] = true;
                $reminder = $reminder->addMinutes($preferences['frequency']);
            }

            // Nieuwe opdracht: elk begin van een blok van 90 minuten binnen de periode.
            $interval = ChallengeService::INTERVAL_SECONDS;
            $block = (int) ceil($start->getTimestamp() / $interval) * $interval;

            for (; $block <= $end->getTimestamp(); $block += $interval) {
                $challenges[$block] = true;
            }
        }

        $planned = [];

        foreach (array_keys($challenges) as $at) {
            $planned[] = $this->notification('challenge', $at);
        }

        foreach (array_keys($reminders) as $at) {
            if (! $this->hasChallengeNear($challenges, $at)) {
                $planned[] = $this->notification('reminder', $at);
            }
        }

        $planned = array_values(array_filter($planned, fn (array $item): bool => $item['at'] > $now->getTimestamp()));
        usort($planned, fn (array $a, array $b): int => [$a['at'], $a['kind']] <=> [$b['at'], $b['kind']]);

        return array_slice($planned, 0, self::MAX_PLANNED);
    }

    /**
     * Werk/schoolperiodes van gisteren (voor een nachtdienst die nog loopt) tot en met de horizon.
     *
     * @param  array{workDays: list<int>, workSchedule: list<array{start: string, end: string}>}  $preferences
     * @return list<array{0: CarbonImmutable, 1: CarbonImmutable}>
     */
    private function workPeriods(array $preferences, CarbonImmutable $now): array
    {
        $periods = [];

        for ($offset = -1; $offset < self::HORIZON_DAYS; $offset++) {
            $day = $now->startOfDay()->addDays($offset);

            if (! in_array($day->dayOfWeek, $preferences['workDays'], true)) {
                continue;
            }

            $hours = $preferences['workSchedule'][$day->dayOfWeek];
            $start = $day->setTimeFromTimeString($hours['start']);
            $end = $day->setTimeFromTimeString($hours['end']);

            // Eindtijd vóór de starttijd: nachtdienst, loopt door tot de volgende dag.
            if ($end < $start) {
                $end = $end->addDay();
            }

            $periods[] = [$start, $end];
        }

        return $periods;
    }

    /**
     * @param  array<int, true>  $challenges
     */
    private function hasChallengeNear(array $challenges, int $at): bool
    {
        foreach (array_keys($challenges) as $challengeAt) {
            if (abs($challengeAt - $at) <= self::MERGE_SECONDS) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  'reminder'|'challenge'  $kind
     * @return array{id: string, at: int, kind: 'reminder'|'challenge', title: string, body: string}
     */
    private function notification(string $kind, int $at): array
    {
        return [
            'id' => "upmove-{$kind}-{$at}",
            'at' => $at,
            'kind' => $kind,
            'title' => $kind === 'challenge' ? self::CHALLENGE_TITLE : self::REMINDER_TITLE,
            'body' => $kind === 'challenge' ? self::CHALLENGE_BODY : self::REMINDER_BODY,
        ];
    }
}
