(() => {
    const form = document.getElementById('form-connexion');
    if (!form) {
        return;
    }

    const urlEtat = form.dataset.etat;
    const pseudo = document.getElementById('pseudo');
    const bloc = document.getElementById('bloc-mot-de-passe');
    const labelPassword = document.getElementById('texte-password');
    const champPassword = document.getElementById('password');
    const labelConfirmation = document.getElementById('label-confirmation');
    const champConfirmation = document.getElementById('password_confirmation');
    const bouton = document.getElementById('btn-connexion');
    const nom = document.getElementById('auth-nom');
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    let timer = null;

    const cacherMdp = () => {
        bloc.classList.add('is-hidden');
        labelConfirmation.classList.add('is-hidden');
        champPassword.autocomplete = 'current-password';
        champPassword.value = '';
        champConfirmation.value = '';
        bouton.textContent = 'Continuer';
        nom.hidden = true;
    };

    const montrerConnexion = (prenom) => {
        bloc.classList.remove('is-hidden');
        bloc.dataset.mode = 'mot_de_passe';
        labelConfirmation.classList.add('is-hidden');
        labelPassword.textContent = 'Mot de passe';
        champPassword.autocomplete = 'current-password';
        champPassword.required = true;
        champConfirmation.required = false;
        bouton.textContent = 'Entrer';
        nom.hidden = false;
        nom.textContent = prenom ? `Bonjour ${prenom}.` : '';
        champPassword.focus();
    };

    const montrerCreation = (prenom) => {
        bloc.classList.remove('is-hidden');
        bloc.dataset.mode = 'creation';
        labelConfirmation.classList.remove('is-hidden');
        labelPassword.textContent = 'Choisis un mot de passe';
        champPassword.autocomplete = 'new-password';
        champPassword.required = true;
        champConfirmation.required = true;
        bouton.textContent = 'Créer et entrer';
        nom.hidden = false;
        nom.textContent = prenom ? `${prenom}, première connexion : crée ton mot de passe.` : 'Première connexion : crée ton mot de passe.';
        champPassword.focus();
    };

    const interroger = async () => {
        const valeur = pseudo.value.trim();
        if (valeur.length < 2) {
            cacherMdp();
            return;
        }

        try {
            const reponse = await fetch(urlEtat, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ pseudo: valeur }),
            });
            const data = await reponse.json();

            if (!data.existe) {
                cacherMdp();
                return;
            }

            if (data.premier) {
                montrerCreation(data.nom);
            } else {
                montrerConnexion(data.nom);
            }
        } catch {
            cacherMdp();
        }
    };

    pseudo.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(interroger, 280);
    });

    pseudo.addEventListener('blur', interroger);

    if (form.dataset.etape === 'creation') {
        montrerCreation('');
    } else if (form.dataset.etape === 'mot_de_passe') {
        montrerConnexion('');
    }
})();
