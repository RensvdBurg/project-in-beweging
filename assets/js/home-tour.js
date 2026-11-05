(() => {
  const home = document.getElementById('screen-home');

  if (!home) {
    return;
  }

  const userId = home.dataset.userId;
  const completedKey = `project-in-beweging:home-tour-completed:${userId}`;
  let tourStarted = false;

  const startTourIfNeeded = () => {
    if (
      tourStarted ||
      home.classList.contains('hidden') ||
      localStorage.getItem(completedKey)
    ) {
      return;
    }

    const createDriver = window.driver?.js?.driver;
    if (typeof createDriver !== 'function') {
      console.error('Driver.js kon niet worden geladen; de home-rondleiding is niet beschikbaar.');
      return;
    }

    const selectors = [
      {
        element: '#home-tour-welcome',
        title: 'Welkom bij UpMove!',
        description: 'Hier zie je je naam en hoeveel XP je al hebt verdiend.',
      },
      {
        element: '#home-tour-challenge',
        title: 'Jouw opdracht van dit moment',
        description: 'Bekijk de beweegopdracht en rond hem af om XP te verdienen.',
      },
      {
        element: '#home-tour-timer',
        title: 'De volgende opdracht',
        description: 'Hier zie je wanneer er weer een nieuwe opdracht klaarstaat.',
      },
      {
        element: '.activity-nav-button',
        title: 'Activiteiten',
        description: 'Bekijk en deel activiteiten met anderen.',
      },
      {
        element: '#activity-feed',
        title: 'Samen in beweging',
        description: 'Ontdek wat andere UpMove-gebruikers hebben gedaan.',
      },
      {
        element: '.upmove-footer',
        title: 'De navigatie',
        description: 'Via deze balk ga je naar Home, de ranglijst, Leren en Statistieken.',
      },
    ];

    const steps = selectors
      .filter(({ element }) => document.querySelector(element))
      .map(({ element, title, description }) => ({
        element,
        popover: { title, description },
      }));

    if (steps.length === 0) {
      return;
    }

    tourStarted = true;
    const tour = createDriver({
      steps,
      popoverClass: 'home-tour-popover',
      showProgress: true,
      nextBtnText: 'Volgende',
      prevBtnText: 'Vorige',
      doneBtnText: 'Klaar',
      closeBtnLabel: 'Rondleiding sluiten',
      onDestroyed: () => localStorage.setItem(completedKey, 'true'),
    });

    tour.drive();
  };

  startTourIfNeeded();
  window.addEventListener('upmove:screen-changed', startTourIfNeeded);
})();
