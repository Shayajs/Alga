<?php

namespace App\Http\Controllers;

use App\Models\Affectation;
use App\Services\JournalAffectation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompletionController extends Controller
{
    public function store(Request $request, Affectation $affectation, JournalAffectation $journal): RedirectResponse
    {
        $user = $request->user();

        if (! $affectation->peutEtreModifieePar($user)) {
            return back()->with('erreur', 'C’est le lot de l’autre couple.');
        }

        if ($affectation->completion()->exists()) {
            return back()->with('erreur', 'Déjà cochée — ouvre la tâche pour corriger l’heure ou le crédit.');
        }

        $data = $request->validate([
            'auteur_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('couple_id', $affectation->couple_id),
            ],
        ]);

        $auteurId = $data['auteur_id'] ?? null;
        $auteurId = $auteurId ? (int) $auteurId : null;

        $affectation->loadMissing(['couple', 'tache']);
        $journal->appliquer($affectation, $user, true, $auteurId, now());

        $qui = $auteurId
            ? ($affectation->couple->membres()->whereKey($auteurId)->value('name') ?? $affectation->couple->nom)
            : $affectation->couple->nom;

        return back()->with('ok', 'C’est noté pour '.$qui.', horodaté maintenant.')
            ->with('ouvrir_piece', $affectation->cleOuverture())
            ->with('ouvrir_tache', $affectation->id);
    }
}
