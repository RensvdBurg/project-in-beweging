<?php

require_once __DIR__ . '/../../database/database.php';

// Bewaar de gegevens apart zodat databasefouten een duidelijke melding kunnen tonen.
$rankings = [];
$rankingLoadError = false;
$currentUser = null;
$currentUserRank = null;

try {
    $db = getDatabaseConnection();

    // Haal de ingelogde gebruiker op; die kan buiten de top 10 staan.
    $currentUserStatement = $db->prepare(
        'SELECT username, full_name, COALESCE(xp, 0) AS xp
         FROM users
         WHERE id = :user_id'
    );
    $currentUserStatement->execute(['user_id' => (int) $_SESSION['user']['id']]);
    $currentUser = $currentUserStatement->fetch() ?: null;

    if ($currentUser !== null) {
        // Tel hoeveel gebruikers voor deze gebruiker staan om de echte positie te bepalen.
        // Bij gelijke XP bepalen gebruikersnaam en daarna ID de vaste volgorde.
        $rankStatement = $db->prepare(
            'SELECT COUNT(*) + 1
             FROM users
             WHERE COALESCE(xp, 0) > :current_xp
                OR (
                    COALESCE(xp, 0) = :tie_xp
                    AND (
                        username < :current_username
                        OR (username = :same_username AND id < :user_id)
                    )
                )'
        );
        $rankStatement->execute([
            'current_xp' => (int) $currentUser['xp'],
            'tie_xp' => (int) $currentUser['xp'],
            'current_username' => $currentUser['username'],
            'same_username' => $currentUser['username'],
            'user_id' => (int) $_SESSION['user']['id'],
        ]);
        $currentUserRank = (int) $rankStatement->fetchColumn();
    }

    // De lijst en het podium tonen alleen de tien gebruikers met de meeste XP.
    $statement = $db->query(
        'SELECT id, username, full_name, COALESCE(xp, 0) AS xp
         FROM users
         ORDER BY COALESCE(xp, 0) DESC, username ASC, id ASC
         LIMIT 10'
    );
    $rankings = $statement->fetchAll();
} catch (PDOException $exception) {
    // Log technische details, maar toon gebruikers alleen een algemene foutmelding.
    error_log('Ranglijst/databasefout: ' . $exception->getMessage());
    $rankingLoadError = true;
}

// Er zijn nog geen profielfoto's; gebruik daarom de eerste letters van de naam.
$getAvatarInitials = static function (string $fullName): string {
    $initials = '';
    foreach (preg_split('/\s+/', trim($fullName)) ?: [] as $namePart) {
        if ($namePart !== '') {
            $characters = preg_split('//u', $namePart, -1, PREG_SPLIT_NO_EMPTY);
            $initials .= $characters[0] ?? '?';
            if (preg_match('/\A.{2}/us', $initials)) {
                break;
            }
        }
    }

    return $initials !== '' ? strtoupper($initials) : '?';
};
?>
<!-- Alleen zichtbaar wanneer de gebruiker de ranglijst in de hoofdnavigatie kiest. -->
<main
    class="screen<?= (($_GET['screen'] ?? 'home') === 'ranglijst') ? '' : ' hidden' ?> mx-auto min-h-dvh w-full max-w-lg overflow-y-auto px-4 pb-28 pt-[max(1rem,env(safe-area-inset-top))] sm:px-6"
    id="screen-ranglijst">
    <header class="mb-5 pt-2">
        <p class="mb-1 text-sm font-bold text-orange">Samen in beweging</p>
        <h1 class="text-2xl font-extrabold tracking-tight">Ranglijst</h1>
        <p class="mt-1 text-sm text-muted">Bekijk wie de meeste XP heeft verdiend.</p>
    </header>

    <?php if ($rankingLoadError): ?>
        <p class="rounded-[18px] bg-surface p-4 text-sm font-semibold text-muted shadow-card" role="alert">
            De ranglijst kon niet worden geladen. Probeer het later opnieuw.
        </p>
    <?php elseif ($rankings === []): ?>
        <p class="rounded-[18px] bg-surface p-4 text-sm font-semibold text-muted shadow-card">
            Er staan nog geen deelnemers op de ranglijst.
        </p>
    <?php else: ?>
        <!-- Plaats de tweede gebruiker links, de eerste in het midden en de derde rechts. -->
        <?php
        $podiumLayout = count($rankings) === 1
            ? 'leaderboard-podium--single'
            : (count($rankings) === 2 ? 'leaderboard-podium--two' : '');
        ?>
        <section class="leaderboard-podium <?= $podiumLayout ?>" aria-label="Top drie">
            <?php foreach ([1 => 2, 0 => 1, 2 => 3] as $podiumIndex => $rank): ?>
                <?php if (isset($rankings[$podiumIndex])): ?>
                    <?php $podiumUser = $rankings[$podiumIndex]; ?>
                    <a
                        href="/project-in-beweging/pages/ranglijst/profile.php?id=<?= (int) $podiumUser['id'] ?>"
                        class="leaderboard-podium-card leaderboard-podium-card--<?= $rank ?>"
                        data-reveal-rank="<?= $rank ?>"
                        aria-label="Bekijk het profiel van <?= htmlspecialchars($podiumUser['full_name'], ENT_QUOTES, 'UTF-8') ?>">
                        <span class="leaderboard-podium-rank" aria-label="Positie <?= $rank ?>">#<?= $rank ?></span>
                        <span class="leaderboard-podium-avatar" aria-hidden="true">
                            <?= htmlspecialchars($getAvatarInitials($podiumUser['full_name']), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <span class="leaderboard-podium-name">
                            <?= htmlspecialchars($podiumUser['full_name'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <span class="leaderboard-podium-username">
                            @<?= htmlspecialchars($podiumUser['username'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <span class="leaderboard-xp">
                            <?= (int) $podiumUser['xp'] ?> XP
                        </span>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </section>

        <!-- Het podium bevat posities 1-3; deze genummerde lijst toont de resterende posities. -->
        <section class="rounded-[22px] bg-surface p-4 shadow-card sm:p-5" aria-labelledby="rankingTitle">
            <h2 id="rankingTitle" class="mb-4 text-lg font-extrabold tracking-tight">Top 10</h2>
            <?php if (count($rankings) > 3): ?>
                <ol class="space-y-2">
                    <?php foreach (array_slice($rankings, 3, 7) as $index => $rankedUser): ?>
                        <?php
                        $rank = $index + 4;
                        $isCurrentUser = (int) $rankedUser['id'] === (int) $_SESSION['user']['id'];
                        ?>
                        <li data-reveal-rank="<?= $rank ?>">
                            <a
                                href="/project-in-beweging/pages/ranglijst/profile.php?id=<?= (int) $rankedUser['id'] ?>"
                                class="leaderboard-user-link flex items-center gap-3 rounded-2xl px-3 py-3 <?= $isCurrentUser ? 'bg-orange-soft' : 'bg-app-bg/60' ?>"
                                aria-label="Bekijk het profiel van <?= htmlspecialchars($rankedUser['full_name'], ENT_QUOTES, 'UTF-8') ?>">
                            <span class="leaderboard-rank" role="img" aria-label="Positie <?= $rank ?>">
                                <?= $rank ?>
                            </span>
                            <span class="leaderboard-avatar" aria-hidden="true">
                                <?= htmlspecialchars($getAvatarInitials($rankedUser['full_name']), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-bold">
                                    <?= htmlspecialchars($rankedUser['full_name'], ENT_QUOTES, 'UTF-8') ?>
                                    <?= $isCurrentUser ? '<span class="text-orange">(jij)</span>' : '' ?>
                                </span>
                                <span class="block truncate text-xs text-muted">
                                    @<?= htmlspecialchars($rankedUser['username'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </span>
                            <span class="leaderboard-xp shrink-0">
                                <?= (int) $rankedUser['xp'] ?> XP
                            </span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php else: ?>
                <p class="text-sm text-muted">De overige plekken vullen zich zodra er meer deelnemers zijn.</p>
            <?php endif; ?>
        </section>

        <!-- Toon de eigen positie apart als de ingelogde gebruiker buiten de top 10 valt. -->
        <?php if (
            $currentUser !== null
            && $currentUserRank !== null
            && $currentUserRank > count($rankings)
        ): ?>
            <section class="leaderboard-your-rank" aria-label="Jouw positie" data-reveal-rank="0">
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold uppercase tracking-wide text-orange">Jouw positie</p>
                    <p class="truncate text-base font-extrabold">
                        <?= htmlspecialchars($currentUser['full_name'], ENT_QUOTES, 'UTF-8') ?>
                        <span class="text-sm font-semibold text-muted">
                            @<?= htmlspecialchars($currentUser['username'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </p>
                    <p class="mt-0.5 text-xs text-muted">Je staat buiten de top 10</p>
                </div>
                <span class="leaderboard-your-rank-number">#<?= $currentUserRank ?></span>
                <span class="leaderboard-xp"><?= (int) $currentUser['xp'] ?> XP</span>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</main>
