<?php

namespace App\Http\Controllers;

use App\Models\Affectation;
use App\Services\JournalAffectation;
use App\Support\CreditTache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

        $credit = CreditTache::depuis($request, array_filter([$affectation->couple_id]));

        $affectation->loadMissing(['couple', 'tache']);
        $journal->appliquer(
            $affectation,
            $user,
            true,
            $credit['auteur_id'],
            now(),
            null,
            $credit['credit_externe'],
        );

        return back()->with('ok', 'C’est noté pour '.$this->qui($affectation).', horodaté maintenant.')
            ->with('ouvrir_piece', $affectation->cleOuverture())
            ->with('ouvrir_tache', $affectation->id);
    }

    public function voler(Request $request, Affectation $affectation, JournalAffectation $journal): RedirectResponse
    {
        $user = $request->user();

        if ($user->couple_id === null || $user->couple_id === $affectation->couple_id) {
            return back()->with('erreur', 'Ce lot est déjà le vôtre.');
        }

        if ($affectation->completion()->exists()) {
            return back()->with('erreur', 'Déjà cochée — ouvre la tâche pour corriger.');
        }

        $credit = CreditTache::depuis($request, [$user->couple_id]);
        $affectation->loadMissing(['couple', 'tache']);
        $journal->appliquer(
            $affectation,
            $user,
            true,
            $credit['auteur_id'],
            now(),
            null,
            $credit['credit_externe'],
        );

        $retour = back()->with('ok', 'C’est noté : vous avez fait la tâche de '.$affectation->couple->nom.'.');

        if ($request->boolean('depuis_lot_autre')) {
            return $retour->with('ouvrir_lot_autre', true);
        }

        return $retour->with('ouvrir_tache', $affectation->id);
    }

    private function qui(Affectation $affectation): string
    {
        $affectation->loadMissing('completion');

        if ($affectation->completion) {
            $affectation->completion->setRelation('affectation', $affectation);

            return $affectation->completion->libelleAffiche();
        }

        return $affectation->couple?->nom ?? '—';
    }
}
