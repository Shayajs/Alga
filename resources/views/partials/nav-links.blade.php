<a href="{{ route('accueil') }}" class="{{ request()->routeIs('accueil') ? 'on' : '' }}">Accueil</a>
@auth
    <a href="{{ route('tableau.aujourdhui') }}" class="{{ request()->routeIs('tableau.aujourdhui') ? 'on' : '' }}">Notre lot</a>
    <a href="{{ route('tableau.historique') }}" class="{{ request()->routeIs('tableau.historique') ? 'on' : '' }}">7 jours</a>
    <a href="{{ route('absences.index') }}" class="{{ request()->routeIs('absences.*') ? 'on' : '' }}">Absences</a>
    <a href="{{ route('regles.index') }}" class="{{ request()->routeIs('regles.*') ? 'on' : '' }}">Règles</a>
@else
    <a href="{{ route('login') }}" class="{{ request()->routeIs('login') ? 'on' : '' }}">Connexion</a>
@endauth
