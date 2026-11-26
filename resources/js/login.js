/*
 * Aanmelden en registreren (resources/views/auth/login.blade.php).
 *
 * - Wisselen tussen de panelen zonder herladen (knoppen met data-mode).
 * - Wachtwoord tonen/verbergen (knoppen met data-password-toggle).
 */
(() => {
  const panels = {
    login: document.getElementById('login'),
    register: document.getElementById('register'),
  };

  if (!panels.login || !panels.register) {
    return;
  }

  const titles = {
    login: 'Aanmelden | UpMove',
    register: 'Registreren | UpMove',
  };

  const showMode = (mode) => {
    const target = panels[mode];

    // Oude meldingen horen bij het andere formulier: weghalen bij wisselen.
    document.querySelectorAll('.auth-message').forEach((message) => message.remove());

    Object.entries(panels).forEach(([name, panel]) => {
      panel.classList.toggle('hidden', name !== mode);
    });

    // Het paneel schuift vanzelf in: de animatie start opnieuw zodra het zichtbaar wordt (app.css).
    document.title = titles[mode];
    target.querySelector('input:not([type="hidden"])')?.focus({ preventScroll: true });
    window.scrollTo({ top: 0 });
  };

  document.querySelectorAll('[data-mode]').forEach((button) => {
    button.addEventListener('click', () => showMode(button.dataset.mode));
  });

  document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    const input = document.getElementById(button.dataset.passwordToggle);

    if (!input) {
      return;
    }

    button.addEventListener('click', () => {
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      button.setAttribute('aria-pressed', String(show));
      button.setAttribute('aria-label', show ? 'Wachtwoord verbergen' : 'Wachtwoord tonen');
      button.querySelector('[data-icon="show"]')?.classList.toggle('hidden', show);
      button.querySelector('[data-icon="hide"]')?.classList.toggle('hidden', !show);
      input.focus({ preventScroll: true });
    });
  });

  // Wachtwoorden nooit zichtbaar laten staan bij versturen (wachtwoordbeheerders).
  document.querySelectorAll('#loginForm, #registerForm').forEach((form) => {
    form.addEventListener('submit', () => {
      form.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = document.getElementById(button.dataset.passwordToggle);

        if (input?.type === 'text') {
          button.click();
        }
      });
    });
  });
})();
