(() => {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!token) {
        return;
    }

    const serialiser = (form) => {
        const params = new URLSearchParams();
        new FormData(form).forEach((valeur, nom) => {
            if (nom === '_token' || nom === '_method') {
                return;
            }
            params.append(nom, String(valeur));
        });
        return params.toString();
    };

    const etat = (el, classe, texte) => {
        if (!el) {
            return;
        }
        el.classList.remove('is-ok', 'is-err', 'is-busy');
        if (classe) {
            el.classList.add(classe);
        }
        el.textContent = texte;
    };

    const corpsFormulaire = (form) => {
        const params = new URLSearchParams();
        new FormData(form).forEach((valeur, nom) => {
            params.append(nom, String(valeur));
        });
        if (!params.has('_method')) {
            params.set('_method', 'PUT');
        }
        if (!params.has('_token')) {
            params.set('_token', token);
        }
        return params;
    };

    document.querySelectorAll('form.js-autosave').forEach((form) => {
        const statut = form.querySelector('.ops-save-status');
        let dernier = serialiser(form);
        let enCours = false;
        let relancer = false;
        let timer = null;

        const enregistrer = async () => {
            const actuel = serialiser(form);
            if (actuel === dernier) {
                return;
            }

            if (enCours) {
                relancer = true;
                return;
            }

            enCours = true;
            etat(statut, 'is-busy', 'Enregistrement…');

            try {
                const reponse = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token,
                    },
                    body: corpsFormulaire(form),
                    credentials: 'same-origin',
                });
                const json = await reponse.json().catch(() => ({}));
                if (!reponse.ok) {
                    const msg = json.message || Object.values(json.errors || {}).flat()[0] || ('Erreur ' + reponse.status);
                    throw new Error(msg);
                }
                dernier = serialiser(form);
                etat(statut, 'is-ok', json.message || 'Enregistré');
            } catch (e) {
                etat(statut, 'is-err', e.message || 'Échec');
            } finally {
                enCours = false;
                if (relancer) {
                    relancer = false;
                    enregistrer();
                }
            }
        };

        const plusTard = () => {
            clearTimeout(timer);
            timer = setTimeout(enregistrer, 400);
        };

        form.querySelectorAll('input, select, textarea').forEach((champ) => {
            champ.addEventListener('blur', enregistrer);
            champ.addEventListener('change', enregistrer);
            champ.addEventListener('input', plusTard);
        });

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            clearTimeout(timer);
            enregistrer();
        });

        window.addEventListener('pagehide', enregistrer);
    });
})();
