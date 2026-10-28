<?php

$secureCookie = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $secureCookie,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (empty($_SESSION['user']['id'])) {
    header('Location: /project-in-beweging/index.php', true, 303);
    exit;
}

$profileId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
if (!is_int($profileId)) {
    http_response_code(400);
    exit('Ongeldig profiel.');
}

require_once __DIR__ . '/../../database/database.php';

try {
    $db = getDatabaseConnection();
    $profileStatement = $db->prepare(
        'SELECT id, username, full_name, COALESCE(xp, 0) AS xp
         FROM users
         WHERE id = :user_id'
    );
    $profileStatement->execute(['user_id' => $profileId]);
    $profile = $profileStatement->fetch();

    if ($profile === false) {
        http_response_code(404);
    } else {
        $rankStatement = $db->prepare(
            'SELECT COUNT(*) + 1
             FROM users
             WHERE COALESCE(xp, 0) > :profile_xp
                OR (
                    COALESCE(xp, 0) = :tie_xp
                    AND (
                        username < :profile_username
                        OR (username = :same_username AND id < :user_id)
                    )
                )'
        );
        $rankStatement->execute([
            'profile_xp' => (int) $profile['xp'],
            'tie_xp' => (int) $profile['xp'],
            'profile_username' => $profile['username'],
            'same_username' => $profile['username'],
            'user_id' => $profileId,
        ]);
        $profileRank = (int) $rankStatement->fetchColumn();
    }
} catch (PDOException $exception) {
    error_log('Profiel/databasefout: ' . $exception->getMessage());
    http_response_code(500);
    $profile = null;
    $profileRank = null;
}

$escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$cssFile = __DIR__ . '/../../css/output.css';
$cssVersion = file_exists($cssFile) ? filemtime($cssFile) : time();
$pageTitle = isset($profile) && is_array($profile)
    ? $profile['full_name'] . ' | Ranglijst'
    : 'Profiel | Ranglijst';

?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#f7f8fa">
    <title><?= $escape($pageTitle) ?></title>
    <link rel="stylesheet" href="/project-in-beweging/css/output.css?v=<?= $cssVersion ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="min-h-dvh bg-app-bg font-sans text-ink">
    <main class="mx-auto min-h-dvh w-full max-w-lg px-4 pb-10 pt-[max(1.5rem,env(safe-area-inset-top))] sm:px-6">
        <a
            href="/project-in-beweging/?screen=ranglijst"
            class="mb-6 inline-flex items-center gap-2 rounded-full bg-surface px-4 py-2.5 text-sm font-bold text-ink shadow-card transition hover:-translate-x-1 focus-visible:outline-2 focus-visible:outline-orange">
            <span aria-hidden="true">&larr;</span>
            Terug naar ranglijst
        </a>

        <?php if (!isset($profile) || $profile === false): ?>
            <section class="rounded-[22px] bg-surface p-6 shadow-card" role="alert">
                <h1 class="text-xl font-extrabold">Profiel niet beschikbaar</h1>
                <p class="mt-2 text-sm text-muted">Dit profiel kon niet worden geladen. Probeer het later opnieuw.</p>
            </section>
        <?php else: ?>
            <?php
            $initials = '';
            foreach (preg_split('/\s+/', trim($profile['full_name'])) ?: [] as $namePart) {
                if ($namePart !== '') {
                    $characters = preg_split('//u', $namePart, -1, PREG_SPLIT_NO_EMPTY);
                    $initials .= $characters[0] ?? '?';
                    if (preg_match('/\A.{2}/us', $initials)) {
                        break;
                    }
                }
            }
            $initials = $initials !== '' ? strtoupper($initials) : '?';
            ?>
            <header class="mb-5">
                <p class="mb-1 text-sm font-bold text-orange">Samen in beweging</p>
                <h1 class="text-2xl font-extrabold tracking-tight">Profiel</h1>
            </header>

            <section class="rounded-[22px] bg-surface p-6 text-center shadow-card" aria-label="Profiel van <?= $escape($profile['full_name']) ?>">
                <span class="mx-auto grid h-20 w-20 place-items-center rounded-full bg-orange-soft text-2xl font-extrabold text-orange" aria-hidden="true">
                    <?= $escape($initials) ?>
                </span>
                <h2 class="mt-4 text-xl font-extrabold"><?= $escape($profile['full_name']) ?></h2>
                <p class="mt-1 text-sm font-medium text-muted">@<?= $escape($profile['username']) ?></p>
                <div class="mt-6 grid grid-cols-2 gap-3">
                    <div class="rounded-2xl bg-app-bg/60 p-4">
                        <p class="text-xs font-semibold text-muted">Totaal XP</p>
                        <p class="mt-1 text-lg font-extrabold text-orange"><?= (int) $profile['xp'] ?></p>
                    </div>
                    <div class="rounded-2xl bg-app-bg/60 p-4">
                        <p class="text-xs font-semibold text-muted">Rang</p>
                        <p class="mt-1 text-lg font-extrabold text-orange">#<?= $profileRank ?></p>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
