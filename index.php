<?php
require __DIR__ . '/pages/auth.php';

$isLoggedIn = $currentUser !== null;
$requestedScreen = $_GET['screen'] ?? 'home';
$enableLearnPageTransitions = $isLoggedIn
    && is_string($requestedScreen)
    && $requestedScreen === 'leren';
$isReturningToLearnPage = $enableLearnPageTransitions
    && is_string($_GET['transition'] ?? null)
    && $_GET['transition'] === 'back';
?>
<!doctype html>
<html lang="nl"<?= $isReturningToLearnPage ? ' class="learn-page-transition-back"' : '' ?>>
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
        <script src="pages/login.js" defer></script>
    <?php endif; ?>
</body>
</html>
