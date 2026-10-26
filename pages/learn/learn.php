<?php

require_once __DIR__ . '/../../database/database.php';

$challenges = [];
$challengeLoadError = false;

try {
    $db = getDatabaseConnection();
    $statement = $db->query(
        'SELECT id, title, description, img, kcal_burned, difficulty, xp_reward
         FROM challenges
         ORDER BY id'
    );
    $challenges = $statement->fetchAll();
} catch (PDOException $exception) {
    error_log('Challenges/databasefout: ' . $exception->getMessage());
    $challengeLoadError = true;
}

$requestedChallengeId = filter_input(
    INPUT_GET,
    'challenge',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
$viewedChallenge = null;
if ($requestedChallengeId !== false && $requestedChallengeId !== null) {
    foreach ($challenges as $challenge) {
        if ((int) $challenge['id'] === $requestedChallengeId) {
            $viewedChallenge = $challenge;
            break;
        }
    }
}
?>
<main class="screen<?= (($_GET['screen'] ?? 'home') === 'leren') ? '' : ' hidden' ?> mx-auto min-h-dvh w-full max-w-lg overflow-y-auto px-4 pb-28 pt-[max(1rem,env(safe-area-inset-top))] sm:px-6"
    id="screen-leren">
    <?php if ($viewedChallenge !== null): ?>
        <a href="/project-in-beweging/?screen=leren"
            class="mb-5 inline-flex items-center gap-2 py-2 text-sm font-bold text-muted transition hover:text-orange"
            aria-label="Terug naar alle challenges">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="m15 18-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
            Alle challenges
        </a>

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
                    <?= htmlspecialchars($viewedChallenge['description'], ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>
        </article>
    <?php elseif ($challengeLoadError): ?>
        <a href="/project-in-beweging/?screen=leren"
            class="mb-5 inline-flex items-center gap-2 py-2 text-sm font-bold text-muted transition hover:text-orange">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="m15 18-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
            Terug naar alle challenges
        </a>
        <p class="rounded-[18px] bg-surface p-4 text-sm font-semibold text-muted shadow-card" role="alert">
            De challenge kon niet worden geladen. Probeer het later opnieuw.
        </p>
    <?php elseif ($requestedChallengeId !== false && $requestedChallengeId !== null): ?>
        <a href="/project-in-beweging/?screen=leren"
            class="mb-5 inline-flex items-center gap-2 py-2 text-sm font-bold text-muted transition hover:text-orange">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="m15 18-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
            Terug naar alle challenges
        </a>
        <p class="rounded-[18px] bg-surface p-4 text-sm font-semibold text-muted" role="status">
            Deze challenge is niet gevonden.
        </p>
    <?php else: ?>
        <header class="mb-5 pt-2">
            <p class="mb-1 text-sm font-bold text-orange">Leren &amp; Groeien</p>
            <h1 class="text-2xl font-extrabold tracking-tight">Challenges</h1>
            <p class="mt-1 text-sm text-muted">Kies een challenge en ontdek wat je kunt doen.</p>
        </header>

        <?php if ($challengeLoadError): ?>
            <p class="rounded-[18px] bg-surface p-4 text-sm font-semibold text-muted shadow-card" role="alert">
                De challenges konden niet worden geladen. Probeer het later opnieuw.
            </p>
        <?php elseif ($challenges === []): ?>
            <p class="rounded-[18px] bg-surface p-4 text-sm font-semibold text-muted shadow-card">
                Er zijn nog geen challenges beschikbaar.
            </p>
        <?php else: ?>
            <ul class="space-y-4">
                <?php foreach ($challenges as $challenge): ?>
                    <li>
                        <a href="/project-in-beweging/?screen=leren&amp;challenge=<?= (int) $challenge['id'] ?>"
                            class="flex items-center gap-4 rounded-[20px] bg-surface p-3 shadow-card transition hover:-translate-y-0.5 hover:shadow-lg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange">
                            <?php if (!empty($challenge['img'])): ?>
                                <img src="<?= htmlspecialchars($challenge['img'], ENT_QUOTES, 'UTF-8') ?>"
                                    alt=""
                                    class="h-20 w-20 shrink-0 rounded-[14px] object-cover" loading="lazy">
                            <?php else: ?>
                                <span
                                    class="grid h-20 w-20 shrink-0 place-items-center rounded-[14px] bg-orange-soft text-orange"
                                    aria-hidden="true">
                                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none">
                                        <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8" />
                                        <path d="M10 8.5v7l6-3.5-6-3.5z" fill="currentColor" />
                                    </svg>
                                </span>
                            <?php endif; ?>

                            <span class="min-w-0 flex-1">
                                <span class="mb-1 block text-xs font-bold uppercase tracking-wide text-orange">
                                    <?= htmlspecialchars($challenge['difficulty'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <span class="block truncate text-base font-extrabold tracking-tight">
                                    <?= htmlspecialchars($challenge['title'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <span class="mt-1 block text-xs font-medium text-muted">
                                    <?= (int) $challenge['kcal_burned'] ?> kcal
                                    <span aria-hidden="true">•</span>
                                    <?= (int) $challenge['xp_reward'] ?> XP
                                </span>
                            </span>

                            <svg class="shrink-0 text-muted" width="20" height="20" viewBox="0 0 24 24" fill="none"
                                aria-hidden="true">
                                <path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php endif; ?>
</main>