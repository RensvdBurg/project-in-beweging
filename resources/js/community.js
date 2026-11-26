/*
 * "Samen in beweging": activiteitkaarten (home, /community en profielpagina's).
 *
 * - Reactiepaneel open- en dichtklappen: de knop heeft data-comments-toggle en
 *   verwijst via aria-controls naar het paneel.
 * - Liken, reageren en een reactie verwijderen zonder dat de pagina herlaadt:
 *   het formulier wordt via fetch verstuurd en de server stuurt de bijgewerkte
 *   kaart terug (zelfde Blade-component), die op dezelfde plek wordt vervangen.
 *   Lukt dat niet, dan wordt het formulier gewoon verstuurd zoals vroeger.
 *
 * Alles via event delegation, zodat het ook werkt op vervangen kaarten.
 */
(() => {
  document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-comments-toggle]');

    if (!button) {
      return;
    }

    const panel = document.getElementById(button.getAttribute('aria-controls'));

    if (!panel) {
      return;
    }

    const open = panel.hidden;
    panel.hidden = !open;
    button.setAttribute('aria-expanded', String(open));

    if (open) {
      panel.querySelector('input[name="body"]')?.focus();
    }
  });

  document.addEventListener('submit', async (event) => {
    const form = event.target;
    const card = form.closest('[data-community-card]');

    if (!card || form.dataset.sending === 'true') {
      if (card) {
        event.preventDefault();
      }
      return;
    }

    event.preventDefault();
    form.dataset.sending = 'true';

    const panel = card.querySelector('[id^="reacties-"]');
    const isCommentForm = form.querySelector('input[name="body"]') !== null;
    const data = new FormData(form);
    data.set('profile_from', card.dataset.profileFrom || 'home');

    if (panel && !panel.hidden) {
      data.set('open_comments', '1');
    }

    try {
      const response = await fetch(form.action, {
        method: 'POST', // _method (bijv. DELETE) zit in de FormData
        body: data,
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
      });

      if (!response.ok && response.status !== 422) {
        throw new Error(`HTTP ${response.status}`);
      }

      const { html } = await response.json();
      const template = document.createElement('template');
      template.innerHTML = html.trim();
      const newCard = template.content.firstElementChild;

      if (!newCard) {
        throw new Error('Lege kaart');
      }

      card.replaceWith(newCard);

      // Na een reactie (of een fout) direct verder kunnen typen.
      if (isCommentForm) {
        newCard.querySelector('input[name="body"]')?.focus({ preventScroll: true });
      }
    } catch {
      // Terugvallen op het gewone formulier (pagina herlaadt dan wel).
      delete form.dataset.sending;
      form.submit();
    }
  });
})();
