@extends('layouts.app')

@section('title', 'Absences — Alga')
@section('subtitle', 'Départ de 24 h ou plus')

@section('content')
    <h1 class="page-title">Hors maison</h1>
    <p class="lede">Si un couple part plus de 24 h, on le déclare ici. L’heure de la déclaration est figée, comme le reste.</p>

    <form method="POST" action="{{ route('absences.store') }}" class="card-form">
        @csrf
        <label>
            Couple
            <select name="couple_id" required>
                <option value="">Choisir…</option>
                @foreach ($couples as $couple)
                    <option value="{{ $couple->id }}" @selected(old('couple_id') == $couple->id)>{{ $couple->nom }}</option>
                @endforeach
            </select>
        </label>
        @error('couple_id')
            <p class="field-err">{{ $message }}</p>
        @enderror

        <label>
            Départ
            <input type="datetime-local" name="debut_a" value="{{ old('debut_a') }}" required>
        </label>
        @error('debut_a')
            <p class="field-err">{{ $message }}</p>
        @enderror

        <label>
            Retour prévu
            <input type="datetime-local" name="fin_prevue_a" value="{{ old('fin_prevue_a') }}" required>
        </label>
        @error('fin_prevue_a')
            <p class="field-err">{{ $message }}</p>
        @enderror

        <button type="submit" class="btn btn-primary">Déclarer l’absence</button>
    </form>

    <h2 class="block-title">Déclarations</h2>
    @forelse ($absences as $absence)
        <article class="absence {{ $absence->estHorsMaison() ? '' : 'absence-short' }}">
            <p class="absence-who">{{ $absence->couple->nom }}</p>
            <p>
                Du {{ $absence->debut_a->translatedFormat('l j F') }} à {{ $absence->debut_a->format('H:i') }}
                au {{ $absence->fin_prevue_a->translatedFormat('l j F') }} à {{ $absence->fin_prevue_a->format('H:i') }}
            </p>
            <p class="stamp">
                Déclaré par {{ $absence->declarant->name }}
                le {{ $absence->declare_a->translatedFormat('l j F') }}
                à {{ $absence->declare_a->format('H:i:s') }}
            </p>
        </article>
    @empty
        <p class="empty">Aucune absence déclarée.</p>
    @endforelse
@endsection
