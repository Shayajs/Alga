<?php

namespace App\Http\Controllers;

use App\Models\Absence;
use App\Models\Affectation;
use App\Models\Couple;
use App\Support\JourMaison;
use Illuminate\Http\Request;
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
        $autres = $affectations->where('couple_id', '!=', $monCouple->id)->values();

        return view('tableau.aujourdhui', [
            'jour' => $jour,
            'lendemain' => $lendemain,
            'monCouple' => $monCouple,
            'autreCouple' => Couple::query()->whereKeyNot($monCouple->id)->orderBy('id')->first(),
            'lots' => $this->grouperLots($miennes),
            'lotsAutre' => $this->grouperLots($autres),
            'resteAutre' => $autres->reject->estFaite()->count(),
            'lotsAvance' => $this->grouperLots($demain),
            'lotDemain' => $demain->first()?->tache->libelleLot(),
        ]);
    }

    public function historique(Request $request): View
    {
        $aujourdHui = JourMaison::actuel();
        $lundiActuel = $aujourdHui->copy()->startOfWeek(Carbon::MONDAY);
        $monCoupleId = auth()->user()->couple_id;
        [$lundiMin, $lundiMax] = $this->bornesSemaines($lundiActuel);
        $lundi = $this->lundiDemande($request->query('semaine'), $lundiActuel, $lundiMin, $lundiMax);

        $colonnes = collect();
        for ($i = 0; $i < 7; $i++) {
            $jour = $lundi->copy()->addDays($i);
            $quotidien = $this->lignesDuCouple($jour, 'quotidien', $monCoupleId);
            $prises = $this->lignesPrises($jour, 'quotidien', $monCoupleId);

            $colonnes->push([
                'date' => $jour,
                'estAujourdhui' => $jour->isSameDay($aujourdHui),
                'absents' => $this->couplesAbsents($jour),
                'lot' => $this->resumeLot($quotidien),
                'lignes' => $quotidien->concat($prises)->values(),
            ]);
        }

        $hebdoLignes = $this->lignesDuCouple($lundi, 'hebdo', $monCoupleId);
        $hebdo = $this->resumeLot($hebdoLignes);
        $hebdo['lignes'] = $hebdoLignes
            ->concat($this->lignesPrises($lundi, 'hebdo', $monCoupleId))
            ->values();

        return view('tableau.historique', [
            'aujourdHui' => $aujourdHui,
            'lundi' => $lundi,
            'dimanche' => $lundi->copy()->addDays(6),
            'lundiActuel' => $lundiActuel,
            'lundiPrecedent' => $lundi->gt($lundiMin) ? $lundi->copy()->subWeek() : null,
            'lundiSuivant' => $lundi->lt($lundiMax) ? $lundi->copy()->addWeek() : null,
            'estSemaineCourante' => $lundi->isSameDay($lundiActuel),
            'estSemaineProchaine' => $lundi->gt($lundiActuel),
            'colonnes' => $colonnes,
            'hebdo' => $hebdo,
        ]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function bornesSemaines(Carbon $lundiActuel): array
    {
        $lundiMin = $lundiActuel->copy()->subWeeks(16);
        $premiere = Affectation::query()->min('date');

        if ($premiere) {
            $lundiDonnee = Carbon::parse($premiere, config('app.timezone'))->startOfWeek(Carbon::MONDAY);
            if ($lundiDonnee->lt($lundiMin)) {
                $lundiMin = $lundiDonnee;
            }
        }

        return [$lundiMin, $lundiActuel->copy()->addWeek()];
    }

    private function lundiDemande(mixed $semaine, Carbon $lundiActuel, Carbon $lundiMin, Carbon $lundiMax): Carbon
    {
        $lundi = $lundiActuel->copy();

        if (is_string($semaine) && $semaine !== '') {
            try {
                $lundi = Carbon::parse($semaine, config('app.timezone'))->startOfWeek(Carbon::MONDAY);
            } catch (\Throwable) {
                $lundi = $lundiActuel->copy();
            }
        }

        if ($lundi->lt($lundiMin)) {
            return $lundiMin->copy();
        }

        if ($lundi->gt($lundiMax)) {
            return $lundiMax->copy();
        }

        return $lundi;
    }

    private function lignesDuCouple(Carbon $jour, string $frequence, int $monCoupleId): Collection
    {
        return $this->lignesFiltrees($jour, $frequence, function ($query) use ($monCoupleId): void {
            $query->where('couple_id', $monCoupleId);
        });
    }

    private function lignesPrises(Carbon $jour, string $frequence, int $monCoupleId): Collection
    {
        return $this->lignesFiltrees($jour, $frequence, function ($query) use ($monCoupleId): void {
            $query->where('couple_id', '!=', $monCoupleId)
                ->whereHas('completion', function ($completion) use ($monCoupleId): void {
                    $completion->whereHas('user', fn ($user) => $user->where('couple_id', $monCoupleId));
                });
        });
    }

    private function lignesFiltrees(Carbon $jour, string $frequence, callable $filtre): Collection
    {
        return Affectation::query()
            ->with(Affectation::RELATIONS_BOARD)
            ->whereDate('date', $jour)
            ->where($filtre)
            ->whereHas('tache', fn ($q) => $q->where('frequence', $frequence))
            ->get()
            ->sortBy(fn (Affectation $a) => $frequence === 'hebdo'
                ? sprintf('%s-%02d', $a->tache->piece, $a->tache->ordre)
                : sprintf('%s-%02d', $a->tache->groupe ?? 'A', $a->tache->ordre))
            ->values();
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
        $tone = Affectation::toneParmi($lignes->map->statut());

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
