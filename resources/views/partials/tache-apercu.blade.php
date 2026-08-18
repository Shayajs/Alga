@php
    $statut = $affectation->statut();
    $titre = ($affectation->tache->frequence === 'hebdo' ? $affectation->tache->piece.' · ' : '').$affectation->tache->titre;
@endphp
<li class="task task-compact task-{{ $statut }}">
    <div class="task-head">
        <p class="task-title">{{ $titre }}</p>
        <span class="status-chip status-chip-{{ $statut }}">{{ $affectation->codeStatut() }} · {{ $affectation->libelleStatut() }}</span>
    </div>
    @if ($affectation->estFaite())
        <p class="stamp">
            {{ $affectation->libelleFait() }}
            à {{ $affectation->completion->fait_a->timezone(config('app.timezone'))->format('H:i') }}
        </p>
    @elseif ($statut === 'en_retard')
        <p class="stamp stamp-late">Non coché après 03:00</p>
    @endif
</li>
