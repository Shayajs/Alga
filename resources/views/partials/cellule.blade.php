@php
    $lectureSeule = $lectureSeule ?? false;
    $dansModale = $dansModale ?? false;
    $pct = $bloc['total'] > 0 ? (int) round(100 * $bloc['faits'] / $bloc['total']) : 0;
@endphp

@if ($dansModale)
    <div class="cellule-modale">
        @include('partials.cellule-cartes', ['bloc' => $bloc, 'lectureSeule' => $lectureSeule])
    </div>
@else
    <section class="hq-board {{ auth()->check() ? ($bloc['estLeMien'] ? 'hq-board-mine' : 'hq-board-other') : '' }}">
        <header class="hq-board-head">
            <p class="hq-board-en">
                @if (! auth()->check())
                    CELL
                @elseif ($bloc['estLeMien'])
                    YOUR CELL
                @else
                    THE OTHER CELL
                @endif
            </p>
            <h2>{{ $bloc['couple']->nom }}</h2>
            <p class="hq-board-meta">{{ $bloc['faits'] }} / {{ $bloc['total'] }} done</p>
            <div class="hq-progress" aria-hidden="true"><span style="width: {{ $pct }}%"></span></div>
        </header>
        @include('partials.cellule-cartes', ['bloc' => $bloc, 'lectureSeule' => $lectureSeule])
    </section>
@endif
