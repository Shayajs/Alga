@extends('layouts.app')

@section('title', 'Alga — House command center')
@section('subtitle', 'House ops · UTC+2')
@section('body_class', 'page-accueil')

@php
    $legende = [
        ['cle' => 'fait', 'en' => 'DONE', 'fr' => 'Fait'],
        ['cle' => 'a_faire', 'en' => 'TODO', 'fr' => 'À faire'],
        ['cle' => 'en_retard', 'en' => 'LATE', 'fr' => 'Pas coché la veille'],
        ['cle' => 'avance', 'en' => 'AHEAD', 'fr' => 'Avance sur demain'],
    ];
@endphp

@section('content')
    <section class="hq-hero">
        <p class="hq-kicker">
            <span>HOUSE COMMAND CENTER</span>
            <span class="hq-dot"></span>
            <span>EUROPE / PARIS</span>
            <span class="hq-dot"></span>
            <span id="hq-clock" data-offset="{{ $fuseau }}">{{ $horloge->format('H:i:s') }}</span>
            <span>UTC+2</span>
        </p>
        <h1 class="hq-title">
            Alga.
            <span>Le board de la maison.</span>
        </h1>
        <p class="hq-lede">
            Two households. One written record.
            <em>Ops ménagères, horodatées.</em>
            Le jour bascule à 03:00 — les heures restent en UTC+2.
        </p>
        <div class="hq-actions">
            @auth
                <a class="btn btn-primary btn-inline" href="{{ route('tableau.aujourdhui') }}">Enter ops · Notre lot</a>
                <a class="btn btn-ghost btn-inline" href="{{ route('tableau.historique') }}">7 jours</a>
                @if ($autreBloc)
                    <button type="button" class="btn btn-ghost btn-inline" data-modale="modale-autre-cell">THE OTHER CELL</button>
                @endif
            @else
                <a class="btn btn-primary btn-inline" href="{{ route('login') }}">Enter ops · Connexion</a>
            @endauth
        </div>
        <ul class="hq-legend" aria-label="Légende des couleurs">
            @foreach ($legende as $item)
                <li class="hq-legend-item hq-legend-item-{{ $item['cle'] }}">
                    <span class="hq-swatch"></span>
                    <strong>{{ $item['en'] }}</strong>
                    <span>{{ $item['fr'] }}</span>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="hq-metrics" aria-label="Compteurs du jour">
        <article class="hq-metric hq-metric-fait">
            <p class="hq-metric-en">DONE</p>
            <p class="hq-metric-value">{{ $compteurs['fait'] }}</p>
            <p class="hq-metric-fr">Fait</p>
        </article>
        <article class="hq-metric hq-metric-a_faire">
            <p class="hq-metric-en">TODO</p>
            <p class="hq-metric-value">{{ $compteurs['a_faire'] }}</p>
            <p class="hq-metric-fr">À faire</p>
        </article>
        <article class="hq-metric hq-metric-en_retard">
            <p class="hq-metric-en">LATE</p>
            <p class="hq-metric-value">{{ $compteurs['en_retard'] }}</p>
            <p class="hq-metric-fr">Pas coché</p>
        </article>
        <article class="hq-metric hq-metric-avance">
            <p class="hq-metric-en">AHEAD</p>
            <p class="hq-metric-value">{{ $compteurs['avance'] }}</p>
            <p class="hq-metric-fr">Avance</p>
        </article>
    </section>

    <p class="hq-dayline">
        <span>OPERATIONAL DAY</span>
        {{ $jour->translatedFormat('l j F Y') }}
        <span>· depuis 03:00</span>
    </p>

    <div class="hq-systems">
        <a class="hq-system" href="{{ auth()->check() ? route('tableau.aujourdhui') : route('login') }}">
            <p class="hq-system-en">DAILY OPS</p>
            <p class="hq-system-fr">Lot quotidien</p>
        </a>
        <a class="hq-system" href="{{ auth()->check() ? route('tableau.aujourdhui') : route('login') }}">
            <p class="hq-system-en">WEEKLY ROOMS</p>
            <p class="hq-system-fr">Pièces de la semaine</p>
        </a>
        <a class="hq-system" href="{{ auth()->check() ? route('absences.index') : route('login') }}">
            <p class="hq-system-en">ABSENCES</p>
            <p class="hq-system-fr">Hors maison ≥ 24 h</p>
        </a>
        <a class="hq-system" href="{{ auth()->check() ? route('regles.index') : route('login') }}">
            <p class="hq-system-en">HOUSE RULES</p>
            <p class="hq-system-fr">Le règlement écrit</p>
        </a>
    </div>

    <div class="couples-grid couples-grid-{{ $parCouple->count() }} hq-boards">
        @foreach ($parCouple as $bloc)
            @include('partials.cellule', ['bloc' => $bloc])
        @endforeach
    </div>

    @auth
        @if ($autreBloc)
            @php $pctAutre = $autreBloc['total'] > 0 ? (int) round(100 * $autreBloc['faits'] / $autreBloc['total']) : 0; @endphp
            <button type="button" class="btn-other-cell" data-modale="modale-autre-cell">
                <span class="hq-board-en">THE OTHER CELL</span>
                <strong>{{ $autreBloc['couple']->nom }}</strong>
                <span>{{ $autreBloc['faits'] }} / {{ $autreBloc['total'] }} done · aperçu</span>
                <div class="hq-progress" aria-hidden="true"><span style="width: {{ $pctAutre }}%"></span></div>
            </button>

            <dialog class="modale modale-cell" id="modale-autre-cell" aria-labelledby="modale-autre-cell-titre">
                <article class="modale-sheet">
                    <header class="modale-head">
                        <div>
                            <p class="modale-kicker">THE OTHER CELL</p>
                            <h2 id="modale-autre-cell-titre">{{ $autreBloc['couple']->nom }}</h2>
                            <p class="modale-meta">{{ $autreBloc['faits'] }} / {{ $autreBloc['total'] }} done · lecture seule</p>
                        </div>
                        <form method="dialog">
                            <button type="submit" class="modale-close" aria-label="Fermer">×</button>
                        </form>
                    </header>
                    @include('partials.cellule', [
                        'bloc' => $autreBloc,
                        'lectureSeule' => true,
                        'dansModale' => true,
                    ])
                    <footer class="modale-foot">
                        <form method="dialog">
                            <button type="submit" class="btn btn-ghost">Fermer</button>
                        </form>
                    </footer>
                </article>
            </dialog>
        @endif
    @endauth

    <p class="hq-footnote">
        Day rolls at 03:00 · Heures affichées UTC+2 · Ce qui est coché est vrai.
    </p>
@endsection

@section('scripts')
    <script>
        (function () {
            var el = document.getElementById('hq-clock');
            if (!el) return;
            var fmt = new Intl.DateTimeFormat('fr-FR', {
                timeZone: 'Europe/Paris',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            });
            function tick() { el.textContent = fmt.format(new Date()); }
            tick();
            setInterval(tick, 1000);
        })();
    </script>
@endsection
