(() => {
  const activityPage = document.querySelector("[data-activity-user-id]");

  if (!activityPage) {
    return;
  }

  const completedKey = `project-in-beweging:activity-tour-completed:${activityPage.dataset.activityUserId}`;

  if (localStorage.getItem(completedKey)) {
    return;
  }

  const createDriver = window.driver?.js?.driver;
  if (typeof createDriver !== "function") {
    console.error(
      "Driver.js kon niet worden geladen; de uitleg bij Activiteiten is niet beschikbaar.",
    );
    return;
  }

  const activityListSelector = document.querySelector(
    "#activity-tour-empty-state",
  )
    ? "#activity-tour-list"
    : "#activity-list-title";

  const tourSteps = [
    {
      selector: "#activity-tour-intro",
      title: "Jouw activiteiten",
      description:
        "Op deze pagina deel je wat je hebt gedaan en bekijk je je eigen eerdere activiteiten.",
    },
    {
      selector: "#activity-title",
      title: "Deel een activiteit",
      description:
        "Vul een titel in om te vertellen wat je hebt gedaan.",
    },
    {
      selector: "#activity-description",
      title: "Deel een activiteit",
      description:
        "Vul een beschrijving in om te vertellen wat je hebt gedaan.",
    },
    {
      selector: "#activity-photo",
      title: "Voeg een foto toe",
      description: "Kies een JPG-, PNG- of WebP-afbeelding van maximaal 5 MB.",
    },
    {
      selector: 'form#activity-tour-form button[type="submit"]',
      title: "Plaats je activiteit",
      description:
        "Klaar? Tik op Activiteit delen. Daarna verschijnt je bericht bij jouw activiteiten.",
    },
    {
      selector: activityListSelector,
      title: "Jouw gedeelde activiteiten",
      description: "Hier vind je de activiteiten die je eerder hebt geplaatst.",
    },
  ];

  const steps = tourSteps
    .filter(({ selector }) => document.querySelector(selector))
    .map(({ selector, title, description }) => ({
      element: selector,
      onHighlightStarted: (target) => {
        if (!(target instanceof HTMLElement)) {
          return;
        }

        const pageBounds = activityPage.getBoundingClientRect();
        const targetBounds = target.getBoundingClientRect();
        const targetPosition = pageBounds.top + pageBounds.height * 0.68;
        activityPage.scrollTop += targetBounds.top - targetPosition;
      },
      popover: {
        title,
        description,
        side: "top",
        align: "start",
      },
    }));

  if (steps.length === 0) {
    return;
  }

  const tour = createDriver({
    steps,
    popoverClass: "home-tour-popover",
    showProgress: true,
    nextBtnText: "Volgende",
    prevBtnText: "Vorige",
    doneBtnText: "Klaar",
    closeBtnLabel: "Uitleg sluiten",
    onDestroyed: () => localStorage.setItem(completedKey, "true"),
  });

  tour.drive();
})();
