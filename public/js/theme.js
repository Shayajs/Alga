(() => {
    const racine = document.documentElement;
    const bouton = document.getElementById('btn-theme');
    const meta = document.getElementById('theme-color');

    const appliquer = (theme) => {
        racine.dataset.theme = theme;
        try {
            localStorage.setItem('alga-theme', theme);
        } catch (e) {
            // stockage indisponible
        }
        if (meta) {
            meta.setAttribute('content', theme === 'sombre' ? '#2b2b2b' : '#f3ead8');
        }
        if (bouton) {
            bouton.textContent = theme === 'sombre' ? 'Clair' : 'Sombre';
        }
    };

    const actuel = racine.dataset.theme === 'sombre' ? 'sombre' : 'clair';
    appliquer(actuel);

    bouton?.addEventListener('click', () => {
        appliquer(racine.dataset.theme === 'sombre' ? 'clair' : 'sombre');
    });
})();
