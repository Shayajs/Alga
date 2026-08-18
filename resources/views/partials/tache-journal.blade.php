@php
    $evenements = $evenements ?? collect();
@endphp
<section class="task-log" aria-label="Historique des changements">
    <h3>Historique</h3>
    @forelse ($evenements as $evenement)
        <article class="task-log-item">
            <time datetime="{{ $evenement->created_at->toIso8601String() }}">{{ $evenement->horaire() }}</time>
            <p>{{ $evenement->resume }}</p>
            @if ($evenement->commentaire && $evenement->action === 'note')
                <p class="task-log-note">« {{ $evenement->commentaire }} »</p>
            @endif
        </article>
    @empty
        <p class="muted">Aucun changement enregistré pour l’instant.</p>
    @endforelse
</section>
