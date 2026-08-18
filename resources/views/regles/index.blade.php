@extends('layouts.app')

@section('title', 'Règles — Alga')
@section('subtitle', 'Pièces communes')

@section('content')
    <h1 class="page-title">Règles de la maison</h1>
    <p class="lede">Les pièces à vivre ensemble. Numérotées, applicables, pas négociables chaque soir.</p>

    @foreach ($parPiece as $piece => $regles)
        <section class="rules-block">
            <h2>{{ $piece }}</h2>
            <ol class="regles-num">
                @foreach ($regles as $regle)
                    <li>{{ $regle->contenu }}</li>
                @endforeach
            </ol>
        </section>
    @endforeach
@endsection
