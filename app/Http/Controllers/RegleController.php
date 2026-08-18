<?php

namespace App\Http\Controllers;

use App\Models\Regle;
use Illuminate\View\View;

class RegleController extends Controller
{
    public function index(): View
    {
        $groupes = Regle::query()
            ->orderBy('ordre')
            ->orderBy('id')
            ->get()
            ->groupBy('piece');

        $parPiece = collect(config('maison.pieces'))
            ->mapWithKeys(fn (string $piece) => [$piece => $groupes->get($piece, collect())]);

        return view('regles.index', ['parPiece' => $parPiece]);
    }
}
