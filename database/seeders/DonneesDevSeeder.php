<?php

namespace Database\Seeders;

use App\Models\Affectation;
use App\Models\User;
use App\Services\JournalAffectation;
use App\Services\Repartiteur;
use App\Support\JourMaison;
use Illuminate\Database\Seeder;

class DonneesDevSeeder extends Seeder
{
    public function run(): void
    {
        $jour = JourMaison::actuel();
        app(Repartiteur::class)->generer($jour->copy()->subDay(), $jour->copy()->addDays(13));

        $lucas = User::query()->where('name', 'Lucas')->first();
        if ($lucas === null) {
            return;
        }

        $this->cocher(
            Affectation::query()
                ->with('couple.membres')
                ->whereDate('date', $jour)
                ->whereHas('tache', fn ($q) => $q->where('frequence', 'quotidien'))
                ->orderBy('id')
                ->take(2)
                ->get(),
            $jour->copy()->setTime(8, 12)
        );

        $this->cocher(
            Affectation::query()
                ->with('couple.membres')
                ->whereDate('date', $jour->copy()->subDay())
                ->whereHas('tache', fn ($q) => $q->where('frequence', 'quotidien'))
                ->orderBy('id')
                ->take(3)
                ->get(),
            $jour->copy()->subDay()->setTime(19, 40)
        );

        $this->cocher(
            Affectation::query()
                ->with('couple.membres')
                ->whereDate('date', $jour->copy()->addDay())
                ->whereHas('tache', fn ($q) => $q->where('frequence', 'quotidien'))
                ->orderBy('id')
                ->take(1)
                ->get(),
            JourMaison::maintenant()->copy()->subHour()
        );
    }

    private function cocher($affectations, $faitA): void
    {
        $journal = app(JournalAffectation::class);

        foreach ($affectations as $i => $affectation) {
            $membre = $affectation->couple?->membres->first();
            if ($membre === null || $affectation->completion()->exists()) {
                continue;
            }

            $journal->appliquer(
                $affectation,
                $membre,
                true,
                $i === 0 ? $membre->id : null,
                $faitA->copy()->addMinutes($i * 11),
            );
        }
    }
}
