(() => {
    const standalone = window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js').catch(() => {});
        });
    }

    if (standalone) {
        return;
    }

    const barre = document.getElementById('pwa-install');
    const installer = document.getElementById('pwa-install-btn');
    const ignorer = document.getElementById('pwa-install-dismiss');
    const texte = document.getElementById('pwa-install-text');
    if (!barre || !installer || !ignorer || !texte) {
        return;
    }

    const cle = 'alga-pwa-dismiss';
    try {
        if (localStorage.getItem(cle)) {
            return;
        }
    } catch (e) {
        // ignore
    }

    const fermer = () => {
        barre.hidden = true;
        try {
            localStorage.setItem(cle, '1');
        } catch (e) {
            // ignore
        }
    };

    let promptEvent = null;
    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        promptEvent = event;
        texte.textContent = 'Installer Alga sur l’écran d’accueil';
        installer.hidden = false;
        barre.hidden = false;
    });

    installer.addEventListener('click', async () => {
        if (!promptEvent) {
            return;
        }
        promptEvent.prompt();
        await promptEvent.userChoice.catch(() => {});
        promptEvent = null;
        fermer();
    });

    ignorer.addEventListener('click', fermer);

    const ios = /iphone|ipad|ipod/i.test(navigator.userAgent);
    const safari = /safari/i.test(navigator.userAgent) && !/crios|fxios|edgios/i.test(navigator.userAgent);
    if (ios && safari) {
        texte.textContent = 'Sur iPhone : Partager, puis « Sur l’écran d’accueil ».';
        installer.hidden = true;
        barre.hidden = false;
    }
})();
