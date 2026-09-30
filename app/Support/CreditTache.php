<?php

namespace App\Support;

use App\Models\Completion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CreditTache
{
    /**
     * @param  list<int>  $couplesAutorises
     * @return array{auteur_id: ?int, credit_externe: ?string}
     */
    public static function depuis(Request $request, array $couplesAutorises): array
    {
        $qui = $request->input('qui');
        $auteurId = null;
        $externe = null;

        if (is_string($qui) && $qui !== '') {
            if (str_starts_with($qui, 'u-')) {
                $auteurId = (int) substr($qui, 2);
            } elseif (str_starts_with($qui, 'x-')) {
                $externe = substr($qui, 2);
            } else {
                throw ValidationException::withMessages(['qui' => 'Choix invalide.']);
            }
        } elseif ($request->filled('auteur_id')) {
            $auteurId = (int) $request->input('auteur_id');
        }

        if ($externe !== null && ! in_array($externe, Completion::CREDITS_EXTERNES, true)) {
            throw ValidationException::withMessages(['qui' => 'Personne inconnue.']);
        }

        if ($auteurId !== null) {
            $ok = User::query()
                ->whereKey($auteurId)
                ->whereIn('couple_id', $couplesAutorises)
                ->exists();

            if (! $ok) {
                throw ValidationException::withMessages([
                    'qui' => 'Cette personne n’est pas dans ce foyer.',
                    'auteur_id' => 'Cette personne n’est pas dans ce foyer.',
                ]);
            }
        }

        if ($externe !== null) {
            $auteurId = null;
        }

        return [
            'auteur_id' => $auteurId,
            'credit_externe' => $externe,
        ];
    }
}
