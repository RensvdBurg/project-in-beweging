(() => {
  const defaults = {
    frequency: 60,
    darkMode: false,
    workOnly: false,
    workStart: "08:30",
    workEnd: "17:00",
    workDays: [1, 2, 3, 4, 5],
  };
  const initial = window.upmoveInitialSettings || {};
  let settings = { ...defaults, ...(initial.settings || {}) };

  function applySettings(updatedSettings) {
    settings = {
      ...defaults,
      ...updatedSettings,
      workDays: Array.isArray(updatedSettings.workDays)
        ? [...updatedSettings.workDays]
        : [...defaults.workDays],
    };

    document.documentElement.classList.toggle(
      "upmove-dark",
      settings.darkMode === true,
    );

    const themeColor = document.getElementById("theme-color");
    if (themeColor) {
      themeColor.setAttribute(
        "content",
        settings.darkMode ? "#151920" : "#f7f8fa",
      );
    }

    window.upmoveSettings = Object.freeze({
      ...settings,
      workDays: [...settings.workDays],
    });
  }

  function timeToMinutes(time) {
    const [hours, minutes] = time.split(":").map(Number);
    return hours * 60 + minutes;
  }

  function getWorkHours() {
    return {
      enabled: settings.workOnly,
      start: settings.workStart,
      end: settings.workEnd,
      days: [...settings.workDays],
    };
  }

  function isActiveNow(date = new Date()) {
    const workHours = getWorkHours();
    if (!workHours.enabled) {
      return true;
    }

    const currentDay = date.getDay();
    if (!workHours.days.includes(currentDay)) {
      return false;
    }

    const currentMinutes = date.getHours() * 60 + date.getMinutes();
    const start = timeToMinutes(workHours.start);
    const end = timeToMinutes(workHours.end);

    return start <= end
      ? currentMinutes >= start && currentMinutes <= end
      : currentMinutes >= start || currentMinutes <= end;
  }

  applySettings(settings);

  window.UpMoveSettings = {
    applyDarkMode: () => applySettings(settings),
    getWorkHours,
    isActiveNow,
  };

  window.addEventListener("upmove:settings-changed", (event) => {
    if (event.detail && typeof event.detail === "object") {
      applySettings(event.detail);
    }
  });

  async function migrateLegacySettings() {
    if (initial.userId === null || initial.exists) {
      return;
    }

    let legacySettings;
    try {
      const saved = localStorage.getItem(
        `upmove:settings:${initial.userId}`,
      );
      if (!saved) {
        return;
      }
      legacySettings = JSON.parse(saved);
    } catch (error) {
      console.error("Oude instellingen konden niet worden gelezen.", error);
      return;
    }

    if (!legacySettings || typeof legacySettings !== "object") {
      return;
    }

    try {
      const response = await fetch(
        "/project-in-beweging/pages/settings/settings-api.php",
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-CSRF-Token": initial.csrfToken,
          },
          body: JSON.stringify({ settings: legacySettings }),
        },
      );
      const result = await response.json();
      if (!response.ok) {
        throw new Error(result.error || "Instellingen migreren is mislukt.");
      }

      initial.settings = result.settings;
      initial.exists = true;
      applySettings(result.settings);
      window.dispatchEvent(
        new CustomEvent("upmove:settings-loaded", {
          detail: result.settings,
        }),
      );
    } catch (error) {
      console.error("Oude instellingen konden niet worden overgezet.", error);
    }
  }

  let migrationPromise = Promise.resolve();
  let settingsRefreshInProgress = false;

  async function refreshSettings() {
    if (
      initial.userId === null ||
      document.getElementById("screen-settings") ||
      settingsRefreshInProgress
    ) {
      return;
    }

    settingsRefreshInProgress = true;
    try {
      await migrationPromise;
      const response = await fetch(
        "/project-in-beweging/pages/settings/settings-api.php",
        {
          cache: "no-store",
          credentials: "same-origin",
        },
      );
      const result = await response.json();
      if (!response.ok) {
        throw new Error(result.error || "Instellingen konden niet worden bijgewerkt.");
      }

      initial.settings = result.settings;
      initial.exists = result.exists;
      applySettings(result.settings);
      window.dispatchEvent(
        new CustomEvent("upmove:settings-loaded", {
          detail: result.settings,
        }),
      );
    } catch (error) {
      console.error("Instellingen konden niet opnieuw worden geladen.", error);
    } finally {
      settingsRefreshInProgress = false;
    }
  }

  document.addEventListener(
    "DOMContentLoaded",
    () => {
      migrationPromise = migrateLegacySettings();
    },
    { once: true },
  );
  window.addEventListener("pageshow", refreshSettings);
  document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "visible") {
      refreshSettings();
    }
  });
  window.addEventListener("focus", refreshSettings);
})();
