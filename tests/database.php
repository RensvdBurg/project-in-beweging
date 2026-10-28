<?php
// CLI-only helper. Refuses existing application databases and non-loopback hosts.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$name = getenv('DB_NAME');
$host = getenv('DB_HOST');
if (!is_string($name) || !preg_match('/\Ain_beweging_test_[a-f0-9]{16}\z/', $name)
    || $host !== '127.0.0.1' || !getenv('TEST_DB_PORT')) {
    throw new RuntimeException('Explicit isolated test database configuration required.');
}
$db = new PDO('mysql:host=127.0.0.1;port=' . (int) getenv('TEST_DB_PORT') . ';charset=utf8mb4',
    getenv('TEST_DB_USER') ?: 'root', getenv('TEST_DB_PASSWORD') ?: '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$operation = $argv[1] ?? '';
if ($operation === 'create') {
    // Deliberately no IF NOT EXISTS: never adopt another run's database.
    $db->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    exit;
}
if ($operation === 'drop') { $db->exec("DROP DATABASE `$name`"); exit; }
$db->exec("USE `$name`");
if ($operation === 'init') {
    foreach (['database/schema.sql', 'database/challenge_completions.sql', 'tests/fixtures.sql'] as $file) {
        $db->exec(file_get_contents(__DIR__ . '/../' . $file));
    }
} elseif ($operation === 'inspect') {
    $users = $db->query('SELECT password_hash, xp FROM users ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode([
        'users' => count($users),
        'passwordValid' => count($users) === 1 && password_verify('Test-password-123!', $users[0]['password_hash']),
        'hashed' => count($users) === 1 && $users[0]['password_hash'] !== 'Test-password-123!',
        'xp' => count($users) === 1 ? (int) $users[0]['xp'] : null,
        'completions' => (int) $db->query('SELECT COUNT(*) FROM challenge_completions')->fetchColumn(),
    ], JSON_THROW_ON_ERROR);
} else { throw new RuntimeException('Unknown test operation'); }
