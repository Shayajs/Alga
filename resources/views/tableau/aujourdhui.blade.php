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
            <p class="lot-kicker">Si vous l’avez fait à leur place</p>
            <ul class="lot-legend" aria-label="Couleurs quand une équipe fait le lot de l’autre">
                <li><span class="hq-swatch swatch-volee"></span> Violet — l’autre équipe a fait notre tâche</li>
                <li><span class="hq-swatch swatch-prise"></span> Turquoise — on a fait leur tâche</li>
            </ul>
            @if ($resteAutre > 0)
                <form method="POST" action="{{ route('completions.voler-lot') }}" class="lot-autre-actions">
                    @csrf
                    <button type="submit" class="btn btn-prise">On a fait leur job</button>
                </form>
            @endif
            @foreach ($lotsAutre as $lot)
                <h2 class="block-title">{{ $lot['piece'] }}</h2>
                <p class="lot-kicker">{{ $lot['libelle'] }}</p>
                @include('partials.taches-liste', [
                    'affectations' => $lot['affectations'],
                    'vol' => true,
                ])
            @endforeach
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
