<?php
  // Instellingen- en accountpagina activeren het item "Instellingen".
  $isSettingsPage = request()->routeIs('settings', 'account');

  $allowedScreens = ['home', 'ranglijst', 'leren', 'stats'];

  // Op een challengebeschrijving is "Leren" actief, op de statistiekenpagina "Statistieken".
  $isStatisticsPage = request()->routeIs('statistics');

  $currentScreen = match (true) {
    request()->routeIs('challenges.show') => 'leren',
    $isStatisticsPage => 'stats',
    default => request()->query('screen', 'home'),
  };

  if (! in_array($currentScreen, $allowedScreens, true)) {
    $currentScreen = 'home';
  }

  $footerItemClass = function (bool $active): string {
    $color = $active
      ? 'text-orange'
      : 'text-[#656c77]';

    return
      'nav-item flex flex-col items-center gap-1 px-0.5 py-1 ' .
      'text-[10px] font-semibold transition active:scale-95 ' .
      $color;
  };
?>

<nav
  class="upmove-footer fixed bottom-[calc(0.75rem+env(safe-area-inset-bottom))] left-1/2 z-50 w-[calc(100%-2rem)] max-w-lg -translate-x-1/2 rounded-2xl border border-[#eceef2] bg-white/70 shadow-[0_8px_28px_rgba(20,24,40,0.12)] backdrop-blur-md"
  aria-label="Hoofdnavigatie">
  <div class="mx-auto grid max-w-lg grid-cols-5 px-1.5 py-2">

    <!-- HOME -->
    <a
      href="<?php echo e(route('home', ['screen' => 'home'])); ?>"
      class="<?php echo e($footerItemClass(! $isSettingsPage && $currentScreen === 'home')); ?>"
      data-screen="home">
      <svg
        width="22"
        height="22"
        viewBox="0 0 24 24"
        fill="none"
        aria-hidden="true">
        <path
          d="M3 10.5 12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1v-10.5z"
          stroke="currentColor"
          stroke-width="1.8"
          stroke-linejoin="round" />
      </svg>

      <span>Home</span>
    </a>


    <!-- RANGLIJST -->
    <a
      href="<?php echo e(route('home', ['screen' => 'ranglijst'])); ?>"
      class="<?php echo e($footerItemClass(! $isSettingsPage && $currentScreen === 'ranglijst')); ?>"
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
    <a
      href="<?php echo e(route('home', ['screen' => 'leren'])); ?>"
      class="<?php echo e($footerItemClass(! $isSettingsPage && $currentScreen === 'leren')); ?>"
      data-screen="leren">
      <svg
        width="22"
        height="22"
        viewBox="0 0 24 24"
        fill="none"
        aria-hidden="true">
        <path
          d="M4 5.5c2.2-1 4.3-1.2 6.5 0v13c-2.2-1.2-4.3-1-6.5 0v-13zm16 0c-2.2-1-4.3-1.2-6.5 0v13c2.2-1.2 4.3-1 6.5 0v-13z"
          stroke="currentColor"
          stroke-width="1.8"
          stroke-linejoin="round" />
      </svg>

      <span>Leren</span>
    </a>


    <!-- STATISTIEKEN -->
    <a
      href="<?php echo e(route('statistics')); ?>"
      class="<?php echo e($footerItemClass(! $isSettingsPage && $currentScreen === 'stats')); ?>"
      data-screen="stats"
      <?php if($isStatisticsPage): ?> aria-current="page" <?php endif; ?>>
      <svg
        width="22"
        height="22"
        viewBox="0 0 24 24"
        fill="none"
        aria-hidden="true">
        <path
          d="M5 19V11M12 19V5M19 19v-7"
          stroke="currentColor"
          stroke-width="1.8"
          stroke-linecap="round" />
      </svg>

      <span>Statistieken</span>
    </a>


    <!-- INSTELLINGEN -->
    <a
      href="<?php echo e(route('settings')); ?>"
      class="<?php echo e($footerItemClass($isSettingsPage)); ?>"
      <?php if($isSettingsPage): ?> aria-current="page" <?php endif; ?>>
      <svg
        width="22"
        height="22"
        viewBox="0 0 24 24"
        fill="none"
        aria-hidden="true">
        <circle
          cx="12"
          cy="12"
          r="3"
          stroke="currentColor"
          stroke-width="1.8" />

        <path
          d="M12 2.5v2.2
                       M12 19.3v2.2
                       M4.2 6.5l1.6 1.6
                       M18.2 15.9l1.6 1.6
                       M2.5 12h2.2
                       M19.3 12h2.2
                       M4.2 17.5l1.6-1.6
                       M18.2 8.1l1.6-1.6"
          stroke="currentColor"
          stroke-width="1.8"
          stroke-linecap="round" />
      </svg>

      <span>Instellingen</span>
    </a>

  </div>
</nav>
<?php /**PATH C:\xampp\htdocs\project-in-beweging\resources\views/components/footer-nav.blade.php ENDPATH**/ ?>