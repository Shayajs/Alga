<?php

namespace App\Http\Controllers;

use App\Models\Affectation;
use App\Models\Couple;
use App\Support\JourMaison;
use Illuminate\View\View;

class AccueilController extends Controller
{
    public function __invoke(): View
    {
        $jour = JourMaison::actuel();
        $veille = $jour->copy()->subDay();
        $lendemain = $jour->copy()->addDay();
        $relations = Affectation::RELATIONS_BOARD;

        $duJour = Affectation::query()
            ->with($relations)
            ->visiblesPour($jour)
            ->get();

        $retardsVeille = Affectation::query()
            ->with($relations)
            ->whereDate('date', $veille)
            ->whereHas('tache', fn ($q) => $q->where('frequence', 'quotidien'))
            ->whereDoesntHave('completion')
            ->get();

        $avances = Affectation::query()
            ->with($relations)
            ->whereDate('date', $lendemain)
            ->whereHas('tache', fn ($q) => $q->where('frequence', 'quotidien'))
            ->whereHas('completion')
            ->get();

        $lignes = $duJour->concat($retardsVeille)->concat($avances)->unique('id')->values();
        $monCoupleId = auth()->user()?->couple_id;

        $parCouple = Couple::query()
            ->with('membres')
            ->orderBy('id')
            ->get()
            ->map(function (Couple $couple) use ($lignes, $monCoupleId): array {
                $items = $lignes
                    ->filter(fn (Affectation $a) => $a->couple_id === $couple->id)
                    ->sortBy(fn (Affectation $a) => match ($a->statut()) {
                        'en_retard' => 0,
                        'a_faire' => 1,
                        'avance' => 2,
                        default => 3,
                    })
                    ->values();

                return [
                    'couple' => $couple,
                    'estLeMien' => $monCoupleId !== null && $couple->id === $monCoupleId,
                    'items' => $items,
                    'faits' => $items->filter(fn (Affectation $a) => $a->statut() === 'fait')->count(),
                    'total' => $items->count(),
                ];
            })
            ->sortBy(fn (array $bloc) => $bloc['estLeMien'] ? 0 : 1)
            ->values();

        $autreBloc = null;
        if ($monCoupleId) {
            $autreBloc = $parCouple->first(fn (array $bloc) => ! $bloc['estLeMien']);
            $parCouple = $parCouple->filter(fn (array $bloc) => $bloc['estLeMien'])->values();
            $lignes = $parCouple->first()['items'] ?? collect();
        }

        $compteurs = [
            'fait' => $lignes->filter(fn (Affectation $a) => $a->statut() === 'fait')->count(),
            'a_faire' => $lignes->filter(fn (Affectation $a) => $a->statut() === 'a_faire')->count(),
            'en_retard' => $lignes->filter(fn (Affectation $a) => $a->statut() === 'en_retard')->count(),
            'avance' => $lignes->filter(fn (Affectation $a) => $a->statut() === 'avance')->count(),
        ];

        return view('accueil.index', [
            'jour' => $jour,
            'horloge' => JourMaison::maintenant(),
            'fuseau' => JourMaison::fuseau(),
            'parCouple' => $parCouple,
            'autreBloc' => $autreBloc,
            'compteurs' => $compteurs,
            'total' => array_sum($compteurs),
        ]);
    }
}
