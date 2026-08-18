@extends('layouts.app')

@section('title', 'Connexion — Alga')
@section('subtitle', 'Connexion')
@section('body_class', 'page-connexion')

@section('content')
    <div class="auth-shell">
        <p class="back"><a href="{{ route('accueil') }}">← Accueil</a></p>
        <h1 class="page-title">Connexion</h1>
        <p class="lede">Ton pseudo. Première fois : tu crées un mot de passe. Ensuite, le champ mot de passe s’affiche.</p>

        @php
            $etape = session('connexion_etape', old('password') ? 'mot_de_passe' : null);
        @endphp

        <form method="POST" action="{{ route('login.store') }}" class="card-form" id="form-connexion" data-etat="{{ route('login.etat') }}" data-etape="{{ $etape }}">
            @csrf

            <label>
                Pseudo
                <input id="pseudo" type="text" name="pseudo" value="{{ old('pseudo') }}" autocomplete="username" autocapitalize="none" spellcheck="false" required>
            </label>
            @error('pseudo')
                <p class="field-err">{{ $message }}</p>
            @enderror

            <div id="bloc-mot-de-passe" class="auth-bloc {{ in_array($etape, ['mot_de_passe', 'creation'], true) ? '' : 'is-hidden' }}" data-mode="{{ $etape === 'creation' ? 'creation' : 'mot_de_passe' }}">
                <p id="auth-nom" class="auth-nom" hidden></p>
                <label id="label-password">
                    <span id="texte-password">Mot de passe</span>
                    <input id="password" type="password" name="password" autocomplete="current-password">
                </label>
                @error('password')
                    <p class="field-err">{{ $message }}</p>
                @enderror

                <label id="label-confirmation" class="{{ $etape === 'creation' ? '' : 'is-hidden' }}">
                    Confirme le mot de passe
                    <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password">
                </label>
            </div>

            <button type="submit" class="btn btn-primary" id="btn-connexion">Continuer</button>
        </form>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/connexion.js') }}"></script>
@endsection
