<?php

function getDefaultUserSettings(): array
{
    return [
        'frequency' => 60,
        'darkMode' => false,
        'workOnly' => false,
        'workStart' => '08:30',
        'workEnd' => '17:00',
        'workDays' => [1, 2, 3, 4, 5],
    ];
}

function ensureUserSettingsTable(PDO $db): void
{
    $db->exec(
        'CREATE TABLE IF NOT EXISTS user_settings (
            user_id INT UNSIGNED NOT NULL PRIMARY KEY,
            settings_json LONGTEXT NOT NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
}

function getUserSettings(PDO $db, int $userId): array
{
    ensureUserSettingsTable($db);

    $statement = $db->prepare(
        'SELECT settings_json FROM user_settings WHERE user_id = :user_id'
    );
    $statement->execute(['user_id' => $userId]);
    $savedSettings = $statement->fetchColumn();

    if (!is_string($savedSettings)) {
        return [
            'settings' => getDefaultUserSettings(),
            'exists' => false,
        ];
    }

    $settings = json_decode($savedSettings, true);
    if (!is_array($settings)) {
        throw new RuntimeException('Stored user settings contain invalid JSON.');
    }

    return [
        'settings' => normalizeUserSettings($settings),
        'exists' => true,
    ];
}

function normalizeUserSettings(array $settings): array
{
    $defaults = getDefaultUserSettings();
    $frequency = $settings['frequency'] ?? $defaults['frequency'];
    $workDays = $settings['workDays'] ?? $defaults['workDays'];

    if (
        !is_int($frequency) ||
        $frequency < 15 ||
        $frequency > 120 ||
        $frequency % 15 !== 0
    ) {
        $frequency = $defaults['frequency'];
    }

    if (!is_array($workDays)) {
        $workDays = $defaults['workDays'];
    }

    $workDays = array_values(array_unique(array_filter(
        $workDays,
        static fn($day): bool => is_int($day) && $day >= 0 && $day <= 6
    )));
    if ($workDays === []) {
        $workDays = $defaults['workDays'];
    }

    return [
        'frequency' => $frequency,
        'darkMode' => is_bool($settings['darkMode'] ?? null)
            ? $settings['darkMode']
            : $defaults['darkMode'],
        'workOnly' => is_bool($settings['workOnly'] ?? null)
            ? $settings['workOnly']
            : $defaults['workOnly'],
        'workStart' => is_string($settings['workStart'] ?? null)
            && preg_match('/\A(?:[01]\d|2[0-3]):[0-5]\d\z/', $settings['workStart'])
            ? $settings['workStart']
            : $defaults['workStart'],
        'workEnd' => is_string($settings['workEnd'] ?? null)
            && preg_match('/\A(?:[01]\d|2[0-3]):[0-5]\d\z/', $settings['workEnd'])
            ? $settings['workEnd']
            : $defaults['workEnd'],
        'workDays' => $workDays,
    ];
}

function saveUserSettings(PDO $db, int $userId, array $settings): array
{
    ensureUserSettingsTable($db);
    $normalizedSettings = normalizeUserSettings($settings);
    $encodedSettings = json_encode($normalizedSettings, JSON_THROW_ON_ERROR);

    $statement = $db->prepare(
        'INSERT INTO user_settings (user_id, settings_json)
         VALUES (:user_id, :settings_json)
         ON DUPLICATE KEY UPDATE settings_json = VALUES(settings_json)'
    );
    $statement->execute([
        'user_id' => $userId,
        'settings_json' => $encodedSettings,
    ]);

    return $normalizedSettings;
}
