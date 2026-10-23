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


/*
 * CSRF token voor bijvoorbeeld uitloggen.
 */
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$homeCsrfToken = $_SESSION['csrf_token'];
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

$challengeIntervalSeconds = 90 * 60;
$challengeInterval = intdiv(time(), $challengeIntervalSeconds);
$challengeChangeAt = ($challengeInterval + 1) * $challengeIntervalSeconds;

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
      if (count($challengeOptions) > 1 && $previousChallengeId !== 0) {
        $challengeOptions = array_values(array_filter(
          $challengeOptions,
          static fn(array $challenge): bool => (int) $challenge['id'] !== $previousChallengeId
        ));
      }
      $selectedChallenge = $challengeOptions[random_int(0, count($challengeOptions) - 1)];
      $_SESSION['home_challenge'] = [
        'interval' => $challengeInterval,
        'id' => (int) $selectedChallenge['id'],
      ];
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
?>
<div class="mx-auto flex min-h-dvh w-full max-w-lg flex-col">

  <main class="screen flex-1 overflow-y-auto px-4 pb-28 pt-[max(1rem,env(safe-area-inset-top))] sm:px-6"
    id="screen-home">

    <header class="mb-4 flex flex-wrap items-start justify-between gap-3 sm:mb-5">
      <div class="min-w-0">
        <p class="mb-0.5 text-sm font-medium text-muted">Welkom terug</p>
        <h1 class="text-2xl font-extrabold tracking-tight sm:text-[1.75rem]">
          Hé, <?= htmlspecialchars($_SESSION['user']['full_name'], ENT_QUOTES, 'UTF-8') ?> 👋
        </h1>
        <p class="text-sm font-bold text-orange">Totaal: <?= (int) ($_SESSION['user']['xp'] ?? 0) ?> XP</p>
      </div>
      <form method="post" class="mb-4 flex justify-end">
        <input type="hidden" name="action" value="logout">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($homeCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit"
          class="rounded-full px-3 py-2 text-xs font-bold text-white hover:bg-[#e02e06] bg-[#ff6b4a] cursor-pointer">Uitloggen</button>
      </form>
    </header>

    <div id="alert-card"
      class="alert-card mb-4 flex w-full items-center gap-3 rounded-[18px] border-[1.5px] border-alert-border bg-alert-bg p-3.5 text-left sm:p-4"
      role="alert">
      <span class="grid h-[42px] w-[42px] shrink-0 place-items-center rounded-xl bg-alert-icon" aria-hidden="true">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
          <path d="M12 3.5L21.5 20H2.5L12 3.5Z" fill="#1A1A1A" />
          <rect x="11" y="9" width="2" height="6" rx="1" fill="#F5D76E" />
          <circle cx="12" cy="17.5" r="1.2" fill="#F5D76E" />
        </svg>
      </span>
      <span class="flex min-w-0 flex-col gap-0.5">
        <strong class="text-[15px] font-bold tracking-tight">Te lang gezeten! (60 min)</strong>
        <span class="text-[13px] leading-snug text-[#5c616a]">Tijd om op te staan en een snelle 2 min stretch te
          doen.</span>
      </span>
      <button type="button" id="btn-dismiss-alert"
        class="cursor-pointer grid h-10 w-10 shrink-0 place-items-center rounded-full bg-white text-teal transition hover:bg-teal-soft active:scale-95"
        aria-label="Melding sluiten voor 60 minuten" title="Sluiten voor 60 minuten">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="m5 12 4.5 4.5L19 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
            stroke-linejoin="round" />
        </svg>
      </button>
    </div>




    <section class="mb-3.5 rounded-[22px] bg-surface p-5 shadow-card sm:p-7 sm:pb-5" aria-label="Dagelijkse voortgang">


      <div class="relative mx-auto mb-5 h-[190px] w-[190px] sm:mb-6 sm:h-[210px] sm:w-[210px]">


        <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
          <?php if ($challengeLoadError): ?>
            <p class="text-sm text-muted" role="alert">
              De opdracht kon niet worden geladen. Probeer het later opnieuw.
            </p>
          <?php elseif ($selectedChallenge === null): ?>
            <p class="text-sm text-muted">Er is nog geen opdracht beschikbaar.</p>
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
          <input type="hidden" name="csrf_token"
            value="<?= htmlspecialchars($homeCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
          <button type="submit"
            class="w-full rounded-xl bg-teal px-4 py-3 text-sm font-extrabold text-white transition hover:brightness-95 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-60"
            <?= $challengeCompleted ? 'disabled' : '' ?>>
            Voltooid
          </button>
          <?php if ($challengeCompletionNotice !== ''): ?>
            <p class="rounded-xl bg-white/70 px-3 py-2 text-sm font-semibold text-ink" role="status">
              <?= htmlspecialchars($challengeCompletionNotice, ENT_QUOTES, 'UTF-8') ?>
            </p>
          <?php endif; ?>
        </form>
      <?php endif; ?>

    </section>


    <section class="mb-5 flex items-center gap-3 rounded-[18px] border-2 border-teal bg-surface p-3 sm:p-3.5">

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


    <section>
      <h2 class="mb-3 text-lg font-extrabold tracking-tight">Klaar voor de volgende opdracht?</h2>
      <form method="post" class="rounded-[20px] bg-orange-soft p-4 sm:p-[18px]">
        <input type="hidden" name="action" value="next_challenge">
        <input type="hidden" name="csrf_token"
          value="<?= htmlspecialchars($homeCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <div class="flex items-center gap-3">
          <div class="min-w-0 flex-1">
            <p class="text-base font-extrabold text-orange">Direct de volgende opdracht starten?</p>
            <p class="text-[13px] leading-snug text-[#6a707a]">
              Je kunt vandaag nog <?= $challengeChangesRemaining ?> keer direct wisselen.
            </p>
          </div>

          <button type="submit" id="btn-play"
            class="cursor-pointer btn-play grid h-14 w-14 shrink-0 place-items-center rounded-full bg-orange shadow-[0_8px_18px_rgba(255,107,74,0.35)] transition hover:shadow-[0_10px_22px_rgba(255,107,74,0.45)] active:scale-95 disabled:cursor-not-allowed disabled:opacity-50"
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

    <?php if ($selectedChallenge !== null): ?>
      <script>
        (() => {
          const countdown = document.getElementById('next-break');
          const challengeChangesAt = <?= $challengeChangeAt * 1000 ?>;
          let countdownInterval;

          const updateCountdown = () => {
            const secondsRemaining = Math.max(0, Math.ceil((challengeChangesAt - Date.now()) / 1000));
            const minutes = Math.floor(secondsRemaining / 60);
            const seconds = String(secondsRemaining % 60).padStart(2, '0');
            countdown.textContent = `${minutes}m ${seconds}s`;

            if (secondsRemaining === 0) {
              window.clearInterval(countdownInterval);
              window.location.reload();
            }
          };

          updateCountdown();
          countdownInterval = window.setInterval(updateCountdown, 1000);
        })();
      </script>
    <?php endif; ?>
  </main>

</div>

<script>
  (() => {
    const alertCard = document.getElementById('alert-card');
    const dismissButton = document.getElementById('btn-dismiss-alert');
    const storageKey = 'project-in-beweging:alert-dismissed-until:<?= (int) $_SESSION['user']['id'] ?>';
    const duration = 60 * 60 * 1000; // 1 uur in milliseconden
    let timer;

    const showAlert = () => {
      alertCard.classList.remove('hidden');
      localStorage.removeItem(storageKey);
    };

    const scheduleAlert = (showAt) => {
      alertCard.classList.add('hidden');
      timer = window.setTimeout(showAlert, Math.max(0, showAt - Date.now()));
    };

    const dismissedUntil = Number(localStorage.getItem(storageKey));
    if (Number.isFinite(dismissedUntil) && dismissedUntil > Date.now()) {
      scheduleAlert(dismissedUntil);
    } else {
      localStorage.removeItem(storageKey);
    }

    dismissButton.addEventListener('click', () => {
      window.clearTimeout(timer);
      const showAt = Date.now() + duration;
      localStorage.setItem(storageKey, String(showAt));
      scheduleAlert(showAt);
    });
  })();
</script>
