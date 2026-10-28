<?php

$cssFile = __DIR__ . '/../css/output.css';

$cssVersion = file_exists($cssFile)
    ? filemtime($cssFile)
    : time();

$initialSettings = [
    'userId' => null,
    'csrfToken' => null,
    'settings' => [
        'frequency' => 60,
        'darkMode' => false,
        'workOnly' => false,
        'workStart' => '08:30',
        'workEnd' => '17:00',
        'workDays' => [1, 2, 3, 4, 5],
    ],
    'exists' => false,
];

if (!empty($_SESSION['user']['id'])) {
    require_once __DIR__ . '/../database/database.php';
    require_once __DIR__ . '/../database/user-settings.php';

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    $userSettingsData = getUserSettings(
        getDatabaseConnection(),
        (int) $_SESSION['user']['id']
    );
    $initialSettings = [
        'userId' => (int) $_SESSION['user']['id'],
        'csrfToken' => $_SESSION['csrf_token'],
        'settings' => $userSettingsData['settings'],
        'exists' => $userSettingsData['exists'],
    ];
}

?>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1, viewport-fit=cover">

<meta
    name="theme-color"
    content="#f7f8fa"
    id="theme-color">

<title><?= htmlspecialchars($pageTitle ?? 'In beweging', ENT_QUOTES, 'UTF-8') ?></title>
<!-- ========================================= -->
<!-- GLOBALE INSTELLINGEN -->
<!-- Dark mode + werk/school instellingen -->
<!-- ========================================= -->

<script>
    window.upmoveInitialSettings = <?= json_encode(
        $initialSettings,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
    ) ?>;
</script>
<script
    src="/project-in-beweging/assets/js/app-settings.js?v=<?= filemtime(__DIR__ . '/../assets/js/app-settings.js') ?>"></script>


<!-- ========================================= -->
<!-- ICONEN -->
<!-- ========================================= -->

<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/gh/iconoir-icons/iconoir@main/css/iconoir.css">


<!-- ========================================= -->
<!-- TAILWIND -->
<!-- Cache wordt automatisch vernieuwd -->
<!-- ========================================= -->

<link
    rel="stylesheet"
    href="/project-in-beweging/css/output.css?v=<?= $cssVersion ?>">

<?php if (!empty($enableLearnPageTransitions)): ?>
    <?php $learnTransitionCss = __DIR__ . '/../assets/css/learn-page-transition.css'; ?>
    <link
        rel="stylesheet"
        href="/project-in-beweging/assets/css/learn-page-transition.css?v=<?= filemtime($learnTransitionCss) ?>">
<?php endif; ?>


<!-- ========================================= -->
<!-- FONT -->
<!-- ========================================= -->

<link
    rel="preconnect"
    href="https://fonts.googleapis.com">

<link
    rel="preconnect"
    href="https://fonts.gstatic.com"
    crossorigin>

<link
    href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap"
    rel="stylesheet">