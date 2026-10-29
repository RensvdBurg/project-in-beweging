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
require_once __DIR__ . '/../../database/activity-posts.php';
require_once __DIR__ . '/../../database/user-settings.php';

$userId = (int) $_SESSION['user']['id'];
$activityPageSize = 5;
$requestedActivityPage = filter_input(
    INPUT_GET,
    'activity_page',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
$activityPage = is_int($requestedActivityPage) ? $requestedActivityPage : 1;
$profileActivities = [];
$profileActivityPageCount = 1;
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$userSettingsData = [
    'settings' => getDefaultUserSettings(),
    'exists' => false,
];

try {
    $db = getDatabaseConnection();
    ensureActivityPostsTable($db);
    $userSettingsData = getUserSettings($db, $userId);
    $profileStatement = $db->prepare(
        'SELECT id, username, full_name, about_me, COALESCE(xp, 0) AS xp
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

        $activityCountStatement = $db->prepare(
            'SELECT COUNT(*) FROM activity_posts WHERE user_id = :user_id'
        );
        $activityCountStatement->execute(['user_id' => $profileId]);
        $activityCount = (int) $activityCountStatement->fetchColumn();
        $profileActivityPageCount = max(1, (int) ceil($activityCount / $activityPageSize));
        $activityPage = min($activityPage, $profileActivityPageCount);
        $activityOffset = ($activityPage - 1) * $activityPageSize;

        $activitiesStatement = $db->prepare(
            'SELECT activity_posts.id, activity_posts.title, activity_posts.description,
                    activity_posts.image_filename, activity_posts.created_at,
                    (SELECT COUNT(*) FROM activity_post_likes
                     WHERE activity_post_likes.post_id = activity_posts.id) AS like_count,
                    EXISTS (
                        SELECT 1 FROM activity_post_likes
                        WHERE activity_post_likes.post_id = activity_posts.id
                          AND activity_post_likes.user_id = :current_user_id
                    ) AS liked_by_user,
                    (SELECT COUNT(*) FROM activity_post_comments
                     WHERE activity_post_comments.post_id = activity_posts.id) AS comment_count
             FROM activity_posts
             WHERE activity_posts.user_id = :profile_user_id
             ORDER BY activity_posts.created_at DESC, activity_posts.id DESC
             LIMIT :limit OFFSET :offset'
        );
        $activitiesStatement->bindValue(':current_user_id', $userId, PDO::PARAM_INT);
        $activitiesStatement->bindValue(':profile_user_id', $profileId, PDO::PARAM_INT);
        $activitiesStatement->bindValue(':limit', $activityPageSize, PDO::PARAM_INT);
        $activitiesStatement->bindValue(':offset', $activityOffset, PDO::PARAM_INT);
        $activitiesStatement->execute();
        $profileActivities = $activitiesStatement->fetchAll();
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
    ? $profile['full_name'] . ' | Profiel'
    : 'Profiel | Ranglijst';
$returnToHome = ($_GET['from'] ?? '') === 'home';

?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#f7f8fa" id="theme-color">
    <title><?= $escape($pageTitle) ?></title>
    <link rel="stylesheet" href="/project-in-beweging/css/output.css?v=<?= $cssVersion ?>">
    <meta
        name="upmove-initial-settings"
        content="<?= htmlspecialchars(
            json_encode([
                'userId' => $userId,
                'csrfToken' => $_SESSION['csrf_token'],
                'settings' => $userSettingsData['settings'],
                'exists' => $userSettingsData['exists'],
            ], JSON_THROW_ON_ERROR),
            ENT_QUOTES,
            'UTF-8'
        ) ?>">
    <script
        src="/project-in-beweging/assets/js/app-settings.js?v=<?= filemtime(__DIR__ . '/../../assets/js/app-settings.js') ?>"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="min-h-dvh bg-app-bg font-sans text-ink">
    <main class="mx-auto min-h-dvh w-full max-w-lg px-4 pb-10 pt-[max(1.5rem,env(safe-area-inset-top))] sm:px-6">
        <a
            href="<?= $returnToHome ? '/project-in-beweging/?screen=home#activity-feed' : '/project-in-beweging/?screen=ranglijst' ?>"
            class="mb-6 inline-flex items-center gap-2 rounded-full bg-surface px-4 py-2.5 text-sm font-bold text-ink shadow-card transition hover:-translate-x-1 focus-visible:outline-2 focus-visible:outline-orange">
            <span aria-hidden="true">&larr;</span>
            <?= $returnToHome ? 'Terug naar activiteiten' : 'Terug naar ranglijst' ?>
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
                <?php if ($profile['about_me'] !== ''): ?>
                    <div class="mt-4 border-t border-[#eceef2] pt-4 text-left">
                        <h3 class="text-sm font-bold">Over mij</h3>
                        <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-muted"><?= nl2br($escape($profile['about_me'])) ?></p>
                    </div>
                <?php endif; ?>
                <div class="mt-6 grid grid-cols-2 gap-3">
                    <div class="rounded-2xl bg-app-bg p-4">
                        <p class="text-xs font-semibold text-muted">Totaal XP</p>
                        <p class="mt-1 text-lg font-extrabold text-orange"><?= (int) $profile['xp'] ?></p>
                    </div>
                    <div class="rounded-2xl bg-app-bg p-4">
                        <p class="text-xs font-semibold text-muted">Rang</p>
                        <p class="mt-1 text-lg font-extrabold text-orange">#<?= $profileRank ?></p>
                    </div>
                </div>
            </section>

            <section class="mt-6" aria-labelledby="profile-activities-title">
                <header class="mb-3">
                    <p class="mb-1 text-sm font-bold text-orange">Gedeeld door <?= $escape($profile['full_name']) ?></p>
                    <h2 id="profile-activities-title" class="text-xl font-extrabold tracking-tight">Activiteiten</h2>
                </header>

                <?php if ($profileActivities === []): ?>
                    <p class="rounded-[18px] bg-surface p-4 text-sm font-semibold text-muted shadow-card">
                        <?= $escape($profile['full_name']) ?> heeft nog geen activiteiten gedeeld.
                    </p>
                <?php else: ?>
                    <ul class="space-y-4">
                        <?php foreach ($profileActivities as $activityPost): ?>
                            <li>
                                <article class="overflow-hidden rounded-[20px] bg-surface shadow-card">
                                    <img
                                        src="/project-in-beweging/uploads/activiteiten/<?= rawurlencode($activityPost['image_filename']) ?>"
                                        alt="<?= $escape($activityPost['title']) ?>"
                                        class="max-h-80 w-full object-cover"
                                        loading="lazy">
                                    <?php require __DIR__ . '/../../components/activity-interactions.php'; ?>
                                    <div class="p-4">
                                        <h3 class="text-base font-extrabold tracking-tight"><?= $escape($activityPost['title']) ?></h3>
                                        <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-muted"><?= $escape($activityPost['description']) ?></p>
                                        <time
                                            class="mt-3 block text-xs font-medium text-muted"
                                            datetime="<?= $escape(date(DATE_ATOM, strtotime($activityPost['created_at']))) ?>">
                                            <?= $escape(date('d-m-Y H:i', strtotime($activityPost['created_at']))) ?>
                                        </time>
                                    </div>
                                </article>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if ($profileActivityPageCount > 1): ?>
                        <nav class="mt-5 flex flex-wrap justify-center gap-2" aria-label="Pagina's met activiteiten van <?= $escape($profile['full_name']) ?>">
                            <?php for ($page = 1; $page <= $profileActivityPageCount; $page++): ?>
                                <a
                                    href="/project-in-beweging/pages/ranglijst/profile.php?id=<?= $profileId ?>&amp;from=<?= $returnToHome ? 'home' : 'ranglijst' ?>&amp;activity_page=<?= $page ?>#profile-activities-title"
                                    class="activity-feed-page-link grid h-10 min-w-10 place-items-center rounded-full px-3 text-sm font-bold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange <?= $page === $activityPage ? 'bg-orange text-white' : 'bg-surface text-ink shadow-card hover:bg-orange-soft' ?>"
                                    <?= $page === $activityPage ? 'aria-current="page"' : '' ?>>
                                    <?= $page ?>
                                </a>
                            <?php endfor; ?>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
    <?php if (isset($profile) && is_array($profile)): ?>
        <script src="/project-in-beweging/assets/js/activity-interactions.js?v=<?= filemtime(__DIR__ . '/../../assets/js/activity-interactions.js') ?>" defer></script>
    <?php endif; ?>
</body>
</html>
