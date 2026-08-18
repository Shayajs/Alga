<?php

namespace App\Services;

use App\Models\Affectation;
use App\Models\Couple;
use App\Models\Tache;
use App\Support\JourMaison;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Repartiteur
{
    public function generer(?Carbon $debut = null, ?Carbon $fin = null): void
    {
        $debut = ($debut ?? JourMaison::actuel())->copy()->startOfDay();
        $fin = ($fin ?? JourMaison::actuel()->addWeeks(8))->copy()->startOfDay();
        $couples = Couple::query()->orderBy('id')->get();

        if ($couples->isEmpty()) {
            return;
        }

        $this->quotidiens($debut, $fin, $couples);
        $this->hebdos($debut, $fin, $couples);
        $this->mensuels($debut, $fin, $couples);
    }

    private function quotidiens(Carbon $debut, Carbon $fin, Collection $couples): void
    {
        $taches = Tache::query()->where('frequence', 'quotidien')->orderBy('ordre')->get();

        if ($taches->isEmpty() || $couples->isEmpty()) {
            return;
        }

        $parGroupe = $taches->groupBy(fn (Tache $tache) => $tache->groupe ?: 'A');

        for ($jour = $debut->copy(); $jour->lte($fin); $jour->addDay()) {
            $this->affecterGroupes($parGroupe, $this->couplesDuTour($couples, $this->indexJour($jour)), $jour);
        }
    }

    private function hebdos(Carbon $debut, Carbon $fin, Collection $couples): void
    {
        $taches = Tache::query()->where('frequence', 'hebdo')->orderBy('ordre')->get();

        if ($taches->isEmpty()) {
            return;
        }

        $parGroupe = $taches->groupBy(fn (Tache $tache) => $tache->groupe ?: 'A');
        $lundi = $debut->copy()->startOfWeek(Carbon::MONDAY);
        $finSemaine = $fin->copy()->startOfWeek(Carbon::MONDAY);

        for ($semaine = $lundi->copy(); $semaine->lte($finSemaine); $semaine->addWeek()) {
            $this->affecterGroupes($parGroupe, $this->couplesDuTour($couples, $this->indexSemaine($semaine)), $semaine);
        }
    }

    private function mensuels(Carbon $debut, Carbon $fin, Collection $couples): void
    {
        $parPiece = Tache::query()
            ->where('frequence', 'mensuel')
            ->orderBy('ordre')
            ->get()
            ->groupBy('piece');

        if ($parPiece->isEmpty()) {
            return;
        }

        $premier = $debut->copy()->startOfMonth();
        $dernier = $fin->copy()->startOfMonth();
        $lots = $parPiece->keys()->values();

        for ($mois = $premier->copy(); $mois->lte($dernier); $mois->addMonth()) {
            foreach ($lots as $i => $piece) {
                $couple = $couples[($mois->month + $i) % $couples->count()];

                foreach ($parPiece[$piece] as $tache) {
                    $this->affecter($tache, $couple, $mois);
                }
            }
        }
    }

    /**
     * @return array{A: Couple, B: Couple}
     */
    private function couplesDuTour(Collection $couples, int $index): array
    {
        $n = $couples->count();
        $tour = abs($index);

        return [
            'A' => $couples[$tour % $n],
            'B' => $couples[($tour + 1) % $n],
        ];
    }

    private function indexJour(Carbon $jour): int
    {
        $origine = Carbon::parse('2026-01-01', config('app.timezone'))->startOfDay();

        return (int) $origine->diffInDays($jour->copy()->startOfDay(), false);
    }

    private function indexSemaine(Carbon $lundi): int
    {
        $origine = Carbon::parse('2026-01-05', config('app.timezone'))->startOfDay();

        return (int) $origine->diffInWeeks($lundi->copy()->startOfDay(), false);
    }

    /**
     * @param  array{A: Couple, B: Couple}  $tour
     */
    private function affecterGroupes(Collection $parGroupe, array $tour, Carbon $date): void
    {
        foreach (['A', 'B'] as $groupe) {
            foreach ($parGroupe->get($groupe, collect()) as $tache) {
                $this->affecter($tache, $tour[$groupe], $date);
            }
        }
    }

    private function affecter(Tache $tache, Couple $couple, Carbon $date): void
    {
        $affectation = Affectation::query()->firstOrNew([
            'tache_id' => $tache->id,
            'date' => $date->toDateString(),
        ]);

        $affectation->couple_id = $couple->id;
        $affectation->save();
    }
}
