@forelse ($bloc['items']->groupBy(fn ($a) => $a->tache->cleAffichage()) as $items)
    @php $tache = $items->first()->tache; @endphp
    @include('partials.piece-card', [
        'id' => 'cell-'.$bloc['couple']->id.'-'.$tache->frequence.'-'.($tache->groupe ?: \Illuminate\Support\Str::slug($tache->piece)).($lectureSeule ?? false ? '-apercu' : ''),
        'piece' => $tache->libelleLot(),
        'kicker' => $bloc['couple']->nom,
        'affectations' => $items->values(),
        'peutCocher' => ! ($lectureSeule ?? false) && $bloc['estLeMien'] && auth()->check(),
        'lectureSeule' => $lectureSeule ?? false,
    ])
@empty
    <p class="empty">Standby — rien sur le board.</p>
@endforelse
