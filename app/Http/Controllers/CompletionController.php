<?php

namespace App\Http\Controllers;

use App\Models\Affectation;
use App\Models\Couple;
use App\Services\JournalAffectation;
use App\Support\CreditTache;
use App\Support\JourMaison;
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

        return back()->with('ok', 'C’est noté : vous avez fait la tâche de '.$affectation->couple->nom.'.')
            ->with('ouvrir_tache', $affectation->id);
    }

    public function volerLot(Request $request, JournalAffectation $journal): RedirectResponse
    {
        $user = $request->user();

        if ($user->couple_id === null) {
            return back()->with('erreur', 'Aucun foyer associé à ce compte.');
        }

        $autres = Affectation::query()
            ->with(['couple', 'tache'])
            ->visiblesPour(JourMaison::actuel())
            ->where('couple_id', '!=', $user->couple_id)
            ->whereDoesntHave('completion')
            ->get();

        if ($autres->isEmpty()) {
            return back()->with('erreur', 'Leur lot est déjà coché.');
        }

        foreach ($autres as $affectation) {
            $journal->appliquer($affectation, $user, true, null, now());
        }

        $nom = $autres->first()->couple?->nom
            ?? Couple::query()->whereKeyNot($user->couple_id)->value('nom')
            ?? 'l’autre équipe';
        $n = $autres->count();

        return back()->with('ok', 'C’est noté : vous avez fait '.$n.' tâche'.($n > 1 ? 's' : '').' de '.$nom.'.');
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
