@extends('layouts.app')

@section('title', 'Planning — Alga')
@section('subtitle', $monCouple->nom)

@section('content')
    <h1 class="page-title">Votre lot</h1>
    <p class="lede">
        Tes tâches du jour, et celles de la semaine.
        Quotidien : ménage du salon un jour, cuisine et linge le lendemain — ça s’inverse chaque jour.
        Hebdo : salon et cuisine vs toilettes et salle de bain, chaque lundi.
        ≈ 15 min. Clique une tâche pour la modifier.
    </p>

    @if ($lots->isEmpty() && $lotsAvance->isEmpty() && $lotsAutre->isEmpty())
        <p class="empty">Rien à faire de votre côté aujourd’hui.</p>
    @endif

    @foreach ($lots as $lot)
        <h2 class="block-title">{{ $lot['piece'] }}</h2>
        <p class="lot-kicker">{{ $lot['libelle'] }}</p>
        @include('partials.taches-liste', ['affectations' => $lot['affectations']])
    @endforeach

    @if ($lotsAutre->isNotEmpty())
        <section class="lot-autre">
            <h2 class="block-title">Lot de {{ $autreCouple?->nom ?? 'l’autre équipe' }}</h2>
            <p class="lot-kicker">Ouvre la liste, et coche seulement ce que vous avez fait</p>
            <ul class="lot-legend" aria-label="Couleurs quand une équipe fait le lot de l’autre">
                <li><span class="hq-swatch swatch-volee"></span> Violet — l’autre équipe a fait notre tâche</li>
                <li><span class="hq-swatch swatch-prise"></span> Turquoise — on a fait leur tâche</li>
            </ul>
            <button type="button" class="btn btn-prise" data-modale="modale-lot-autre">On a fait leur job</button>

            @if ($prisesAutre->isNotEmpty())
                @include('partials.taches-liste', ['affectations' => $prisesAutre])
            @endif

            <dialog class="modale" id="modale-lot-autre" aria-labelledby="modale-lot-autre-titre" @if (session('ouvrir_lot_autre')) data-open @endif>
                <article class="modale-sheet">
                    <header class="modale-head">
                        <div>
                            <p class="modale-kicker">Leur lot</p>
                            <h2 id="modale-lot-autre-titre">{{ $autreCouple?->nom ?? 'L’autre équipe' }}</h2>
                            <p class="modale-meta">Une tâche à la fois. Rien n’est coché tout seul.</p>
                        </div>
                        <form method="dialog">
                            <button type="submit" class="modale-close" aria-label="Fermer">×</button>
                        </form>
                    </header>
                    @foreach ($lotsAutre as $lot)
                        <h3 class="vol-lot">{{ $lot['piece'] }}</h3>
                        <ul class="vol-liste">
                            @foreach ($lot['affectations'] as $affectation)
                                @include('partials.lot-autre-modale', [
                                    'affectation' => $affectation,
                                    'monCouple' => $monCouple,
                                ])
                            @endforeach
                        </ul>
                    @endforeach
                    <footer class="modale-foot">
                        <form method="dialog">
                            <button type="submit" class="btn btn-ghost">Fermer</button>
                        </form>
                    </footer>
                </article>
            </dialog>
        </section>
    @endif

    @if ($lotsAvance->isNotEmpty())
        <h2 class="block-title">Demain · {{ $lotDemain ?: $lendemain->translatedFormat('l j F') }}</h2>
        <p class="lot-kicker">{{ $lendemain->translatedFormat('l j F') }} · l’autre lot, tu peux cocher en avance</p>
        @foreach ($lotsAvance as $lot)
            @include('partials.taches-liste', [
                'affectations' => $lot['affectations'],
                'avance' => true,
            ])
        @endforeach
    @endif
@endsection
