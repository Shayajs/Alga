<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#2b2b2b" id="theme-color">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}" sizes="64x64">
    <link rel="icon" type="image/png" href="{{ asset('icon-192.png') }}" sizes="192x192">
    @include('partials.pwa-head')
    <title>@yield('title', 'Alga')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Syne:wght@600;700;800&display=swap">
    <script>
        (function () {
            try {
                var choisi = localStorage.getItem('alga-theme');
                var sombre = choisi ? choisi === 'sombre' : window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.dataset.theme = sombre ? 'sombre' : 'clair';
            } catch (e) {
                document.documentElement.dataset.theme = 'clair';
            }
        })();
    </script>
    <link rel="stylesheet" href="{{ asset('css/alga.css') }}">
</head>
<body class="@yield('body_class') {{ auth()->check() ? 'is-auth' : '' }} {{ auth()->user()?->est_admin ? 'is-admin' : '' }}">
    <div class="shell">
        <header class="top">
            <a class="brand-link" href="{{ route('accueil') }}">
                <img class="brand-mark" src="{{ asset('img/alga.png') }}" width="40" height="40" alt="">
                <span>
                    <p class="brand">Alga</p>
                    <p class="top-sub">@yield('subtitle', 'La maison')</p>
                </span>
            </a>
            <div class="top-actions">
                <nav class="nav-pc" aria-label="Navigation">
                    @include('partials.nav-links')
                </nav>
                <button type="button" class="linkish" id="btn-theme" aria-label="Changer de thème">Thème</button>
                @auth
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="linkish">{{ auth()->user()->name }} · sortir</button>
                    </form>
                @endauth
            </div>
        </header>

        @if (session('ok'))
            <p class="flash flash-ok">{{ session('ok') }}</p>
        @endif
        @if (session('erreur'))
            <p class="flash flash-err">{{ session('erreur') }}</p>
        @endif

        <main>
            @yield('content')
        </main>
    </div>

    @include('partials.pwa-install')

    <nav class="dock" aria-label="Navigation mobile">
        @include('partials.nav-links')
    </nav>

    <script src="{{ asset('js/theme.js') }}"></script>
    <script src="{{ asset('js/modale.js') }}"></script>
    <script src="{{ asset('js/pwa.js') }}"></script>
    @yield('scripts')
</body>
</html>
