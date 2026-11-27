// Admin-dashboard: bevestiging bij verwijderen en voorbeeld van de challenge-afbeelding.
(() => {
    // ===== Bevestigen =====
    // Formulieren met data-confirm vragen eerst om bevestiging in een <dialog>
    // (geen window.confirm(): dat werkt niet in elke WebView). Zonder JavaScript
    // wordt het formulier gewoon verstuurd.
    const dialog = document.getElementById('admin-confirm-dialog');
    const message = document.getElementById('admin-confirm-message');
    const okButton = document.getElementById('admin-confirm-ok');
    const cancelButton = document.getElementById('admin-confirm-cancel');
    let pendingForm = null;

    if (dialog instanceof HTMLDialogElement && message && okButton && cancelButton) {
        document.querySelectorAll('form[data-confirm]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                pendingForm = form;
                message.textContent = form.dataset.confirm ?? '';
                dialog.showModal();
            });
        });

        okButton.addEventListener('click', () => {
            const form = pendingForm;
            pendingForm = null;
            dialog.close();
            // submit() slaat het submit-event over, dus geen tweede dialoog.
            form?.submit();
        });

        cancelButton.addEventListener('click', () => dialog.close());
        dialog.addEventListener('close', () => {
            pendingForm = null;
        });
    }

    // ===== Afbeeldingsvoorbeeld =====
    document.querySelectorAll('input[data-image-preview]').forEach((input) => {
        const preview = document.getElementById(input.dataset.imagePreview ?? '');

        if (!(input instanceof HTMLInputElement) || !(preview instanceof HTMLImageElement)) {
            return;
        }

        const update = () => {
            const value = input.value.trim();

            if (value === '') {
                preview.classList.add('hidden');
                return;
            }

            preview.src = /^https?:\/\//i.test(value)
                ? value
                : (input.dataset.assetBase ?? '/').replace(/\/?$/, '/') + value.replace(/^\/+/, '');
            preview.classList.remove('hidden');
        };

        input.addEventListener('change', update);
        preview.addEventListener('error', () => preview.classList.add('hidden'));
        preview.addEventListener('load', () => preview.classList.remove('hidden'));
    });
})();
