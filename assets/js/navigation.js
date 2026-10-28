(() => {
  const navItems = document.querySelectorAll(".nav-item[data-screen]");
  const screens = document.querySelectorAll(".screen");

  function openScreen(screenName, updateUrl = true) {
    const target = document.getElementById(`screen-${screenName}`);
    if (!target) {
      return false;
    }

    screens.forEach((screen) => {
      screen.classList.toggle("hidden", screen !== target);
    });

    navItems.forEach((item) => {
      const active = item.dataset.screen === screenName;
      item.classList.toggle("text-orange", active);
      item.classList.toggle("text-[#9aa0a8]", !active);

      if (active) {
        item.setAttribute("aria-current", "page");
      } else {
        item.removeAttribute("aria-current");
      }
    });

    if (updateUrl) {
      const url = new URL(window.location.href);
      url.searchParams.set("screen", screenName);
      history.replaceState({}, "", url);
    }

    return true;
  }

  navItems.forEach((item) => {
    item.addEventListener("click", (event) => {
      const screenName = item.dataset.screen;
      if (document.getElementById(`screen-${screenName}`)) {
        event.preventDefault();
        openScreen(screenName);
      }
    });
  });

  if (screens.length > 0) {
    const params = new URLSearchParams(window.location.search);
    const requestedScreen = params.get("screen") || "home";

    if (!openScreen(requestedScreen, false)) {
      openScreen("home", false);
    }
  }
})();
