<?php

$currentPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');

$isSettingsPage = str_contains(
  $currentPath,
  '/pages/settings/'
);
$isChallengeDescriptionPage = str_contains(
  $currentPath,
  '/pages/learn/learn-beschrijving.php'
);
$isActivityPage = str_contains(
  $currentPath,
  '/pages/activiteit/activiteit.php'
);

$allowedScreens = [
  'home',
  'ranglijst',
  'leren',
  'stats'
];

$currentScreen = $_GET['screen'] ?? ($isChallengeDescriptionPage ? 'leren' : 'home');

if ($isActivityPage) {
  $currentScreen = '';
} elseif (!in_array($currentScreen, $allowedScreens, true)) {
  $currentScreen = 'home';
}

function footerItemClass(bool $active): string
{
  $color = $active
    ? 'text-orange'
    : 'text-[#9aa0a8]';

  return
    'nav-item flex flex-col items-center gap-1 px-0.5 py-1 ' .
    'text-[10px] font-semibold transition active:scale-95 ' .
    $color;
}

?>

<nav
  class="upmove-footer fixed bottom-[calc(0.75rem+env(safe-area-inset-bottom))] left-1/2 z-50 w-[calc(100%-2rem)] max-w-lg -translate-x-1/2 rounded-2xl border border-[#eceef2] bg-white/70 shadow-[0_8px_28px_rgba(20,24,40,0.12)] backdrop-blur-md"
  aria-label="Hoofdnavigatie">
  <div class="mx-auto grid max-w-lg grid-cols-5 px-1.5 py-2">

    <!-- HOME -->
    <a href="/project-in-beweging/?screen=home"
      class="<?= footerItemClass(!$isSettingsPage && $currentScreen === 'home') ?>" data-screen="home">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M3 10.5 12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1v-10.5z" stroke="currentColor"
          stroke-width="1.8" stroke-linejoin="round" />
      </svg>

      <span>Home</span>
    </a>


    <!-- RANGLIJST -->
    <a
      href="/project-in-beweging/?screen=ranglijst"
      class="<?= footerItemClass(!$isSettingsPage && $currentScreen === 'ranglijst') ?>"
      data-screen="ranglijst">
      <svg
        width="22"
        height="22"
        viewBox="0 0 24 24"
        fill="none"
        aria-hidden="true">
        <path
          d="M8 4h8v5a4 4 0 0 1-8 0V4ZM8 6H4v2a4 4 0 0 0 4 4m8-6h4v2a4 4 0 0 1-4 4m-4 1v4m-4 3h8m-6-3h4v3"
          stroke="currentColor"
          stroke-width="1.8"
          stroke-linecap="round"
          stroke-linejoin="round" />
      </svg>

      <span>Ranglijst</span>
    </a>


    <!-- LEREN -->
    <a href="/project-in-beweging/?screen=leren"
      class="<?= footerItemClass(!$isSettingsPage && $currentScreen === 'leren') ?>" data-screen="leren">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path
          d="M4 5.5c2.2-1 4.3-1.2 6.5 0v13c-2.2-1.2-4.3-1-6.5 0v-13zm16 0c-2.2-1-4.3-1.2-6.5 0v13c2.2-1.2 4.3-1 6.5 0v-13z"
          stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" />
      </svg>

      <span>Leren</span>
    </a>


    <!-- STATISTIEKEN -->
    <a href="/project-in-beweging/?screen=stats"
      class="<?= footerItemClass(!$isSettingsPage && $currentScreen === 'stats') ?>" data-screen="stats">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M5 19V11M12 19V5M19 19v-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
      </svg>

      <span>Statistieken</span>
    </a>

    <!-- INSTELLINGEN -->
    <a href="/project-in-beweging/pages/settings/settings.php" class="<?= footerItemClass($isSettingsPage) ?>"
      <?= $isSettingsPage ? 'aria-current="page"' : '' ?>>
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8" />

        <path d="M12 2.5v2.2
                       M12 19.3v2.2
                       M4.2 6.5l1.6 1.6
                       M18.2 15.9l1.6 1.6
                       M2.5 12h2.2
                       M19.3 12h2.2
                       M4.2 17.5l1.6-1.6
                       M18.2 8.1l1.6-1.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
      </svg>

      <span>Instellingen</span>
    </a>

  </div>
</nav>


<script
  src="/project-in-beweging/assets/js/navigation.js?v=<?= filemtime(__DIR__ . '/../assets/js/navigation.js') ?>"></script>