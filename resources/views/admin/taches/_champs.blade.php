@php
    $tache = $tache ?? null;
@endphp
<label>
    Intitulé
    <input type="text" name="titre" value="{{ old('titre', $tache->titre ?? '') }}" required>
</label>
<label>
    Pièce
    <select name="piece" required>
        @foreach ($pieces as $piece)
            <option value="{{ $piece }}" @selected(old('piece', $tache->piece ?? '') === $piece)>{{ $piece }}</option>
        @endforeach
    </select>
</label>
<label>
    Fréquence
    <select name="frequence" required>
        @foreach ($frequences as $valeur => $libelle)
            <option value="{{ $valeur }}" @selected(old('frequence', $tache->frequence ?? 'quotidien') === $valeur)>{{ $libelle }}</option>
        @endforeach
    </select>
</label>
<label>
    Groupe (rotation)
    <select name="groupe">
        <option value="">—</option>
        @foreach (config('maison.groupes') as $valeur => $libelle)
            <option value="{{ $valeur }}" @selected(old('groupe', $tache->groupe ?? '') === $valeur)>{{ $libelle }}</option>
        @endforeach
    </select>
</label>
<label>
    Heure limite
    <input type="time" name="heure_limite" value="{{ old('heure_limite', isset($tache) ? substr((string) $tache->heure_limite, 0, 5) : '21:00') }}" required>
</label>
<label>
    Pénibilité (1–5)
    <input type="number" name="penibilite" min="1" max="5" value="{{ old('penibilite', $tache->penibilite ?? 2) }}" required>
</label>
<label>
    Ordre
    <input type="number" name="ordre" min="0" max="99" value="{{ old('ordre', $tache->ordre ?? 0) }}">
</label>
