<?php

namespace App\Http\Controllers;

use App\Models\Absence;
use App\Models\Affectation;
use App\Models\Couple;
use App\Support\JourMaison;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class TableauController extends Controller
{
    public function aujourdhui(): View
    {
        $jour = JourMaison::actuel();
        $lendemain = $jour->copy()->addDay();
        $monCouple = auth()->user()->couple;
        $affectations = $this->affectationsVisibles($jour);

        $demain = Affectation::query()
            ->with(Affectation::RELATIONS_BOARD)
            ->whereDate('date', $lendemain)
            ->where('couple_id', $monCouple->id)
            ->whereHas('tache', fn ($q) => $q->where('frequence', 'quotidien'))
            ->get()
            ->sortBy(fn (Affectation $a) => sprintf('%02d-%s', $a->tache->ordre, $a->tache->titre))
            ->values();

        $miennes = $affectations->where('couple_id', $monCouple->id)->values();

        return view('tableau.aujourdhui', [
            'jour' => $jour,
            'lendemain' => $lendemain,
            'monCouple' => $monCouple,
            'lots' => $this->grouperLots($miennes),
            'lotsAvance' => $this->grouperLots($demain),
            'lotDemain' => $demain->first()?->tache->libelleLot(),
        ]);
    }

    public function historique(): View
    {
        $aujourdHui = JourMaison::actuel();
        $lundi = $aujourdHui->copy()->startOfWeek(Carbon::MONDAY);
        $relations = Affectation::RELATIONS_BOARD;
        $monCoupleId = auth()->user()->couple_id;

        $colonnes = collect();
        for ($i = 0; $i < 7; $i++) {
            $jour = $lundi->copy()->addDays($i);
            $quotidien = Affectation::query()
                ->with($relations)
                ->whereDate('date', $jour)
                ->where('couple_id', $monCoupleId)
                ->whereHas('tache', fn ($q) => $q->where('frequence', 'quotidien'))
                ->get()
                ->sortBy(fn (Affectation $a) => sprintf('%s-%02d', $a->tache->groupe ?? 'A', $a->tache->ordre))
                ->values();

            $colonnes->push([
                'date' => $jour,
                'estAujourdhui' => $jour->isSameDay($aujourdHui),
                'absents' => $this->couplesAbsents($jour),
                'lot' => $this->resumeLot($quotidien),
            ]);
        }

        $hebdoLignes = Affectation::query()
            ->with($relations)
            ->whereDate('date', $lundi)
            ->where('couple_id', $monCoupleId)
            ->whereHas('tache', fn ($q) => $q->where('frequence', 'hebdo'))
            ->get()
            ->sortBy(fn (Affectation $a) => sprintf('%s-%02d', $a->tache->piece, $a->tache->ordre))
            ->values();

        $hebdo = $this->resumeLot($hebdoLignes);

        return view('tableau.historique', [
            'aujourdHui' => $aujourdHui,
            'lundi' => $lundi,
            'dimanche' => $lundi->copy()->addDays(6),
            'colonnes' => $colonnes,
            'hebdo' => $hebdo,
        ]);
    }

    private function affectationsVisibles(Carbon $jour): Collection
    {
        return Affectation::query()
            ->with(Affectation::RELATIONS_BOARD)
            ->visiblesPour($jour)
            ->get()
            ->sortBy(fn (Affectation $a) => sprintf('%s-%02d-%s', $a->tache->frequence, $a->tache->ordre, $a->tache->titre))
            ->values();
    }

    private function grouperLots(Collection $affectations): Collection
    {
        return $affectations
            ->groupBy(function (Affectation $a) {
                if ($a->tache->aUnGroupe()) {
                    return $a->tache->frequence.'|'.$a->couple_id.'|'.($a->tache->groupe ?: 'A');
                }

                return $a->tache->frequence.'|'.$a->couple_id.'|'.$a->tache->piece;
            })
            ->map(function (Collection $lignes) {
                $premiere = $lignes->first();
                $groupe = $premiere->tache->aUnGroupe();

                return [
                    'frequence' => $premiere->tache->frequence,
                    'libelle' => $groupe
                        ? ($premiere->tache->frequence === 'hebdo' ? 'Cette semaine · ≈ 15 min' : 'Tous les jours · ≈ 15 min')
                        : $premiere->tache->libelleFrequence(),
                    'piece' => $premiere->tache->libelleLot(),
                    'groupe' => $premiere->tache->groupe,
                    'couple' => $premiere->couple,
                    'penibilite' => $lignes->sum(fn (Affectation $a) => $a->tache->penibilite),
                    'affectations' => $lignes->values(),
                ];
            })
            ->sortBy(fn (array $lot) => ['quotidien' => 0, 'hebdo' => 1, 'mensuel' => 2][$lot['frequence']] ?? 9)
            ->values();
    }

    /**
     * @return array{couple: ?Couple, faits: int, total: int, tone: string, lignes: Collection, titre: ?string}
     */
    private function resumeLot(Collection $lignes): array
    {
        $statuts = $lignes->map->statut();
        $tone = 'vide';

        if ($statuts->contains('en_retard')) {
            $tone = 'en_retard';
        } elseif ($statuts->contains('a_faire')) {
            $tone = 'a_faire';
        } elseif ($statuts->contains('avance')) {
            $tone = 'avance';
        } elseif ($statuts->contains('fait')) {
            $tone = 'fait';
        }

        return [
            'couple' => $lignes->first()?->couple,
            'faits' => $lignes->filter->estFaite()->count(),
            'total' => $lignes->count(),
            'tone' => $tone,
            'lignes' => $lignes,
            'titre' => $lignes->first()?->tache->libelleLot(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function couplesAbsents(Carbon $jour): array
    {
        return Absence::query()
            ->horsMaison()
            ->with('couple')
            ->where('debut_a', '<=', $jour->copy()->endOfDay())
            ->where('fin_prevue_a', '>=', $jour->copy()->startOfDay())
            ->get()
            ->pluck('couple.nom', 'couple_id')
            ->all();
    }
}
