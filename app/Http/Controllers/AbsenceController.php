<?php

namespace App\Http\Controllers;

use App\Models\Absence;
use App\Models\Couple;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AbsenceController extends Controller
{
    public function index(): View
    {
        return view('absences.index', [
            'couples' => Couple::query()->orderBy('id')->get(),
            'absences' => Absence::query()
                ->with(['couple', 'declarant'])
                ->orderByDesc('debut_a')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'couple_id' => ['required', 'integer', 'exists:couples,id'],
            'debut_a' => ['required', 'date'],
            'fin_prevue_a' => ['required', 'date', 'after:debut_a'],
        ], [
            'couple_id.required' => 'Choisis le couple qui part.',
            'fin_prevue_a.after' => 'Le retour doit être après le départ.',
        ]);

        $debut = Carbon::parse($data['debut_a']);
        $fin = Carbon::parse($data['fin_prevue_a']);

        if ($debut->diffInMinutes($fin) < 24 * 60) {
            return back()
                ->withInput()
                ->withErrors(['fin_prevue_a' => 'On déclare seulement un départ de 24 h ou plus.']);
        }

        Absence::query()->create([
            'couple_id' => $data['couple_id'],
            'debut_a' => $debut,
            'fin_prevue_a' => $fin,
            'declare_par' => $request->user()->id,
            'declare_a' => now(),
        ]);

        return back()->with('ok', 'Absence enregistrée, horodatée à l’instant.');
    }
}
