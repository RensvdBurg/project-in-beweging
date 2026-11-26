(() => {
  const DARK_KEY = "upmove:dark-mode";
  const WORK_KEY = "upmove:work-hours";

  /*
   * DARK MODE
   * Wordt op elke pagina uitgevoerd waar dit bestand geladen wordt.
   */
  const root = document.documentElement;
  const userId = root.dataset.userId;

  /*
   * Instellingen die op de instellingenpagina gewijzigd zijn maar nog niet
   * door de server bevestigd (geen verbinding, of weggenavigeerd vóór het
   * opslaan klaar was). Zolang dat zo is gaan deze vóór de database.
   * Geeft null als er niets openstaat.
   */
  function getPendingSettings() {
    if (!userId) {
      return null;
    }

    try {
      if (localStorage.getItem(`upmove:settings-pending:${userId}`) !== "1") {
        return null;
      }

      const saved = JSON.parse(localStorage.getItem(`upmove:settings:${userId}`));

      return saved && typeof saved === "object" ? saved : null;
    } catch {
      return null;
    }
  }

  /*
   * Openstaande wijzigingen op <html> zetten, zodat de rest van dit
   * bestand (en andere scripts) dezelfde waarden zien.
   */
  function applyPendingSettings() {
    const pending = getPendingSettings();

    if (!pending) {
      return false;
    }

    if (typeof pending.darkMode === "boolean") {
      root.dataset.darkMode = String(pending.darkMode);
    }

    if (typeof pending.workOnly === "boolean") {
      root.dataset.workHours = JSON.stringify({
        enabled: pending.workOnly,
        start: pending.workStart,
        end: pending.workEnd,
        days: pending.workDays,
        schedule: pending.workSchedule,
      });
    }

    return true;
  }

  /*
   * Ingelogd: de layout zet de instellingen uit de database op <html>
   * (data-dark-mode, data-work-hours). Die gaan voor; localStorage is
   * alleen een reserve (bijvoorbeeld op het loginscherm).
   */
  function syncFromServer() {
    // Openstaande wijzigingen niet overschrijven met oudere serverwaarden.
    if (applyPendingSettings()) {
      return;
    }

    try {
      if (root.dataset.darkMode !== undefined) {
        localStorage.setItem(DARK_KEY, root.dataset.darkMode);
      }

      if (root.dataset.workHours !== undefined) {
        localStorage.setItem(WORK_KEY, root.dataset.workHours);
      }
    } catch {
      // localStorage niet beschikbaar
    }
  }

  function applyDarkMode() {
    let darkMode = root.dataset.darkMode === "true";

    if (root.dataset.darkMode === undefined) {
      try {
        darkMode = localStorage.getItem(DARK_KEY) === "true";
      } catch {
        // localStorage niet beschikbaar
      }
    }

    root.classList.toggle("upmove-dark", darkMode);

    const themeColor = document.getElementById("theme-color");

    if (themeColor) {
      themeColor.setAttribute("content", darkMode ? "#151920" : "#f7f8fa");
    }
  }

  /*
   * Werk/schoolinstellingen ophalen.
   */
  const isTime = (value) =>
    typeof value === "string" && /^([01]\d|2[0-3]):[0-5]\d$/.test(value);

  /*
   * Tijden per dag (index = getDay(), 0 = zondag). Ontbreekt een dag,
   * dan geldt de algemene start-/eindtijd (oudere instellingen).
   */
  function scheduleFrom(saved, start, end) {
    return Array.from({ length: 7 }, (_, day) => {
      const hours = Array.isArray(saved) ? saved[day] : null;

      return {
        start: isTime(hours?.start) ? hours.start : start,
        end: isTime(hours?.end) ? hours.end : end,
      };
    });
  }

  function getWorkHours() {
    const defaults = {
      enabled: false,
      start: "08:30",
      end: "17:00",
      days: [1, 2, 3, 4, 5],
    };

    defaults.schedule = scheduleFrom(null, defaults.start, defaults.end);

    try {
      const saved = JSON.parse(
        root.dataset.workHours ?? localStorage.getItem(WORK_KEY),
      );

      if (!saved || typeof saved !== "object") {
        return defaults;
      }

      const start = isTime(saved.start) ? saved.start : defaults.start;
      const end = isTime(saved.end) ? saved.end : defaults.end;

      return {
        enabled:
          typeof saved.enabled === "boolean" ? saved.enabled : defaults.enabled,

        start,

        end,

        days: Array.isArray(saved.days) ? saved.days : defaults.days,

        schedule: scheduleFrom(saved.schedule, start, end),
      };
    } catch {
      return defaults;
    }
  }

  /*
   * Zet "08:30" om naar minuten:
   *
   * 8 * 60 + 30 = 510
   */
  function timeToMinutes(time) {
    const [hours, minutes] = time.split(":").map(Number);

    return hours * 60 + minutes;
  }

  /*
   * Controleert of UpMove op dit moment actief mag zijn.
   *
   * true  = reminders mogen
   * false = buiten werk/school
   */
  function isActiveNow(date = new Date()) {
    const settings = getWorkHours();

    /*
     * Beperking staat uit:
     * UpMove mag altijd reminders geven.
     */
    if (!settings.enabled) {
      return true;
    }

    const currentDay = date.getDay();

    const currentMinutes = date.getHours() * 60 + date.getMinutes();

    /*
     * Vandaag een werk/schooldag: binnen de tijden van vandaag?
     */
    if (settings.days.includes(currentDay)) {
      const today = settings.schedule[currentDay];
      const start = timeToMinutes(today.start);
      const end = timeToMinutes(today.end);

      // Normale tijd, bijvoorbeeld 08:30 - 17:00.
      if (start <= end && currentMinutes >= start && currentMinutes <= end) {
        return true;
      }

      // Nachtdienst die vandaag begint, bijvoorbeeld 22:00 - 06:00.
      if (start > end && currentMinutes >= start) {
        return true;
      }
    }

    /*
     * Nog bezig met de nachtdienst van gisteren
     * (bijvoorbeeld zondag 22:00 - maandag 06:00)?
     */
    const previousDay = (currentDay + 6) % 7;

    if (!settings.days.includes(previousDay)) {
      return false;
    }

    const yesterday = settings.schedule[previousDay];

    return (
      timeToMinutes(yesterday.start) > timeToMinutes(yesterday.end) &&
      currentMinutes <= timeToMinutes(yesterday.end)
    );
  }

  /*
   * Beschikbaar maken voor andere scripts.
   */
  window.UpMoveSettings = {
    applyDarkMode,
    getPendingSettings,
    getWorkHours,
    isActiveNow,
  };

  /*
   * Meteen dark mode toepassen.
   */
  syncFromServer();
  applyDarkMode();

  /*
   * Wanneer instellingen veranderen,
   * dark mode opnieuw toepassen.
   */
  window.addEventListener("upmove:settings-changed", applyDarkMode);
})();
