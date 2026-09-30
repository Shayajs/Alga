@extends('layouts.app')

@section('title', '7 jours — Alga')
@section('subtitle', $lundi->translatedFormat('j M').' → '.$dimanche->translatedFormat('j M'))
@section('body_class', 'page-kanban')

@section('content')
    <h1 class="page-title">Semaine</h1>
    <p class="lede">
        Tes tâches seulement. Ménage du salon un jour, cuisine et linge le lendemain.
        Salon / toilettes et salle de bain : chaque lundi. ≈ 15 min. Jour à 03:00, UTC+2.
        Les semaines passées, et la semaine prochaine.
    </p>

    <nav class="week-nav" aria-label="Changer de semaine">
        @if ($lundiPrecedent)
            <a class="btn btn-ghost" href="{{ route('tableau.historique', ['semaine' => $lundiPrecedent->toDateString()]) }}">Semaine précédente</a>
        @else
            <span></span>
        @endif
        <p>
            <span class="week-nav-label">
                @if ($estSemaineCourante)
                    Cette semaine
                @elseif ($estSemaineProchaine)
                    Semaine prochaine
                @else
                    Semaine passée
                @endif
            </span>
            @unless ($estSemaineCourante)
                <a href="{{ route('tableau.historique') }}">Revenir à cette semaine</a>
            @endunless
        </p>
        @if ($lundiSuivant)
            <a class="btn btn-ghost" href="{{ route('tableau.historique', ['semaine' => $lundiSuivant->toDateString()]) }}">Semaine suivante</a>
        @endif
    </nav>

    <div class="week-wrap">
        <table class="week-board">
            <thead>
                <tr>
                    <th></th>
                    @foreach ($colonnes as $col)
                        <th class="{{ $col['estAujourdhui'] ? 'is-today' : '' }}">
                            <span>{{ $col['date']->translatedFormat('D') }}</span>
                            {{ $col['date']->format('j/n') }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th>Tous les jours</th>
                    @foreach ($colonnes as $col)
                        @php $lot = $col['lot']; @endphp
                        <td class="week-cell week-cell-{{ $lot['tone'] }} {{ $col['estAujourdhui'] ? 'is-today' : '' }}">
                            @if ($lot['total'] > 0)
                                <strong>{{ $lot['titre'] }}</strong>
                                <span>{{ $lot['faits'] }}/{{ $lot['total'] }}</span>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
                <tr>
                    <th>{{ $hebdo['titre'] ?: 'Cette semaine' }}</th>
                    <td colspan="7" class="week-cell week-cell-{{ $hebdo['tone'] }} week-cell-span">
                        @if ($hebdo['total'] > 0)
                            <span>toute la semaine · {{ $hebdo['faits'] }}/{{ $hebdo['total'] }}</span>
                        @else
                            <span class="muted">—</span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    @if ($hebdo['total'] > 0)
        <h2 class="block-title">{{ $hebdo['titre'] }}</h2>
        <p class="lot-kicker">
            @if ($estSemaineProchaine)
                Semaine prochaine
            @elseif ($estSemaineCourante)
                Cette semaine
            @else
                Semaine passée
            @endif
            · ≈ 15 min
        </p>
        <div class="kanban-week-tasks">
            @include('partials.taches-liste', ['affectations' => $hebdo['lignes']])
        </div>
    @endif

    <div class="kanban" aria-label="Kanban de la semaine">
        @foreach ($colonnes as $col)
            <section class="kanban-col {{ $col['estAujourdhui'] ? 'is-today' : '' }}">
                <header class="kanban-col-head">
                    <p>{{ $col['date']->translatedFormat('l') }}</p>
                    <h2>{{ $col['date']->translatedFormat('j M') }}</h2>
                    @if ($col['lot']['titre'])
                        <p class="kanban-lot">{{ $col['lot']['titre'] }}</p>
                    @endif
                    @if ($col['estAujourdhui'])
                        <span class="status-chip status-chip-a_faire">TODAY</span>
                    @endif
                </header>
                @if ($col['lignes']->isNotEmpty())
                    @include('partials.taches-liste', ['affectations' => $col['lignes']])
                @endif
            </section>
        @endforeach
    </div>
@endsection
