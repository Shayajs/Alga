<dialog class="modale" id="{{ $modaleId }}" aria-labelledby="{{ $modaleId }}-titre" @if ($ouverte) data-open @endif>
    <article class="modale-sheet">
        <header class="modale-head">
            <div>
                <p class="modale-kicker">Modifier la tâche</p>
                <h2 id="{{ $modaleId }}-titre">{{ $titre }}</h2>
                <p class="modale-meta">
                    <span class="status-chip status-chip-{{ $statut }}">{{ $affectation->codeStatut() }} · {{ $affectation->libelleStatut() }}</span>
                    jusqu’à {{ $affectation->limiteAt()->translatedFormat('D j M') }} · {{ $affectation->limiteAt()->format('H:i') }}
                </p>
            </div>
            <form method="dialog">
                <button type="submit" class="modale-close" aria-label="Fermer">×</button>
            </form>
        </header>

        @auth
            @if ($peutModifier)
                <form method="POST" action="{{ route('affectations.update', $affectation) }}" class="modale-form" id="form-{{ $modaleId }}">
                    @csrf
                    @method('PUT')
                    <div class="modale-grid">
                        <label>
                            Statut
                            <select name="fait">
                                <option value="0" @selected(! $affectation->estFaite())>Pas faite</option>
                                <option value="1" @selected($affectation->estFaite())>Faite</option>
                            </select>
                        </label>
                        <label>
                            Qui
                            @include('partials.qui-select', ['affectation' => $affectation, 'couple' => $coupleCredit ?? $couple])
                        </label>
                    </div>
                    <label>
                        Heure réelle (UTC+2)
                        <input type="datetime-local" name="fait_a" value="{{ $faitA->format('Y-m-d\TH:i') }}">
                    </label>
                    <label>
                        Note du changement
                        <input type="text" name="commentaire" maxlength="280" placeholder="Optionnel — pourquoi on corrige">
                    </label>
                </form>
            @else
                <p class="muted">Lot de {{ $couple->nom }} — tu peux lire l’historique, pas le modifier.</p>
            @endif
        @else
            <p class="muted"><a href="{{ route('login') }}">Connexion</a> pour modifier.</p>
        @endauth

        @include('partials.tache-journal', ['evenements' => $affectation->evenements])

        <footer class="modale-foot">
            <form method="dialog">
                <button type="submit" class="btn btn-ghost">Annuler</button>
            </form>
            @auth
                @if ($peutModifier)
                    <button type="submit" class="btn btn-primary" form="form-{{ $modaleId }}">Enregistrer</button>
                @endif
            @endauth
        </footer>
    </article>
</dialog>
