(() => {
  const home = document.getElementById('screen-home');

  if (!home) {
    return;
  }

  const countdown = document.getElementById('next-break');
  const challengeChangesAt = Number(home.dataset.challengeChangeAt);

  if (countdown && Number.isFinite(challengeChangesAt) && challengeChangesAt > 0) {
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
  }

  const alertCard = document.getElementById('alert-card');
  const dismissButton = document.getElementById('btn-dismiss-alert');
  const alertTitle = document.getElementById('alert-title');

  if (!alertCard || !dismissButton || !alertTitle) {
    return;
  }

  const userId = home.dataset.userId;
  const dismissedUntilKey = `project-in-beweging:alert-dismissed-until:${userId}`;
  const dismissedAtKey = `project-in-beweging:alert-dismissed-at:${userId}`;
  const defaultFrequency = 60;
  let frequency = getReminderFrequency();
  let timer;

  function getReminderFrequency() {
    const savedFrequency = window.upmoveSettings
      ? window.upmoveSettings.frequency
      : null;

    return Number.isInteger(savedFrequency) &&
      savedFrequency >= 15 &&
      savedFrequency <= 120 &&
      savedFrequency % 15 === 0
      ? savedFrequency
      : defaultFrequency;
  }

  const updateAlertText = () => {
    alertTitle.textContent = `Te lang gezeten! (${frequency} min)`;
    dismissButton.setAttribute('aria-label', `Melding sluiten voor ${frequency} minuten`);
    dismissButton.title = `Sluiten voor ${frequency} minuten`;
  };

  const getDuration = () => frequency * 60 * 1000;

  const showAlert = () => {
    alertCard.classList.remove('hidden');
    alertCard.classList.remove('alert-card--dismissing');
    alertCard.classList.add('alert-card--appearing');
    localStorage.removeItem(dismissedUntilKey);
    localStorage.removeItem(dismissedAtKey);
  };

  const scheduleAlert = (showAt, animateDismissal = false) => {
    window.clearTimeout(timer);
    const hideAlert = () => {
      alertCard.classList.add('hidden');
      alertCard.classList.remove('alert-card--dismissing');
      alertCard.classList.remove('alert-card--appearing');
      timer = window.setTimeout(showAlert, Math.max(0, showAt - Date.now()));
    };

    if (animateDismissal && !alertCard.classList.contains('hidden')) {
      alertCard.classList.add('alert-card--dismissing');
      timer = window.setTimeout(hideAlert, 260);
      return;
    }

    hideAlert();
  };

  const dismissedAt = Number(localStorage.getItem(dismissedAtKey));
  if (Number.isFinite(dismissedAt) && dismissedAt > 0) {
    const showAt = dismissedAt + getDuration();
    if (showAt > Date.now()) {
      scheduleAlert(showAt);
    } else {
      showAlert();
    }
  } else {
    const legacyDismissedUntil = Number(localStorage.getItem(dismissedUntilKey));
    if (Number.isFinite(legacyDismissedUntil) && legacyDismissedUntil > Date.now()) {
      const legacyDismissedAt = legacyDismissedUntil - 60 * 60 * 1000;
      localStorage.setItem(dismissedAtKey, String(legacyDismissedAt));
      const showAt = legacyDismissedAt + getDuration();
      if (showAt > Date.now()) {
        scheduleAlert(showAt);
      } else {
        showAlert();
      }
    } else {
      localStorage.removeItem(dismissedUntilKey);
    }
  }

  dismissButton.addEventListener('click', () => {
    const dismissedAt = Date.now();
    localStorage.setItem(dismissedAtKey, String(dismissedAt));
    localStorage.removeItem(dismissedUntilKey);
    scheduleAlert(dismissedAt + getDuration(), true);
  });

  const refreshFrequency = () => {
    const updatedFrequency = getReminderFrequency();
    if (updatedFrequency === frequency) {
      return;
    }

    frequency = updatedFrequency;
    updateAlertText();

    const dismissedAt = Number(localStorage.getItem(dismissedAtKey));
    if (Number.isFinite(dismissedAt) && dismissedAt > 0) {
      scheduleAlert(dismissedAt + getDuration());
    }
  };

  window.addEventListener('upmove:settings-changed', refreshFrequency);
  window.addEventListener('upmove:settings-loaded', refreshFrequency);

  updateAlertText();
})();
