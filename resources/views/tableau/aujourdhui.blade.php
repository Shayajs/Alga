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

    @if ($lots->isEmpty() && $lotsAvance->isEmpty())
        <p class="empty">Rien à faire de votre côté aujourd’hui.</p>
    @endif

    @foreach ($lots as $lot)
        <h2 class="block-title">{{ $lot['piece'] }}</h2>
        <p class="lot-kicker">{{ $lot['libelle'] }}</p>
        @include('partials.taches-liste', ['affectations' => $lot['affectations']])
    @endforeach

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
