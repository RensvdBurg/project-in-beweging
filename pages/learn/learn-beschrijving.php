<?php

require_once __DIR__ . '/../auth.php';

if ($currentUser === null) {
    header('Location: /project-in-beweging/', true, 303);
    exit;
}

require_once __DIR__ . '/../../database/database.php';

$requestedChallengeId = filter_input(
    INPUT_GET,
    'challenge',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
$viewedChallenge = null;
$challengeLoadError = false;

if ($requestedChallengeId !== false && $requestedChallengeId !== null) {
    try {
        $db = getDatabaseConnection();
        $statement = $db->prepare(
            'SELECT id, title, description, img, kcal_burned, difficulty, xp_reward, description_extra
             FROM challenges
             WHERE id = :id'
        );
        $statement->execute(['id' => $requestedChallengeId]);
        $viewedChallenge = $statement->fetch() ?: null;
    } catch (PDOException $exception) {
        error_log('Challengebeschrijving/databasefout: ' . $exception->getMessage());
        $challengeLoadError = true;
    }
}

$pageTitle = $viewedChallenge !== null
    ? $viewedChallenge['title'] . ' | In beweging'
    : 'Challenge | In beweging';

?>
<!doctype html>
<html lang="nl">
<head>
    <?php require __DIR__ . '/../../components/header.php'; ?>
</head>
<body>
    <main class="mx-auto min-h-dvh w-full max-w-lg overflow-y-auto scroll-smooth px-4 pb-28 pt-[max(1rem,env(safe-area-inset-top))] motion-reduce:scroll-auto sm:px-6">
        <a href="/project-in-beweging/?screen=leren#challenge-list"
            class="mb-5 inline-flex items-center gap-2 py-2 text-sm font-bold text-muted transition hover:text-orange"
            aria-label="Terug naar alle challenges">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="m15 18-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
            Alle challenges
        </a>

        <?php if ($viewedChallenge !== null): ?>
            <article class="overflow-hidden rounded-[22px] bg-surface shadow-card">
                <?php if (!empty($viewedChallenge['img'])): ?>
                    <img src="<?= htmlspecialchars($viewedChallenge['img'], ENT_QUOTES, 'UTF-8') ?>"
                        alt="<?= htmlspecialchars($viewedChallenge['title'], ENT_QUOTES, 'UTF-8') ?>"
                        class="h-56 w-full object-cover" loading="lazy">
                <?php endif; ?>
                <div class="p-5 sm:p-7">
                    <p class="mb-2 text-sm font-bold text-orange">Challenge</p>
                    <h1 class="mb-3 text-2xl font-extrabold tracking-tight">
                        <?= htmlspecialchars($viewedChallenge['title'], ENT_QUOTES, 'UTF-8') ?>
                    </h1>

                    <div class="mb-5 flex flex-wrap gap-2 text-sm font-semibold text-muted">
                        <span class="rounded-full bg-orange-soft px-3 py-1">
                            <?= htmlspecialchars($viewedChallenge['difficulty'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <span class="rounded-full bg-orange-soft px-3 py-1">
                            <?= (int) $viewedChallenge['kcal_burned'] ?> kcal
                        </span>
                        <span class="rounded-full bg-orange-soft px-3 py-1">
                            <?= (int) $viewedChallenge['xp_reward'] ?> XP
                        </span>
                    </div>

                    <h2 class="mb-2 text-lg font-extrabold">Over deze challenge</h2>
                    <p class="whitespace-pre-line text-sm leading-relaxed text-muted">
                        <?= htmlspecialchars($viewedChallenge['description_extra'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>
            </article>
        <?php elseif ($challengeLoadError): ?>
            <p class="rounded-[18px] bg-surface p-4 text-sm font-semibold text-muted shadow-card" role="alert">
                De challenge kon niet worden geladen. Probeer het later opnieuw.
            </p>
        <?php else: ?>
            <p class="rounded-[18px] bg-surface p-4 text-sm font-semibold text-muted shadow-card" role="status">
                Deze challenge is niet gevonden.
            </p>
        <?php endif; ?>
    </main>

    <?php require __DIR__ . '/../../components/footer.php'; ?>
</body>
</html>