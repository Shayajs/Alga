<?php

namespace App\Http\Controllers;

use App\Models\Affectation;
use App\Services\JournalAffectation;
use App\Support\CreditTache;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AffectationController extends Controller
{
    public function update(Request $request, Affectation $affectation, JournalAffectation $journal): RedirectResponse
    {
        $user = $request->user();

        if (! $affectation->peutEtreModifieePar($user)) {
            return back()->with('erreur', 'C’est le lot de l’autre couple.');
        }

        $affectation->loadMissing(['couple', 'tache', 'completion.user']);

        $data = $request->validate([
            'fait' => ['required', 'boolean'],
            'qui' => ['nullable', 'string', 'max:40'],
            'auteur_id' => ['nullable', 'integer'],
            'fait_a' => ['nullable', 'date'],
            'commentaire' => ['nullable', 'string', 'max:280'],
        ]);

        $couples = array_values(array_unique(array_filter([
            $affectation->couple_id,
            $user->couple_id,
            $affectation->completion?->user?->couple_id,
        ])));
        $credit = CreditTache::depuis($request, $couples);

        $fait = $request->boolean('fait');
        $faitA = $fait
            ? Carbon::parse($data['fait_a'] ?? now(), config('app.timezone'))
            : null;
        $commentaire = isset($data['commentaire']) ? trim((string) $data['commentaire']) : null;

        $journal->appliquer(
            $affectation,
            $user,
            $fait,
            $credit['auteur_id'],
            $faitA,
            $commentaire ?: null,
            $credit['credit_externe'],
        );

        $retour = back()->with('ok', 'Modification enregistrée et horodatée.');

        if ($request->boolean('depuis_lot_autre')) {
            return $retour->with('ouvrir_lot_autre', true);
        }

        return $retour
            ->with('ouvrir_piece', $affectation->cleOuverture())
            ->with('ouvrir_tache', $affectation->id);
    }
}
