<?php

require_once __DIR__ . '/../../database/database.php';
require_once __DIR__ . '/challenge-image.php';

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

?>
<main class="screen<?= (($_GET['screen'] ?? 'home') === 'leren') ? '' : ' hidden' ?> mx-auto min-h-dvh w-full max-w-lg overflow-y-auto scroll-smooth px-4 pb-28 pt-[max(1rem,env(safe-area-inset-top))] motion-reduce:scroll-auto sm:px-6"
    id="screen-leren">
    <header class="mb-5 pt-2">
        <p class="mb-1 text-sm font-bold text-orange">Leren &amp; Groeien</p>
        <h1 class="text-2xl font-extrabold tracking-tight">Challenges</h1>
        <p class="mt-1 text-sm text-muted">Kies een challenge en ontdek wat je kunt doen.</p>
        <?php if (!$challengeLoadError && $challenges !== []): ?>
            <label class="sr-only" for="challenge-search">Zoek een challenge</label>
            <div class="relative mt-4">
                <input id="challenge-search" type="search" autocomplete="off"
                    placeholder="Zoek een challenge..."
                    class="w-full rounded-[18px] border border-[#eceef2] bg-surface px-4 py-3 text-left text-sm font-medium shadow-card outline-none transition placeholder:text-muted focus-visible:border-orange focus-visible:ring-2 focus-visible:ring-orange/30">
            </div>
        <?php endif; ?>
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
        <ul class="space-y-4" id="challenge-list">
            <?php foreach ($challenges as $index => $challenge): ?>
                <?php
                $searchText = implode(' ', [
                    $challenge['title'],
                    $challenge['description'],
                    $challenge['difficulty'],
                ]);
                ?>
                <li data-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>">
                    <a href="/project-in-beweging/pages/learn/learn-beschrijving.php?challenge=<?= (int) $challenge['id'] ?>"
                        class="flex items-center gap-4 rounded-[20px] bg-surface p-3 shadow-card transition hover:-translate-y-0.5 hover:shadow-lg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange">
                        <?php if (!empty($challenge['img'])): ?>
                            <img src="<?= htmlspecialchars(getChallengeImageUrl($challenge['img'], 320), ENT_QUOTES, 'UTF-8') ?>"
                                alt=""
                                class="h-20 w-20 shrink-0 rounded-[14px] object-cover"
                                width="80"
                                height="80"
                                loading="<?= $index < 3 ? 'eager' : 'lazy' ?>"
                                decoding="async"
                                <?= $index === 0 ? 'fetchpriority="high"' : '' ?>>
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
        <p class="mt-4 rounded-[18px] bg-surface p-4 text-sm font-semibold text-muted shadow-card" id="challenge-search-empty" hidden>
            Geen challenges gevonden. Probeer een andere zoekterm.
        </p>
        <script src="/project-in-beweging/assets/js/challenge-search.js?v=<?= filemtime(__DIR__ . '/../../assets/js/challenge-search.js') ?>"
            defer></script>
    <?php endif; ?>
</main>
