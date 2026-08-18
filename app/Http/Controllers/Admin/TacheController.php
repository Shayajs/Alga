<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tache;
use App\Services\Repartiteur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TacheController extends Controller
{
    public function index(): View
    {
        $taches = Tache::query()
            ->orderByRaw("CASE frequence WHEN 'quotidien' THEN 1 WHEN 'hebdo' THEN 2 ELSE 3 END")
            ->orderBy('groupe')
            ->orderBy('piece')
            ->orderBy('ordre')
            ->get()
            ->groupBy('frequence');

        return view('admin.taches.index', [
            'parFrequence' => $taches,
            'pieces' => config('maison.pieces'),
            'frequences' => config('maison.frequences'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tache = Tache::query()->create($this->donnees($request));
        app(Repartiteur::class)->generer();

        return back()->with('ok', 'Tâche ajoutée et planning mis à jour : '.$tache->titre);
    }

    public function update(Request $request, Tache $tache): RedirectResponse|JsonResponse
    {
        $tache->update($this->donnees($request));

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => 'Tâche enregistrée.']);
        }

        app(Repartiteur::class)->generer();

        return back()->with('ok', 'Tâche modifiée.');
    }

    public function destroy(Tache $tache): RedirectResponse
    {
        $tache->delete();
        app(Repartiteur::class)->generer();

        return back()->with('ok', 'Tâche retirée.');
    }

    public function generer(): RedirectResponse
    {
        app(Repartiteur::class)->generer();

        return back()->with('ok', 'Planning régénéré (8 semaines, les tâches déjà cochées restent).');
    }

    /**
     * @return array<string, mixed>
     */
    private function donnees(Request $request): array
    {
        if ($request->filled('heure_limite')) {
            $request->merge([
                'heure_limite' => substr(str_replace('.', ':', (string) $request->input('heure_limite')), 0, 5),
            ]);
        }

        $data = $request->validate([
            'titre' => ['required', 'string', 'max:180'],
            'piece' => ['required', 'in:'.implode(',', config('maison.pieces'))],
            'frequence' => ['required', 'in:quotidien,hebdo,mensuel'],
            'groupe' => ['nullable', 'in:A,B'],
            'heure_limite' => ['required', 'date_format:H:i'],
            'penibilite' => ['required', 'integer', 'min:1', 'max:5'],
            'ordre' => ['nullable', 'integer', 'min:0', 'max:99'],
        ]);

        $data['ordre'] = $data['ordre'] ?? 0;
        $data['groupe'] = in_array($data['frequence'], ['quotidien', 'hebdo'], true)
            ? ($data['groupe'] ?: 'A')
            : null;

        return $data;
    }
}
