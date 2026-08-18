@extends('layouts.admin')

@section('title', 'Catalogue')
@section('ops_kicker', 'CATALOGUE')
@section('ops_title', 'Tâches')

@section('content')
    <p class="ops-lede">Quotidien, semaine ou mois. Les lots A et B tournent entre les deux foyers. Après chaque changement, le planning des 8 prochaines semaines est recalculé. Ce qui est déjà coché ne bouge pas.</p>

    <form method="POST" action="{{ route('admin.planning.generer') }}" class="inline-form">
        @csrf
        <button class="btn btn-primary btn-inline" type="submit">Régénérer le planning</button>
    </form>

    <section class="ops-group-card ops-group-new">
        <header class="ops-group-head">
            <p class="ops-kicker">NOUVELLE ENTRÉE</p>
            <h3>Ajouter</h3>
        </header>
        <form method="POST" action="{{ route('admin.taches.store') }}" class="card-form card-form-compact">
            @csrf
            @include('admin.taches._champs')
            <div class="admin-actions">
                <button class="btn btn-primary" type="submit">Ajouter la tâche</button>
            </div>
        </form>
    </section>

    @foreach ($parFrequence as $frequence => $taches)
        @php
            $parGroupe = $taches->groupBy(fn ($tache) => $tache->groupe ?: '_');
        @endphp
        <section class="ops-freq">
            <h2 class="block-title">{{ $frequences[$frequence] ?? $frequence }}</h2>
            <div class="ops-group-grid {{ $parGroupe->count() === 1 ? 'ops-group-grid-1' : '' }}">
                @foreach ($parGroupe as $cleGroupe => $lot)
                    @php
                        $premiere = $lot->first();
                        $avecGroupe = $premiere->aUnGroupe();
                    @endphp
                    <article class="ops-group-card ops-group-{{ strtolower((string) $cleGroupe) }}">
                        <header class="ops-group-head">
                            <p class="ops-kicker">{{ $avecGroupe ? 'GROUPE '.$cleGroupe : 'SANS ROTATION' }}</p>
                            <h3>{{ $avecGroupe ? $premiere->libelleGroupe() : 'Toutes les pièces' }}</h3>
                            <p class="ops-group-meta">{{ $lot->count() }} tâche{{ $lot->count() > 1 ? 's' : '' }}</p>
                        </header>
                        @foreach ($lot as $tache)
                            <form method="POST" action="{{ route('admin.taches.update', $tache) }}" class="card-form card-form-compact js-autosave">
                                @csrf
                                @method('PUT')
                                @include('admin.taches._champs', ['tache' => $tache])
                                <div class="admin-actions">
                                    <p class="ops-save-status" aria-live="polite"></p>
                                    <button class="btn btn-primary btn-inline" type="submit">Enregistrer</button>
                                    <button class="linkish" form="del-tache-{{ $tache->id }}" type="submit" onclick="return confirm('Retirer cette tâche ?')">Supprimer</button>
                                </div>
                            </form>
                            <form id="del-tache-{{ $tache->id }}" method="POST" action="{{ route('admin.taches.destroy', $tache) }}">
                                @csrf
                                @method('DELETE')
                            </form>
                        @endforeach
                    </article>
                @endforeach
            </div>
        </section>
    @endforeach
@endsection

@section('scripts')
    <script src="{{ asset('js/admin-autosave.js') }}?v={{ filemtime(public_path('js/admin-autosave.js')) }}"></script>
@endsection
