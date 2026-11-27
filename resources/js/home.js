/*
 * Aftelklok tot de volgende opdracht.
 * Het tijdstip komt uit data-changes-at (milliseconden) op #next-break.
 * Bij 0 wordt de pagina herladen, zodat de nieuwe opdracht verschijnt.
 */
(() => {
  const countdown = document.getElementById('next-break');
  const challengeChangesAt = Number(countdown?.dataset.changesAt);

  if (!countdown || !Number.isFinite(challengeChangesAt) || challengeChangesAt === 0) {
    return;
  }

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

/*
 * Melding "Te lang gezeten!" verbergen na wegklikken en na de ingestelde
 * herinneringsfrequentie (data-frequency, uit de instellingen) weer tonen.
 *
 * Bewaard wordt het moment van wegklikken; zo telt ook een frequentie die
 * je daarna in de instellingen wijzigt.
 */
(() => {
  const alertCard = document.getElementById('alert-card');
  const dismissButton = document.getElementById('btn-dismiss-alert');
  const alertTitle = document.getElementById('alert-title');

  if (!alertCard || !dismissButton) {
    return;
  }

  const isValidFrequency = (value) =>
    Number.isInteger(value) && value >= 15 && value <= 120 && value % 15 === 0;

  /*
   * Frequentie uit de database (data-frequency), tenzij er in de
   * instellingen een wijziging staat die de server nog niet heeft.
   */
  const pendingFrequency = window.UpMoveSettings?.getPendingSettings()?.frequency;
  const minutes = isValidFrequency(pendingFrequency)
    ? pendingFrequency
    : Number(alertCard.dataset.frequency) || 60;
  const duration = minutes * 60 * 1000;

  if (alertTitle) {
    alertTitle.textContent = `Te lang gezeten! (${minutes} min)`;
  }
  dismissButton.setAttribute('aria-label', `Melding sluiten voor ${minutes} minuten`);
  dismissButton.title = `Sluiten voor ${minutes} minuten`;

  // Terug naar home via de terugknop (pagina uit de cache)? Opnieuw laden,
  // zodat gewijzigde instellingen gelden.
  window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
      window.location.reload();
    }
  });
  const storageKey = `project-in-beweging:alert-dismissed-at:${alertCard.dataset.userId}`;
  let timer;

  const read = () => {
    try {
      return Number(localStorage.getItem(storageKey));
    } catch {
      return NaN;
    }
  };

  const write = (value) => {
    try {
      if (value === null) {
        localStorage.removeItem(storageKey);
      } else {
        localStorage.setItem(storageKey, String(value));
      }
    } catch {
      // localStorage niet beschikbaar: werkt dan alleen tot herladen
    }
  };

  // Oude sleutel (vaste 60 minuten) opruimen.
  try {
    localStorage.removeItem(`project-in-beweging:alert-dismissed-until:${alertCard.dataset.userId}`);
  } catch {
    // negeren
  }

  // Melding schuift weer in beeld (alert-card--appearing, zie app.css).
  const showAlert = () => {
    alertCard.classList.remove('hidden', 'alert-card--dismissing');
    alertCard.classList.add('alert-card--appearing');
    write(null);
  };

  // Bij wegklikken eerst 260 ms wegschuiven (alert-card--dismissing), dan verbergen.
  const hideUntil = (showAt, animateDismissal = false) => {
    window.clearTimeout(timer);

    const hide = () => {
      alertCard.classList.add('hidden');
      alertCard.classList.remove('alert-card--dismissing', 'alert-card--appearing');
      timer = window.setTimeout(showAlert, Math.max(0, showAt - Date.now()));
    };

    if (animateDismissal && !alertCard.classList.contains('hidden')) {
      alertCard.classList.add('alert-card--dismissing');
      timer = window.setTimeout(hide, 260);
      return;
    }

    hide();
  };

  const dismissedAt = read();
  if (Number.isFinite(dismissedAt) && dismissedAt > 0 && dismissedAt + duration > Date.now()) {
    hideUntil(dismissedAt + duration);
  } else {
    write(null);
  }

  dismissButton.addEventListener('click', () => {
    const now = Date.now();
    write(now);
    hideUntil(now + duration, true);
  });
})();
