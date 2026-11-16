(() => {
  if ("scrollRestoration" in history) {
    history.scrollRestoration = "manual";
  }

  const scrollToTop = () => {
    window.scrollTo(0, 0);
  };

  document.addEventListener("DOMContentLoaded", scrollToTop, { once: true });
  window.addEventListener("pageshow", () => {
    requestAnimationFrame(scrollToTop);
  });
})();
