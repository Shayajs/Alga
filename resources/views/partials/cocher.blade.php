@php
    $avance = $avance ?? false;
    $statut = $affectation->statut();
    $couple = $affectation->couple;
@endphp
<form method="POST" action="{{ route('completions.store', $affectation) }}" class="cocher">
    @csrf
    <label class="cocher-qui">
        Qui l’a fait
        @include('partials.qui-select', ['affectation' => $affectation, 'couple' => $couple])
    </label>
    <button type="submit" class="btn {{ ! $avance && $statut === 'en_retard' ? 'btn-warn' : 'btn-primary' }}">
        @if ($avance)
            On le fait en avance
        @elseif ($statut === 'en_retard')
            Pas coché — on coche quand même
        @else
            C’est fait
        @endif
    </button>
</form>
