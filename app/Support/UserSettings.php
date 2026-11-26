<?php

namespace App\Support;

/**
 * Standaardwaarden en controle van de instellingen per gebruiker (kolom users.settings).
 *
 * Dezelfde regels als in resources/js/settings-page.js: ontbrekende of ongeldige
 * waarden vallen terug op de standaard.
 */
final class UserSettings
{
    /** Toegestane herinneringsfrequenties in minuten (schuifregelaar 15–120, stap 15). */
    public const FREQUENCIES = [15, 30, 45, 60, 75, 90, 105, 120];

    public const DEFAULTS = [
        'frequency' => 45,
        'dailyGoal' => 12,
        'darkMode' => false,
        'workOnly' => false,
        'workStart' => '08:30',
        'workEnd' => '17:00',
        'workDays' => [1, 2, 3, 4, 5],
        // Tijden per dag (index 0 = zondag … 6 = zaterdag, zoals JavaScript getDay()).
        'workSchedule' => [
            ['start' => '08:30', 'end' => '17:00'],
            ['start' => '08:30', 'end' => '17:00'],
            ['start' => '08:30', 'end' => '17:00'],
            ['start' => '08:30', 'end' => '17:00'],
            ['start' => '08:30', 'end' => '17:00'],
            ['start' => '08:30', 'end' => '17:00'],
            ['start' => '08:30', 'end' => '17:00'],
        ],
    ];

    private const BOOLEANS = ['darkMode', 'workOnly'];

    /**
     * @param  array<string, mixed>|null  $saved
     * @return array{frequency: int, dailyGoal: int, darkMode: bool, workOnly: bool, workStart: string, workEnd: string, workDays: list<int>, workSchedule: list<array{start: string, end: string}>}
     */
    public static function normalize(?array $saved): array
    {
        $settings = self::DEFAULTS;
        $saved ??= [];

        if (in_array($saved['frequency'] ?? null, self::FREQUENCIES, true)) {
            $settings['frequency'] = $saved['frequency'];
        }

        if (is_int($saved['dailyGoal'] ?? null) && $saved['dailyGoal'] >= 1 && $saved['dailyGoal'] <= 30) {
            $settings['dailyGoal'] = $saved['dailyGoal'];
        }

        foreach (self::BOOLEANS as $name) {
            if (is_bool($saved[$name] ?? null)) {
                $settings[$name] = $saved[$name];
            }
        }

        foreach (['workStart', 'workEnd'] as $name) {
            if (self::isTime($saved[$name] ?? null)) {
                $settings[$name] = $saved[$name];
            }
        }

        if (is_array($saved['workDays'] ?? null)) {
            $days = array_values(array_unique(array_filter(
                $saved['workDays'],
                fn ($day): bool => is_int($day) && $day >= 0 && $day <= 6,
            )));

            if ($days !== []) {
                $settings['workDays'] = $days;
            }
        }

        // Per dag een geldige tijd; ontbreekt die (bijv. bij instellingen van vóór de
        // tijden per dag), dan geldt de algemene start-/eindtijd (zoals vroeger).
        $schedule = is_array($saved['workSchedule'] ?? null) ? $saved['workSchedule'] : [];

        for ($day = 0; $day <= 6; $day++) {
            $hours = is_array($schedule[$day] ?? null) ? $schedule[$day] : [];

            $settings['workSchedule'][$day] = [
                'start' => self::isTime($hours['start'] ?? null) ? $hours['start'] : $settings['workStart'],
                'end' => self::isTime($hours['end'] ?? null) ? $hours['end'] : $settings['workEnd'],
            ];
        }

        return $settings;
    }

    private static function isTime(mixed $value): bool
    {
        return is_string($value) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value) === 1;
    }
}
