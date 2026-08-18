<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Absence;
use App\Models\Affectation;
use App\Models\Completion;
use App\Models\Couple;
use App\Models\Regle;
use App\Models\Tache;
use App\Models\User;
use App\Support\JourMaison;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $jour = JourMaison::actuel();

        $duJour = Affectation::query()
            ->with(['tache', 'completion'])
            ->visiblesPour($jour)
            ->get();

        $retardsVeille = Affectation::query()
            ->whereDate('date', $jour->copy()->subDay())
            ->whereHas('tache', fn ($q) => $q->where('frequence', 'quotidien'))
            ->whereDoesntHave('completion')
            ->count();

        return view('admin.dashboard', [
            'jour' => $jour,
            'horloge' => JourMaison::maintenant(),
            'fuseau' => JourMaison::fuseau(),
            'membres' => User::query()->with('couple')->orderBy('id')->get(),
            'couples' => Couple::query()->orderBy('id')->get(),
            'compteurs' => [
                'taches' => Tache::query()->count(),
                'regles' => Regle::query()->count(),
                'affectations' => Affectation::query()->count(),
                'fait' => $duJour->filter(fn (Affectation $a) => $a->statut() === 'fait')->count(),
                'a_faire' => $duJour->filter(fn (Affectation $a) => $a->statut() === 'a_faire')->count(),
                'en_retard' => $duJour->filter(fn (Affectation $a) => $a->statut() === 'en_retard')->count() + $retardsVeille,
                'absences' => Absence::query()
                    ->horsMaison()
                    ->where('debut_a', '<=', now())
                    ->where('fin_prevue_a', '>=', now())
                    ->count(),
            ],
            'horizon' => [
                'debut' => Affectation::query()->min('date'),
                'fin' => Affectation::query()->max('date'),
            ],
            'journal' => Completion::query()
                ->with(['user', 'auteur', 'affectation.couple', 'affectation.tache'])
                ->latest('fait_a')
                ->limit(8)
                ->get(),
        ]);
    }
}
