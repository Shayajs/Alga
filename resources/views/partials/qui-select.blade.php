@php
    $completion = $affectation->completion ?? null;
    $valeurQui = $completion?->credit_externe
        ? 'x-'.$completion->credit_externe
        : ($completion?->auteur_id ? 'u-'.$completion->auteur_id : '');
@endphp
<select name="qui">
    <option value="" @selected($valeurQui === '')>{{ $couple->nom }}</option>
    @foreach ($couple->membres as $membre)
        <option value="u-{{ $membre->id }}" @selected($valeurQui === 'u-'.$membre->id)>{{ $membre->name }}</option>
    @endforeach
    @foreach (\App\Models\Completion::CREDITS_EXTERNES as $nom)
        <option value="x-{{ $nom }}" @selected($valeurQui === 'x-'.$nom)>{{ $nom }}</option>
    @endforeach
</select>
