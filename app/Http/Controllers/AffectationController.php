<?php

namespace App\Http\Controllers;

use App\Models\Affectation;
use App\Services\JournalAffectation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class AffectationController extends Controller
{
    public function update(Request $request, Affectation $affectation, JournalAffectation $journal): RedirectResponse
    {
        $user = $request->user();

        if (! $affectation->peutEtreModifieePar($user)) {
            return back()->with('erreur', 'C’est le lot de l’autre couple.');
        }

        $affectation->loadMissing(['couple', 'tache', 'completion']);

        $data = $request->validate([
            'fait' => ['required', 'boolean'],
            'auteur_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('couple_id', $affectation->couple_id),
            ],
            'fait_a' => ['nullable', 'date'],
            'commentaire' => ['nullable', 'string', 'max:280'],
        ]);

        $fait = $request->boolean('fait');
        $auteurId = isset($data['auteur_id']) && $data['auteur_id'] !== '' ? (int) $data['auteur_id'] : null;
        $faitA = $fait
            ? Carbon::parse($data['fait_a'] ?? now(), config('app.timezone'))
            : null;
        $commentaire = isset($data['commentaire']) ? trim((string) $data['commentaire']) : null;

        $journal->appliquer($affectation, $user, $fait, $auteurId, $faitA, $commentaire ?: null);

        return back()
            ->with('ok', 'Modification enregistrée et horodatée.')
            ->with('ouvrir_piece', $affectation->cleOuverture())
            ->with('ouvrir_tache', $affectation->id);
    }
}
