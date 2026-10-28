<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}


/*
 * home.php hoort via de hoofdapp geladen te worden.
 * Niet ingelogd = terug naar index.
 */
if (
  realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__
  ||
  empty($_SESSION['user'])
) {

  header(
    'Location: ../../index.php',
    true,
    303
  );

  exit;
}


$dailyChallengeChangeLimit = 2;
$today = date('Y-m-d');
$challengeUsageKey = 'manual_challenge_changes_' . (int) $_SESSION['user']['id'];
$challengeUsage = $_SESSION[$challengeUsageKey] ?? [];
$challengeChangesUsed = is_array($challengeUsage) && ($challengeUsage['date'] ?? null) === $today
  ? min($dailyChallengeChangeLimit, max(0, (int) ($challengeUsage['count'] ?? 0)))
  : 0;
$challengeChangesRemaining = $dailyChallengeChangeLimit - $challengeChangesUsed;
$challengeNotice = $_SESSION['challenge_notice'] ?? '';
$challengeCompletionNotice = $_SESSION['challenge_completion_notice'] ?? '';
unset($_SESSION['challenge_notice'], $_SESSION['challenge_completion_notice']);

require_once __DIR__ . '/../../database/database.php';
require_once __DIR__ . '/../../database/activity-posts.php';

$challengeIntervalSeconds = 90 * 60;
$challengeInterval = intdiv(time(), $challengeIntervalSeconds);
$challengeChangeAt = ($challengeInterval + 1) * $challengeIntervalSeconds;
$noDifferentChallengeAvailable = false;

try {
  $db = getDatabaseConnection();
  $userXpStatement = $db->prepare('SELECT xp FROM users WHERE id = :user_id');
  $userXpStatement->execute(['user_id' => (int) $_SESSION['user']['id']]);
  $currentUserXp = $userXpStatement->fetchColumn();
  if ($currentUserXp !== false) {
    $_SESSION['user']['xp'] = (int) $currentUserXp;
  }

  $challengeStatement = $db->query(
    'SELECT id, title, description, img, kcal_burned, difficulty, xp_reward
         FROM challenges
         ORDER BY id'
  );
  $challenges = $challengeStatement->fetchAll();
  $selectedChallenge = null;
  if ($challenges !== []) {
    $savedChallenge = $_SESSION['home_challenge'] ?? null;
    if (is_array($savedChallenge) && ($savedChallenge['interval'] ?? null) === $challengeInterval) {
      foreach ($challenges as $challenge) {
        if ((int) $challenge['id'] === (int) ($savedChallenge['id'] ?? 0)) {
          $selectedChallenge = $challenge;
          break;
        }
      }
    }

    if ($selectedChallenge === null) {
      $previousChallengeId = is_array($savedChallenge) ? (int) ($savedChallenge['id'] ?? 0) : 0;
      $challengeOptions = $challenges;
      if ($previousChallengeId !== 0) {
        $challengeOptions = array_values(array_filter(
          $challengeOptions,
          static fn(array $challenge): bool => (int) $challenge['id'] !== $previousChallengeId
        ));
      }
      if ($challengeOptions === []) {
        $noDifferentChallengeAvailable = true;
      } else {
        $selectedChallenge = $challengeOptions[random_int(0, count($challengeOptions) - 1)];
        $_SESSION['home_challenge'] = [
          'interval' => $challengeInterval,
          'id' => (int) $selectedChallenge['id'],
        ];
      }
    }
  }

  $challengeCompleted = false;
  if ($selectedChallenge !== null) {
    $completionStatement = $db->prepare(
      'SELECT 1 FROM challenge_completions
       WHERE user_id = :user_id AND challenge_id = :challenge_id
         AND challenge_interval = :challenge_interval'
    );
    $completionStatement->execute([
      'user_id' => (int) $_SESSION['user']['id'],
      'challenge_id' => (int) $selectedChallenge['id'],
      'challenge_interval' => $challengeInterval,
    ]);
    $challengeCompleted = $completionStatement->fetchColumn() !== false;
  }

  $challengeLoadError = false;
} catch (PDOException $exception) {
  error_log('Challenges/databasefout: ' . $exception->getMessage());
  $challenges = [];
  $selectedChallenge = null;
  $challengeLoadError = true;
}

$activityPageSize = 5;
$requestedActivityPage = filter_input(
  INPUT_GET,
  'activity_page',
  FILTER_VALIDATE_INT,
  ['options' => ['min_range' => 1]]
);
$activityPage = is_int($requestedActivityPage) ? $requestedActivityPage : 1;
$activityPosts = [];
$activityPageCount = 1;
$activityFeedError = false;

try {
  $activityDb = getDatabaseConnection();
  ensureActivityPostsTable($activityDb);
  $activityCountStatement = $activityDb->query('SELECT COUNT(*) FROM activity_posts');
  $activityCount = (int) $activityCountStatement->fetchColumn();
  $activityPageCount = max(1, (int) ceil($activityCount / $activityPageSize));
  $activityPage = min($activityPage, $activityPageCount);
  $activityOffset = ($activityPage - 1) * $activityPageSize;

  $activityStatement = $activityDb->prepare(
    'SELECT activity_posts.title, activity_posts.description,
            activity_posts.image_filename, activity_posts.created_at,
            COALESCE(users.full_name, users.username, "UpMove-gebruiker") AS author_name
     FROM activity_posts
     LEFT JOIN users ON users.id = activity_posts.user_id
     ORDER BY activity_posts.created_at DESC, activity_posts.id DESC
     LIMIT :limit OFFSET :offset'
  );
  $activityStatement->bindValue(':limit', $activityPageSize, PDO::PARAM_INT);
  $activityStatement->bindValue(':offset', $activityOffset, PDO::PARAM_INT);
  $activityStatement->execute();
  $activityPosts = $activityStatement->fetchAll();
} catch (Throwable $exception) {
  error_log('Activiteitenfeed/databasefout: ' . $exception->getMessage());
  $activityFeedError = true;
}

$escapeActivityFeed = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$challengeCompletionSucceeded = str_starts_with($challengeCompletionNotice, 'Opdracht voltooid!');
?>
<div
  class="screen<?= (($_GET['screen'] ?? 'home') === 'home') ? '' : ' hidden' ?> mx-auto flex min-h-dvh w-full max-w-lg flex-col"
  id="screen-home"
  data-user-id="<?= (int) $_SESSION['user']['id'] ?>"
  data-challenge-id="<?= $selectedChallenge === null ? '' : (int) $selectedChallenge['id'] ?>"
  data-challenge-interval="<?= $selectedChallenge === null ? '' : $challengeInterval ?>"
  data-challenge-change-at="<?= $selectedChallenge === null ? '' : $challengeChangeAt * 1000 ?>"
  data-challenge-title="<?= $selectedChallenge === null ? '' : htmlspecialchars($selectedChallenge['title'], ENT_QUOTES, 'UTF-8') ?>">

  <main class="flex-1 overflow-y-auto px-4 pb-28 pt-[max(1rem,env(safe-area-inset-top))] sm:px-6">

<div class="mx-auto flex min-h-dvh w-full max-w-lg flex-col">


    <header class="home-motion mb-4 flex items-start justify-between gap-3 sm:mb-5">
      <div class="min-w-0">
        <p class="mb-0.5 text-sm font-medium text-muted">Welkom terug</p>
        <h1 class="text-2xl font-extrabold tracking-tight sm:text-[1.75rem]">
          Hé, <?= htmlspecialchars($_SESSION['user']['full_name'], ENT_QUOTES, 'UTF-8') ?>
        </h1>
        <p class="text-sm font-bold text-orange">Totaal: <?= (int) ($_SESSION['user']['xp'] ?? 0) ?> XP</p>
      </div>
      <a
        href="/project-in-beweging/pages/activiteit/activiteit.php"
        class="home-action inline-flex shrink-0 items-center gap-2 rounded-full bg-orange px-4 py-2.5 text-sm font-extrabold text-ink shadow-[0_6px_16px_rgba(255,107,74,0.28)] hover:brightness-95"
      >
        Activiteiten
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round"
            stroke-linejoin="round" />
        </svg>
      </a>
    </header>

    <div id="alert-card"
      class="alert-card home-motion mb-4 flex w-full items-center gap-3 rounded-[18px] border-[1.5px] border-alert-border bg-alert-bg p-3.5 text-left sm:p-4"
      role="alert">
      <span class="grid h-[42px] w-[42px] shrink-0 place-items-center rounded-xl bg-alert-icon" aria-hidden="true">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
          <path d="M12 3.5L21.5 20H2.5L12 3.5Z" fill="#1A1A1A" />
          <rect x="11" y="9" width="2" height="6" rx="1" fill="#F5D76E" />
          <circle cx="12" cy="17.5" r="1.2" fill="#F5D76E" />
        </svg>
      </span>
      <span class="flex min-w-0 flex-col gap-0.5">
        <strong id="alert-title" class="text-[15px] font-bold tracking-tight">Te lang gezeten! (60 min)</strong>
        <span class="text-[13px] leading-snug text-card-foreground">Tijd om op te staan en een snelle 2 min stretch te
          doen.</span>
      </span>
      <button type="button" id="btn-dismiss-alert"
        class="home-action cursor-pointer grid h-10 w-10 shrink-0 place-items-center rounded-full bg-white text-teal transition hover:bg-teal-soft active:scale-95"
        aria-label="Melding sluiten voor 60 minuten" title="Sluiten voor 60 minuten">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="m5 12 4.5 4.5L19 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
            stroke-linejoin="round" />
        </svg>
      </button>
    </div>




    <section class="home-motion home-card-interactive mb-3.5 rounded-[22px] bg-surface p-5 shadow-card sm:p-7 sm:pb-5" aria-label="Dagelijkse voortgang">


      <div class="relative mx-auto mb-5 h-[190px] w-[190px] sm:mb-6 sm:h-[210px] sm:w-[210px]">


        <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
          <?php if ($challengeLoadError): ?>
            <p class="text-sm text-muted" role="alert">
              De opdracht kon niet worden geladen. Probeer het later opnieuw.
            </p>
          <?php elseif ($selectedChallenge === null): ?>
            <p class="text-sm text-muted">
              <?= $noDifferentChallengeAvailable
                ? 'Er is geen andere opdracht beschikbaar.'
                : 'Er is nog geen opdracht beschikbaar.' ?>
            </p>
          <?php else: ?>
            <img src="<?= htmlspecialchars($selectedChallenge['img'], ENT_QUOTES, 'UTF-8') ?>"
              alt="<?= htmlspecialchars($selectedChallenge['title'], ENT_QUOTES, 'UTF-8') ?>"
              class="h-[210px] w-[210px] rounded-full object-cover" loading="lazy">
          </div>
        </div>

        <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2">

          <div class="flex flex-col items-center gap-1 text-center">
            <span class="text-l font-extrabold tracking-tight sm:text-[22px]">
              <?= htmlspecialchars($selectedChallenge['title'], ENT_QUOTES, 'UTF-8') ?>
            </span>
            <span class="text-[13px] font-bold text-muted">Moeilijkheid:
              <?= htmlspecialchars($selectedChallenge['difficulty'], ENT_QUOTES, 'UTF-8') ?></span>
          </div>

          <div class="h-9 w-px bg-[#e6e8ec]" aria-hidden="true"></div>

          <div class="flex flex-col items-center gap-1 text-center">
            <span
              class="text-l font-extrabold tracking-tight sm:text-[22px]"><?= (int) $selectedChallenge['kcal_burned'] ?>
              kcal</span>
            <span class="text-[13px] font-bold text-muted"><?= (int) $selectedChallenge['xp_reward'] ?> XP te
              verdienen</span>
          </div>
        </div>
      <?php endif; ?>

      <div class="my-4 h-px bg-[#e6e8ec]" aria-hidden="true"></div>

      <?php if ($selectedChallenge !== null && !$challengeLoadError): ?>
        <form method="post" class="space-y-3">
          <input type="hidden" name="action" value="complete_challenge">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
          <button type="submit"
            class="home-action w-full rounded-xl bg-teal px-4 py-3 text-sm font-extrabold text-white transition hover:brightness-95 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-60 <?= $challengeCompletionSucceeded ? 'completion-button--success' : '' ?>"
            <?= $challengeCompleted ? 'disabled' : '' ?>>
            <span class="inline-flex items-center justify-center gap-2">
              <?php if ($challengeCompletionSucceeded): ?>
                <svg class="completion-button-check" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="m5 12 4.5 4.5L19 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Lekker bezig!
              <?php else: ?>
                Voltooid
              <?php endif; ?>
            </span>
          </button>
          <?php if ($challengeCompletionNotice !== ''): ?>
            <p class="completion-feedback rounded-xl bg-white/70 px-3 py-2 text-sm font-semibold text-ink <?= $challengeCompletionSucceeded ? 'completion-feedback--success flex items-center gap-2' : '' ?>" role="status">
              <?php if ($challengeCompletionSucceeded): ?>
                <span class="completion-check" aria-hidden="true">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                    <path d="m5 12 4.5 4.5L19 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
                  </svg>
                </span>
              <?php endif; ?>
              <span><?= htmlspecialchars($challengeCompletionNotice, ENT_QUOTES, 'UTF-8') ?></span>
            </p>
          <?php endif; ?>
        </form>
      <?php endif; ?>

    </section>


    <section class="home-motion home-card-interactive mb-5 flex items-center gap-3 rounded-[18px] border-2 border-teal bg-surface p-3 sm:p-3.5">

      <div class="grid h-[42px] w-[42px] shrink-0 place-items-center rounded-xl bg-teal-soft" aria-hidden="true">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
          <circle cx="12" cy="13" r="8" stroke="#2EC4B6" stroke-width="2" />
          <path d="M12 9v4l2.5 1.5" stroke="#2EC4B6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
          <path d="M9 3h6" stroke="#2EC4B6" stroke-width="2" stroke-linecap="round" />
        </svg>
      </div>

      <div class="min-w-0 flex-1">
        <p class="text-xs font-medium text-muted">Volgende opdracht</p>
        <p class="text-base font-extrabold tracking-tight" id="next-break">
          <?= intdiv($challengeChangeAt - time(), 60) ?>m <?= sprintf('%02d', ($challengeChangeAt - time()) % 60) ?>s
        </p>
      </div>

    </section>


    <section class="home-motion">
      <h2 class="mb-3 text-lg font-extrabold tracking-tight">Klaar voor de volgende opdracht?</h2>
      <form method="post" class="rounded-[20px] bg-orange-soft p-4 sm:p-[18px]">
        <input type="hidden" name="action" value="next_challenge">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <div class="flex items-center gap-3">
          <div class="min-w-0 flex-1">
            <p class="text-base font-extrabold text-orange">Direct de volgende opdracht starten?</p>
            <p class="text-[13px] leading-snug text-card-foreground">
              Je kunt vandaag nog <?= $challengeChangesRemaining ?> keer direct wisselen.
            </p>
          </div>

          <button type="submit" id="btn-play"
            class="home-action cursor-pointer btn-play grid h-14 w-14 shrink-0 place-items-center rounded-full bg-orange shadow-[0_8px_18px_rgba(255,107,74,0.35)] transition hover:shadow-[0_10px_22px_rgba(255,107,74,0.45)] active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
            aria-label="Laad een andere opdracht" <?= $challengeChangesRemaining === 0 ? 'disabled' : '' ?>>
            <svg class="ml-0.5" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M8 5.5v13l11-6.5L8 5.5z" fill="#fff" />
            </svg>
          </button>
        </div>
        <?php if ($challengeNotice !== ''): ?>
          <p class="mt-3 rounded-xl bg-white/70 px-3 py-2 text-sm font-semibold text-ink" role="status">
            <?= htmlspecialchars($challengeNotice, ENT_QUOTES, 'UTF-8') ?>
          </p>
        <?php endif; ?>
      </form>
      
    </section>

    <section class="home-motion mt-6" id="activity-feed" aria-labelledby="activity-feed-title">
      <header class="mb-3">
        <p class="mb-1 text-sm font-bold text-orange">Samen in beweging</p>
        <h2 id="activity-feed-title" class="text-xl font-extrabold tracking-tight">Activiteiten van iedereen</h2>
        <p class="mt-1 text-sm text-muted">Bekijk wat de UpMove-community allemaal heeft gedaan.</p>
      </header>

      <?php if ($activityFeedError): ?>
        <p class="rounded-[18px] bg-surface p-4 text-sm font-semibold text-muted shadow-card" role="alert">
          De activiteiten konden niet worden geladen. Probeer het later opnieuw.
        </p>
      <?php elseif ($activityPosts === []): ?>
        <p class="rounded-[18px] bg-surface p-4 text-sm font-semibold text-muted shadow-card">
          Er zijn nog geen activiteiten gedeeld. Wees de eerste!
        </p>
      <?php else: ?>
        <ul class="space-y-4">
          <?php foreach ($activityPosts as $activityPost): ?>
            <li>
              <article class="home-card-interactive overflow-hidden rounded-[20px] bg-surface shadow-card">
                <img
                  src="/project-in-beweging/uploads/activiteiten/<?= rawurlencode($activityPost['image_filename']) ?>"
                  alt="<?= $escapeActivityFeed($activityPost['title']) ?>"
                  class="h-48 w-full object-cover"
                  loading="lazy">
                <div class="p-4">
                  <p class="text-xs font-bold text-orange">
                    <?= $escapeActivityFeed($activityPost['author_name']) ?>
                  </p>
                  <h3 class="mt-1 text-base font-extrabold tracking-tight">
                    <?= $escapeActivityFeed($activityPost['title']) ?>
                  </h3>
                  <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-muted">
                    <?= $escapeActivityFeed($activityPost['description']) ?>
                  </p>
                  <time
                    class="mt-3 block text-xs font-medium text-muted"
                    datetime="<?= $escapeActivityFeed(date(DATE_ATOM, strtotime($activityPost['created_at']))) ?>">
                    <?= $escapeActivityFeed(date('d-m-Y H:i', strtotime($activityPost['created_at']))) ?>
                  </time>
                </div>
              </article>
            </li>
          <?php endforeach; ?>
        </ul>

        <?php if ($activityPageCount > 1): ?>
          <nav class="mt-5 flex flex-wrap justify-center gap-2" aria-label="Pagina's met activiteiten">
            <?php for ($page = 1; $page <= $activityPageCount; $page++): ?>
              <a
                href="/project-in-beweging/?screen=home&amp;activity_page=<?= $page ?>#activity-feed"
                class="activity-feed-page-link grid h-10 min-w-10 place-items-center rounded-full px-3 text-sm font-bold transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange <?= $page === $activityPage ? 'bg-orange text-white' : 'bg-surface text-ink shadow-card hover:bg-orange-soft' ?>"
                <?= $page === $activityPage ? 'aria-current="page"' : '' ?>>
                <?= $page ?>
              </a>
            <?php endfor; ?>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    </section>

    <?php if ($selectedChallenge !== null): ?>
      <script
        src="/project-in-beweging/assets/js/task-notifications.js?v=<?= filemtime(__DIR__ . '/../../assets/js/task-notifications.js') ?>"
        defer></script>
    <?php endif; ?>
  </main>

</div>

<script
  src="/project-in-beweging/assets/js/home-page.js?v=<?= filemtime(__DIR__ . '/../../assets/js/home-page.js') ?>"
  defer></script>