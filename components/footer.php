<?php

$currentPath = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');

$isSettingsPage = str_contains(
  $currentPath,
  '/pages/settings/settings.php'
);

$allowedScreens = [
  'home',
  'bewegen',
  'leren',
  'stats'
];

$currentScreen = $_GET['screen'] ?? 'home';

if (!in_array($currentScreen, $allowedScreens, true)) {
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
  class="upmove-footer fixed bottom-0 left-0 right-0 z-50 border-t border-[#eceef2] bg-white/95 backdrop-blur-md"
  aria-label="Hoofdnavigatie">
  <div
    class="mx-auto grid max-w-lg grid-cols-5 px-1.5
               pb-[max(0.75rem,env(safe-area-inset-bottom))] pt-2">

    <!-- HOME -->
    <a
      href="/project-in-beweging/?screen=home"
      class="<?= footerItemClass(!$isSettingsPage && $currentScreen === 'home') ?>"
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


    <!-- BEWEGEN -->
    <a
      href="/project-in-beweging/?screen=bewegen"
      class="<?= footerItemClass(!$isSettingsPage && $currentScreen === 'bewegen') ?>"
      data-screen="bewegen">
      <svg
        width="22"
        height="22"
        viewBox="0 0 24 24"
        fill="none"
        aria-hidden="true">
        <circle
          cx="12"
          cy="12"
          r="9"
          stroke="currentColor"
          stroke-width="1.8" />

        <path
          d="M10 8.5v7l6-3.5-6-3.5z"
          fill="currentColor" />
      </svg>

      <span>Bewegen</span>
    </a>


    <!-- LEREN -->
    <a
      href="/project-in-beweging/?screen=leren"
      class="<?= footerItemClass(!$isSettingsPage && $currentScreen === 'leren') ?>"
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
      href="/project-in-beweging/?screen=stats"
      class="<?= footerItemClass(!$isSettingsPage && $currentScreen === 'stats') ?>"
      data-screen="stats">
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
      href="/project-in-beweging/pages/settings/settings.php"
      class="<?= footerItemClass($isSettingsPage) ?>"
      <?= $isSettingsPage ? 'aria-current="page"' : '' ?>>
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


<script>
  (() => {

    const navItems =
      document.querySelectorAll('.nav-item[data-screen]');

    const screens =
      document.querySelectorAll('.screen');


    /*
     * Op Home kunnen we zonder reload tussen
     * Home, Bewegen, Leren en Statistieken wisselen.
     *
     * Op settings.php bestaan deze schermen niet,
     * dus werkt de href gewoon als normale link.
     */
    function openScreen(screenName, updateUrl = true) {

      const target =
        document.getElementById(`screen-${screenName}`);

      if (!target) {
        return false;
      }


      screens.forEach((screen) => {

        screen.classList.toggle(
          'hidden',
          screen !== target
        );

      });


      navItems.forEach((item) => {

        const active =
          item.dataset.screen === screenName;

        item.classList.toggle(
          'text-orange',
          active
        );

        item.classList.toggle(
          'text-[#9aa0a8]',
          !active
        );


        if (active) {

          item.setAttribute(
            'aria-current',
            'page'
          );

        } else {

          item.removeAttribute(
            'aria-current'
          );

        }

      });


      /*
       * URL veranderen naar bijvoorbeeld:
       *
       * ?screen=bewegen
       *
       * Daardoor kan je dezelfde pagina
       * ook rechtstreeks openen.
       */
      if (updateUrl) {

        const url =
          new URL(window.location.href);

        url.searchParams.set(
          'screen',
          screenName
        );

        history.replaceState({},
          '',
          url
        );

      }

      return true;
    }


    navItems.forEach((item) => {

      item.addEventListener('click', (event) => {

        const screenName =
          item.dataset.screen;

        /*
         * Bestaat het scherm op deze pagina?
         * Dan blijven we op dezelfde pagina.
         */
        if (
          document.getElementById(
            `screen-${screenName}`
          )
        ) {

          event.preventDefault();

          openScreen(
            screenName,
            true
          );

        }

        /*
         * Bestaat het scherm niet?
         * Bijvoorbeeld als we op settings.php zitten.
         *
         * Dan wordt event.preventDefault NIET uitgevoerd
         * en volgt de browser gewoon de href.
         */

      });

    });


    /*
     * Bij het openen van de homepage kijken
     * of bijvoorbeeld ?screen=leren is meegegeven.
     */
    if (screens.length > 0) {

      const params =
        new URLSearchParams(
          window.location.search
        );

      const requestedScreen =
        params.get('screen') || 'home';


      if (
        document.getElementById(
          `screen-${requestedScreen}`
        )
      ) {

        openScreen(
          requestedScreen,
          false
        );

      } else {

        openScreen(
          'home',
          false
        );

      }

    }

  })();
</script>