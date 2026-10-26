<?php
require __DIR__ . '/pages/auth.php';

$isLoggedIn = $currentUser !== null;
?>
<!doctype html>
<html lang="nl">
<head>
    <?php require __DIR__ . '/components/header.php'; ?>
</head>
<body>
    <?php if ($isLoggedIn): ?>
        <?php require __DIR__ . '/pages/home/home.php'; ?>
        <?php require __DIR__ . '/components/footer.php'; ?>
    <?php else: ?>
        <?php require __DIR__ . '/pages/login.php'; ?>
        <script src="pages/login.js" defer></script>
    <?php endif; ?>
</body>
</html>
