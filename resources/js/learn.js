/*
 * Leren: zoeken in de challenges (vroeger assets/js/challenge-search.js).
 */
(() => {
  const searchInput = document.getElementById('challenge-search');
  const challengeList = document.getElementById('challenge-list');
  const emptyMessage = document.getElementById('challenge-search-empty');

  if (!(searchInput instanceof HTMLInputElement) || !challengeList || !emptyMessage) {
    return;
  }

  const challenges = Array.from(challengeList.querySelectorAll('li[data-search]'));
  const normalize = (value) => value
    .toLocaleLowerCase('nl')
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '');

  searchInput.addEventListener('input', () => {
    const query = normalize(searchInput.value.trim());
    let visibleCount = 0;

    challenges.forEach((challenge) => {
      const matches = normalize(challenge.dataset.search || '').includes(query);
      challenge.hidden = !matches;
      visibleCount += Number(matches);
    });

    emptyMessage.hidden = visibleCount > 0;
  });
})();

/*
 * Slide-overgang (resources/css/learn-page-transition.css) alleen bij links met
 * data-learn-transition: een challenge openen, "Wat is dit?" en "Alle challenges".
 *
 * Home laat de overgang altijd toe (zodat hij ook werkt als je Leren via de
 * navbar opent zonder herladen); bij alle andere navigaties vanaf deze pagina,
 * zoals het versturen van een formulier, wordt hij overgeslagen.
 */
(() => {
  let learnNavigation = false;

  document.addEventListener('click', (event) => {
    learnNavigation = event.target.closest('a[data-learn-transition]') !== null;
  }, true);

  window.addEventListener('pageswap', (event) => {
    if (event.viewTransition && !learnNavigation) {
      event.viewTransition.skipTransition();
    }

    learnNavigation = false;
  });

  // Terug via de browser (pagina uit de cache): opnieuw beginnen.
  window.addEventListener('pageshow', () => {
    learnNavigation = false;
  });
})();
