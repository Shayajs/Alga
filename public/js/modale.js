(() => {
    const ouvrir = (id) => {
        const dialog = document.getElementById(id);
        if (dialog && typeof dialog.showModal === 'function' && !dialog.open) {
            dialog.showModal();
        }
    };

    document.addEventListener('click', (event) => {
        const declencheur = event.target.closest('[data-modale]');
        if (!declencheur) {
            return;
        }

        event.preventDefault();
        ouvrir(declencheur.dataset.modale);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter' && event.key !== ' ') {
            return;
        }

        const declencheur = event.target.closest('[data-modale]');
        if (!declencheur || declencheur.tagName === 'BUTTON') {
            return;
        }

        event.preventDefault();
        ouvrir(declencheur.dataset.modale);
    });

    document.querySelectorAll('dialog.modale').forEach((dialog) => {
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) {
                dialog.close();
            }
        });

        if (dialog.hasAttribute('data-open')) {
            ouvrir(dialog.id);
        }
    });
})();
