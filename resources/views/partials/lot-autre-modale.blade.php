@php
    $faiteParNous = $affectation->voleeParCouple(auth()->user()->couple_id);
    $faiteParEux = $affectation->estFaite() && ! $faiteParNous;
    $titre = ($affectation->tache->frequence === 'hebdo' ? $affectation->tache->piece.' · ' : '').$affectation->tache->titre;
    $faitA = $affectation->completion?->fait_a?->timezone(config('app.timezone'))
        ?? now()->timezone(config('app.timezone'));
@endphp
<li class="vol-row">
    <p class="vol-row-titre">{{ $titre }}</p>
    @if ($faiteParEux)
        <p class="vol-row-note">Déjà cochée par {{ $affectation->libelleFait() }}.</p>
    @elseif ($faiteParNous)
        <form method="POST" action="{{ route('affectations.update', $affectation) }}" class="vol-row-form">
            @csrf
            @method('PUT')
            <input type="hidden" name="depuis_lot_autre" value="1">
            <input type="hidden" name="fait_a" value="{{ $faitA->format('Y-m-d\TH:i') }}">
            <label>
                Statut
                <select name="fait">
                    <option value="0">Pas faite</option>
                    <option value="1" selected>Faite par nous</option>
                </select>
            </label>
            <label>
                Qui
                @include('partials.qui-select', ['affectation' => $affectation, 'couple' => $monCouple])
            </label>
            <button type="submit" class="btn btn-prise">Enregistrer</button>
        </form>
    @else
        <form method="POST" action="{{ route('completions.voler', $affectation) }}" class="vol-row-form">
            @csrf
            <input type="hidden" name="depuis_lot_autre" value="1">
            <label>
                Qui
                @include('partials.qui-select', ['affectation' => $affectation, 'couple' => $monCouple])
            </label>
            <button type="submit" class="btn btn-prise">On l’a fait</button>
        </form>
    @endif
</li>
