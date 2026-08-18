@php
    $avance = $avance ?? false;
    $statut = $affectation->statut();
    $couple = $affectation->couple;
    $peutModifier = $affectation->peutEtreModifieePar(auth()->user());
    $ouverte = (int) session('ouvrir_tache') === (int) $affectation->id;
    $faitA = $affectation->completion?->fait_a?->timezone(config('app.timezone'))
        ?? now()->timezone(config('app.timezone'));
    $modaleId = 'modale-tache-'.$affectation->id;
    $titre = ($affectation->tache->frequence === 'hebdo' ? $affectation->tache->piece.' · ' : '').$affectation->tache->titre;
@endphp
<li class="task task-{{ $statut }}">
    <div class="task-main" data-modale="{{ $modaleId }}" role="button" tabindex="0">
        <div class="task-head">
            <p class="task-title">{{ $titre }}</p>
            <span class="status-chip status-chip-{{ $statut }}">{{ $affectation->codeStatut() }} · {{ $affectation->libelleStatut() }}</span>
        </div>
        <p class="task-meta">jusqu’à {{ $affectation->limiteAt()->translatedFormat('D j M') }} · {{ $affectation->limiteAt()->format('H:i') }}</p>
        @if ($affectation->estFaite())
            <p class="stamp">
                {{ $affectation->libelleFait() }}
                à {{ $affectation->completion->fait_a->timezone(config('app.timezone'))->format('H:i') }}
            </p>
        @elseif ($statut === 'en_retard')
            <p class="stamp stamp-late">Non coché après 03:00</p>
        @else
            <p class="stamp stamp-todo">Ouvert jusqu’à {{ $affectation->limiteAt()->format('H:i') }}</p>
        @endif
    </div>

    <div class="task-actions">
        @if ($peutModifier && ! $affectation->estFaite())
            <form method="POST" action="{{ route('completions.store', $affectation) }}" class="task-actions-fait">
                @csrf
                <button type="submit" class="btn {{ ! $avance && $statut === 'en_retard' ? 'btn-warn' : 'btn-primary' }}">Fait</button>
            </form>
        @elseif ($peutModifier)
            <span class="btn btn-primary task-actions-fait-off">Fait</span>
        @endif
        <button type="button" class="btn btn-ghost task-actions-mod" data-modale="{{ $modaleId }}">Modifier</button>
    </div>

    @include('partials.tache-modale', [
        'affectation' => $affectation,
        'couple' => $couple,
        'statut' => $statut,
        'peutModifier' => $peutModifier,
        'ouverte' => $ouverte,
        'faitA' => $faitA,
        'modaleId' => $modaleId,
        'titre' => $titre,
    ])
</li>
