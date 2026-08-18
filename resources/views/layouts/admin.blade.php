<!DOCTYPE html>
<html lang="fr" data-theme="sombre">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1c1c1c">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}" sizes="64x64">
    @include('partials.pwa-head')
    <title>@yield('title', 'Console') — Alga SYS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=Outfit:wght@400;500;600;700&family=Syne:wght@600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/alga.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body class="ops">
    <aside class="ops-side">
        <a class="ops-brand" href="{{ route('admin.index') }}">
            <img src="{{ asset('img/alga.png') }}" width="36" height="36" alt="">
            <span>
                <strong>ALGA SYS</strong>
                <em>restricted console</em>
            </span>
        </a>
        <p class="ops-kicker">ENDPOINT /admin</p>
        <nav class="ops-nav" aria-label="Console">
            <a href="{{ route('admin.index') }}" class="{{ request()->routeIs('admin.index') ? 'on' : '' }}">
                <span>01</span> Console
            </a>
            <a href="{{ route('admin.taches.index') }}" class="{{ request()->routeIs('admin.taches.*') ? 'on' : '' }}">
                <span>02</span> Catalogue
            </a>
            <a href="{{ route('admin.regles.index') }}" class="{{ request()->routeIs('admin.regles.*') ? 'on' : '' }}">
                <span>03</span> Règles
            </a>
        </nav>
        <div class="ops-side-foot">
            <p class="ops-kicker">OPERATOR</p>
            <p class="ops-operator">{{ auth()->user()->name }} · {{ auth()->user()->pseudo }}</p>
            <a class="ops-exit" href="{{ route('accueil') }}">← Maison</a>
        </div>
    </aside>

    <div class="ops-main">
        <header class="ops-top">
            <div>
                <p class="ops-kicker">@yield('ops_kicker', 'SYSTEM')</p>
                <h1>@yield('ops_title', 'Console')</h1>
            </div>
            <p class="ops-clock">
                <span id="ops-clock">{{ now()->timezone(config('app.timezone'))->format('H:i:s') }}</span>
                <span>UTC+2</span>
            </p>
        </header>

        @if (session('ok'))
            <p class="flash flash-ok">{{ session('ok') }}</p>
        @endif
        @if (session('erreur'))
            <p class="flash flash-err">{{ session('erreur') }}</p>
        @endif

        <main class="ops-content">
            @yield('content')
        </main>
    </div>

    <script>
        (function () {
            var el = document.getElementById('ops-clock');
            if (!el) return;
            var fmt = new Intl.DateTimeFormat('fr-FR', {
                timeZone: 'Europe/Paris', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false
            });
            function tick() { el.textContent = fmt.format(new Date()); }
            tick();
            setInterval(tick, 1000);
        })();
    </script>
    <script src="{{ asset('js/pwa.js') }}"></script>
    @yield('scripts')
</body>
</html>
