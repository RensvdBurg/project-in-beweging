(() => {
    const dialog = document.getElementById('account-delete-dialog');
    const openButton = document.getElementById('account-delete-open');
    const cancelButton = document.getElementById('account-delete-cancel');

    if (dialog instanceof HTMLDialogElement && openButton && cancelButton) {
        openButton.addEventListener('click', () => dialog.showModal());
        cancelButton.addEventListener('click', () => dialog.close());
    }

    document.querySelectorAll('form input[name="confirm_password"]').forEach((confirmation) => {
        const form = confirmation.form;
        const newPassword = form?.querySelector('input[name="new_password"]');

        if (!form || !(newPassword instanceof HTMLInputElement)) {
            return;
        }

        const validate = () => {
            confirmation.setCustomValidity(
                confirmation.value === newPassword.value
                    ? ''
                    : 'De nieuwe wachtwoorden komen niet overeen.'
            );
        };

        confirmation.addEventListener('input', validate);
        newPassword.addEventListener('input', validate);
    });
})();
