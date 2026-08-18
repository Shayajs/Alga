<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Regle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegleController extends Controller
{
    public function index(): View
    {
        $regles = Regle::query()
            ->orderBy('piece')
            ->orderBy('ordre')
            ->orderBy('id')
            ->get()
            ->groupBy('piece');

        $parPiece = collect(config('maison.pieces'))
            ->mapWithKeys(fn (string $piece) => [$piece => $regles->get($piece, collect())]);

        return view('admin.regles.index', [
            'parPiece' => $parPiece,
            'pieces' => config('maison.pieces'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->donnees($request);
        $data['ordre'] = $data['ordre'] ?? ((int) Regle::query()->where('piece', $data['piece'])->max('ordre') + 1);
        $data['titre'] = null;

        Regle::query()->create($data);

        return back()->with('ok', 'Règle ajoutée.');
    }

    public function update(Request $request, Regle $regle): RedirectResponse|JsonResponse
    {
        $regle->update($this->donnees($request) + ['titre' => null]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => 'Règle enregistrée.']);
        }

        return back()->with('ok', 'Règle modifiée.');
    }

    public function destroy(Regle $regle): RedirectResponse
    {
        $regle->delete();

        return back()->with('ok', 'Règle retirée.');
    }

    /**
     * @return array<string, mixed>
     */
    private function donnees(Request $request): array
    {
        return $request->validate([
            'piece' => ['required', 'in:'.implode(',', config('maison.pieces'))],
            'contenu' => ['required', 'string', 'max:500'],
            'ordre' => ['nullable', 'integer', 'min:0', 'max:99'],
        ]);
    }
}
