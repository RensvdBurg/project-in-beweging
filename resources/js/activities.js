/*
 * Afbeelding kiezen voor een nieuwe activiteit.
 *
 * - In de NativePHP-app: native galerijkiezer (camera-plugin). Het gekozen bestand
 *   staat dan op het toestel; we sturen alleen het pad mee in een verborgen veld.
 * - In de browser: gewone <input type="file"> met een voorbeeldweergave.
 */
(() => {
  const preview = document.getElementById('activity-image-preview');

  // ---------- Browser ----------
  const fileInput = document.getElementById('activity-image');

  if (fileInput instanceof HTMLInputElement && preview instanceof HTMLImageElement) {
    let objectUrl = null;

    fileInput.addEventListener('change', () => {
      if (objectUrl) {
        URL.revokeObjectURL(objectUrl);
        objectUrl = null;
      }

      const file = fileInput.files?.[0];
      preview.classList.toggle('hidden', !file);

      if (file) {
        objectUrl = URL.createObjectURL(file);
        preview.src = objectUrl;
      } else {
        preview.removeAttribute('src');
      }
    });
  }

  // ---------- NativePHP-app ----------
  const pickButton = document.getElementById('activity-pick-image');
  const pathInput = document.getElementById('activity-native-image');
  const pickLabel = document.getElementById('activity-pick-label');

  if (!pickButton || !(pathInput instanceof HTMLInputElement) || !pickLabel) {
    return;
  }

  // Alleen in de app laden, zodat de browser de native bibliotheek niet nodig heeft.
  import('#nativephp').then(({ On, PickImage, Events }) => {
    On(Events.Gallery.MediaSelected, (payload) => {
      const file = payload?.files?.[0];

      if (!payload?.success || payload?.cancelled || !file?.path) {
        return;
      }

      pathInput.value = file.path;
      pickLabel.textContent = 'Afbeelding gekozen ✓ (tik om te wijzigen)';
    });

    pickButton.addEventListener('click', () => {
      PickImage();
    });
  });
})();
