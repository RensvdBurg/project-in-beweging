<?php

// Database-backed limits work across sessions and PHP workers. Do not trust proxy
// headers here: trusted proxy handling must be configured by the hosting provider.
function allowLoginAttempt(PDO $db, string $identifier, string $address): bool
{
    $window = intdiv(time(), 900);
    $cleanup = $db->prepare('DELETE FROM login_rate_limits WHERE window_id < :old');
    $cleanup->execute(['old' => $window - 1]);
    foreach (['account:' . strtolower($identifier) => 5, 'ip:' . $address => 30] as $key => $limit) {
        $hash = hash('sha256', $key);
        $increment = $db->prepare('INSERT INTO login_rate_limits (bucket_key, window_id, attempts)
            VALUES (:key, :window, 1) ON DUPLICATE KEY UPDATE attempts = attempts + 1');
        $increment->execute(['key' => $hash, 'window' => $window]);
        $count = $db->prepare('SELECT attempts FROM login_rate_limits WHERE bucket_key = :key AND window_id = :window');
        $count->execute(['key' => $hash, 'window' => $window]);
        if ((int) $count->fetchColumn() > $limit) return false;
    }
    return true;
}
