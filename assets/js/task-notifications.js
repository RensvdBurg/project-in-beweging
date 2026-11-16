(() => {
  const home = document.getElementById('screen-home');

  if (!home || !home.dataset.challengeId || !home.dataset.challengeInterval) {
    return;
  }

  const userId = home.dataset.userId;
  const challengeKey = `upmove:last-notified-challenge:${userId}`;
  const pendingKey = `upmove:pending-challenge-notification:${userId}`;
  const currentChallenge = `${home.dataset.challengeInterval}:${home.dataset.challengeId}`;
  const challengeTitle = home.dataset.challengeTitle || 'Er staat een nieuwe opdracht klaar.';

  function readSettings() {
    return window.upmoveSettings || null;
  }

  function isWithinSchedule(settings, date = new Date()) {
    return (
      settings?.workOnly === true &&
      typeof window.UpMoveSettings?.isActiveNow === 'function' &&
      window.UpMoveSettings.isActiveNow(date)
    );
  }

  function showNotification() {
    try {
      const notification = new Notification('Nieuwe opdracht beschikbaar!', {
        body: challengeTitle,
        tag: `upmove-challenge-${userId}`,
      });

      notification.addEventListener('click', () => {
        window.focus();
        notification.close();
      });
    } catch (error) {
      console.error('De melding voor de nieuwe opdracht kon niet worden getoond.', error);
    }
  }

  function canNotify(settings) {
    return (
      settings &&
      settings.workOnly === true &&
      window.isSecureContext &&
      'Notification' in window &&
      Notification.permission === 'granted'
    );
  }

  function checkPendingNotification() {
    try {
      const settings = readSettings();
      if (!settings) {
        return;
      }

      if (!canNotify(settings)) {
        localStorage.removeItem(pendingKey);
        return;
      }

      const pendingChallenge = localStorage.getItem(pendingKey);
      if (pendingChallenge && isWithinSchedule(settings)) {
        showNotification();
        localStorage.removeItem(pendingKey);
      }
    } catch (error) {
      console.error('De geplande opdrachtmelding kon niet worden verwerkt.', error);
    }
  }

  try {
    const previousChallenge = localStorage.getItem(challengeKey);
    localStorage.setItem(challengeKey, currentChallenge);

    const settings = readSettings();
    if (previousChallenge !== null && previousChallenge !== currentChallenge) {
      if (canNotify(settings)) {
        if (isWithinSchedule(settings)) {
          showNotification();
          localStorage.removeItem(pendingKey);
        } else {
          localStorage.setItem(pendingKey, currentChallenge);
        }
      } else {
        localStorage.removeItem(pendingKey);
      }
    }

    checkPendingNotification();
  } catch (error) {
    console.error('Opdrachtmeldingen konden niet worden verwerkt.', error);
  }

  window.setInterval(checkPendingNotification, 60 * 1000);
  window.addEventListener('upmove:settings-changed', checkPendingNotification);
  window.addEventListener('upmove:settings-loaded', checkPendingNotification);
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
      checkPendingNotification();
    }
  });
})();
