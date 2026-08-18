@php
    $avance = $avance ?? false;
    $lectureSeule = $lectureSeule ?? false;
    $peutCocher = ($peutCocher ?? false) && ! $lectureSeule;
    $penibilite = $penibilite ?? $affectations->sum(fn ($a) => $a->tache->penibilite);
    $kicker = $kicker ?? ($affectations->first()?->tache->libelleFrequence() ?? '');
    $faits = $affectations->filter->estFaite()->count();
    $total = $affectations->count();
    $statuts = $affectations->map->statut();
    $tone = $statuts->contains('en_retard') ? 'en_retard'
        : ($statuts->contains('a_faire') ? 'a_faire'
        : ($statuts->contains('avance') ? 'avance' : 'fait'));
    $cle = session('ouvrir_piece');
    $tacheOuverte = (int) session('ouvrir_tache');
    $ouverte = ($cle && str_contains($id, (string) $cle))
        || ($tacheOuverte && $affectations->contains(fn ($a) => (int) $a->id === $tacheOuverte));
@endphp
<details class="piece-card piece-card-{{ $tone }}" @if ($ouverte) open @endif>
    <summary>
        <p class="piece-card-kicker">{{ $kicker }}</p>
        <h2>{{ $piece }}</h2>
        <p class="piece-card-meta">
            {{ $faits }} / {{ $total }} done
            @if ($penibilite) · pénibilité {{ $penibilite }} @endif
        </p>
        <div class="piece-dots" aria-hidden="true">
            @foreach ($affectations as $affectation)
                <span class="piece-dot piece-dot-{{ $affectation->statut() }}"></span>
            @endforeach
        </div>
        <ul class="piece-preview">
            @foreach ($affectations as $affectation)
                <li>
                    <span class="status-chip status-chip-{{ $affectation->statut() }}">{{ $affectation->codeStatut() }}</span>
                    @if ($affectation->tache->frequence === 'hebdo'){{ $affectation->tache->piece }} · @endif{{ $affectation->tache->titre }}
                </li>
            @endforeach
        </ul>
        <p class="piece-card-more">{{ $lectureSeule ? 'Aperçu' : 'Cliquer pour le détail' }}</p>
    </summary>

    <ul class="tasks">
        @foreach ($affectations as $affectation)
            @include($lectureSeule ? 'partials.tache-apercu' : 'partials.tache-ligne', [
                'affectation' => $affectation,
                'avance' => $avance,
            ])
        @endforeach
    </ul>
</details>
