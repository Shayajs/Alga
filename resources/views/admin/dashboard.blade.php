@extends('layouts.admin')

@section('title', 'Console')
@section('ops_kicker', 'CONTROL PLANE')
@section('ops_title', 'Console')

@section('content')
    <p class="ops-lede">
        Journée opérationnelle <strong>{{ $jour->translatedFormat('l j F Y') }}</strong>
        · bascule 03:00 · heures UTC+2.
        Ce qui est coché est figé.
    </p>

    <section class="ops-metrics">
        <article class="ops-metric">
            <p class="ops-kicker">DONE</p>
            <p class="ops-metric-value ops-ok">{{ $compteurs['fait'] }}</p>
            <p>Fait aujourd’hui</p>
        </article>
        <article class="ops-metric">
            <p class="ops-kicker">TODO</p>
            <p class="ops-metric-value ops-todo">{{ $compteurs['a_faire'] }}</p>
            <p>Encore ouvert</p>
        </article>
        <article class="ops-metric">
            <p class="ops-kicker">LATE</p>
            <p class="ops-metric-value ops-late">{{ $compteurs['en_retard'] }}</p>
            <p>Pas coché</p>
        </article>
        <article class="ops-metric">
            <p class="ops-kicker">AWAY</p>
            <p class="ops-metric-value">{{ $compteurs['absences'] }}</p>
            <p>Absences actives</p>
        </article>
    </section>

    <div class="ops-grid">
        <section class="ops-panel">
            <header class="ops-panel-head">
                <p class="ops-kicker">ROSTER</p>
                <h2>Opérateurs</h2>
            </header>
            <table class="ops-table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Pseudo</th>
                        <th>Cell</th>
                        <th>Rôle</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($membres as $membre)
                        <tr>
                            <td>{{ $membre->name }}</td>
                            <td class="ops-mono">{{ $membre->pseudo }}</td>
                            <td>{{ $membre->couple?->nom }}</td>
                            <td>
                                @if ($membre->est_admin)
                                    <span class="ops-badge ops-badge-admin">ADMIN</span>
                                @else
                                    <span class="ops-badge">USER</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>

        <section class="ops-panel">
            <header class="ops-panel-head">
                <p class="ops-kicker">INVENTORY</p>
                <h2>Système</h2>
            </header>
            <ul class="ops-kv">
                <li><span>Catalogue</span> <strong>{{ $compteurs['taches'] }} tâches</strong></li>
                <li><span>Règles</span> <strong>{{ $compteurs['regles'] }} lignes</strong></li>
                <li><span>Affectations</span> <strong>{{ $compteurs['affectations'] }}</strong></li>
                <li><span>Horizon</span>
                    <strong>
                        @if ($horizon['debut'] && $horizon['fin'])
                            {{ \Illuminate\Support\Carbon::parse($horizon['debut'])->format('d/m') }}
                            → {{ \Illuminate\Support\Carbon::parse($horizon['fin'])->format('d/m/Y') }}
                        @else
                            vide
                        @endif
                    </strong>
                </li>
                <li><span>Couples</span> <strong>{{ $couples->count() }}</strong></li>
            </ul>
            <form method="POST" action="{{ route('admin.planning.generer') }}" class="ops-danger" onsubmit="return confirm('Régénérer 8 semaines ? Les cochages déjà faits restent.')">
                @csrf
                <p class="ops-kicker">DANGER ZONE</p>
                <p>Recalcule le planning. Les completions déjà horodatées ne bougent pas.</p>
                <button class="btn btn-warn btn-inline" type="submit">Régénérer le planning</button>
            </form>
        </section>
    </div>

    <section class="ops-panel">
        <header class="ops-panel-head">
            <p class="ops-kicker">AUDIT LOG</p>
            <h2>Derniers cochages</h2>
        </header>
        @if ($journal->isEmpty())
            <p class="empty">Aucun événement.</p>
        @else
            <table class="ops-table">
                <thead>
                    <tr>
                        <th>Heure</th>
                        <th>Opérateur</th>
                        <th>Tâche</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($journal as $ligne)
                        <tr>
                            <td class="ops-mono">{{ $ligne->fait_a->timezone(config('app.timezone'))->format('d/m H:i') }}</td>
                            <td>{{ $ligne->libelleAffiche() }}</td>
                            <td>{{ $ligne->affectation?->tache?->titre }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
@endsection
