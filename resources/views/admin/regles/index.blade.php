@extends('layouts.admin')

@section('title', 'Règles')
@section('ops_kicker', 'POLICY')
@section('ops_title', 'Règles')

@section('content')
    <p class="ops-lede">Une ligne = une règle, numérotée. Salon et cuisine, douche, toilettes.</p>

    <h2 class="block-title">Ajouter une ligne</h2>
    <form method="POST" action="{{ route('admin.regles.store') }}" class="card-form">
        @csrf
        <label>
            Pièce
            <select name="piece" required>
                @foreach ($pieces as $piece)
                    <option value="{{ $piece }}" @selected(old('piece') === $piece)>{{ $piece }}</option>
                @endforeach
            </select>
        </label>
        <label>
            Règle
            <input type="text" name="contenu" value="{{ old('contenu') }}" required>
        </label>
        <button class="btn btn-primary" type="submit">Ajouter</button>
    </form>

    @foreach ($parPiece as $piece => $regles)
        <section class="rules-block">
            <h2>{{ $piece }}</h2>
            @forelse ($regles as $i => $regle)
                <form method="POST" action="{{ route('admin.regles.update', $regle) }}" class="card-form">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="piece" value="{{ $piece }}">
                    <p class="task-piece">{{ $i + 1 }}.</p>
                    <label>
                        Texte
                        <input type="text" name="contenu" value="{{ $regle->contenu }}" required>
                    </label>
                    <label>
                        Ordre
                        <input type="number" name="ordre" value="{{ $regle->ordre }}" min="0">
                    </label>
                    <div class="admin-actions">
                        <button class="btn btn-primary btn-inline" type="submit">Enregistrer</button>
                        <button class="linkish" form="del-regle-{{ $regle->id }}" type="submit" onclick="return confirm('Retirer cette ligne ?')">Supprimer</button>
                    </div>
                </form>
                <form id="del-regle-{{ $regle->id }}" method="POST" action="{{ route('admin.regles.destroy', $regle) }}">
                    @csrf
                    @method('DELETE')
                </form>
            @empty
                <p class="empty">Pas encore de règle ici.</p>
            @endforelse
        </section>
    @endforeach
@endsection
