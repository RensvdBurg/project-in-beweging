<?php
require __DIR__ . '/pages/auth.php';

$isLoggedIn = $currentUser !== null;
$requestedScreen = $_GET['screen'] ?? 'home';
$requestedTransition = $_GET['transition'] ?? null;
$enableLearnPageTransitions = $isLoggedIn
    && is_string($requestedScreen)
    && $requestedScreen === 'leren';
$isReturningToLearnPage = $enableLearnPageTransitions
    && $requestedTransition === 'back';
$enableSettingsPageTransitions = $isLoggedIn;
$pageTransitionClass = $isReturningToLearnPage
    ? 'learn-page-transition-back'
    : '';
?>
<!doctype html>
<html lang="nl" <?= $pageTransitionClass !== '' ? ' class="' . $pageTransitionClass . '"' : '' ?>>

<head>
    <?php require __DIR__ . '/components/header.php'; ?>
</head>

<body>
    <?php if ($isLoggedIn): ?>
        <?php require __DIR__ . '/pages/home/home.php'; ?>
        <?php require __DIR__ . '/pages/ranglijst/ranglijst.php'; ?>
        <?php require __DIR__ . '/pages/learn/learn.php'; ?>
        <?php require __DIR__ . '/components/footer.php'; ?>
    <?php else: ?>
        <?php require __DIR__ . '/pages/login.php'; ?>
        <script src="/project-in-beweging/assets/js/login.js?v=<?= filemtime(__DIR__ . '/../../assets/js/login.js') ?>"></script>
    <?php endif; ?>
</body>

</html>