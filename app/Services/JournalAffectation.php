<?php

namespace App\Services;

use App\Models\Affectation;
use App\Models\AffectationEvenement;
use App\Models\Completion;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class JournalAffectation
{
    public function appliquer(
        Affectation $affectation,
        User $user,
        bool $fait,
        ?int $auteurId,
        ?Carbon $faitA,
        ?string $commentaire = null,
    ): void {
        $affectation->loadMissing(['completion.auteur', 'couple.membres', 'tache']);
        $avant = $this->snapshot($affectation);

        DB::transaction(function () use ($affectation, $user, $fait, $auteurId, $faitA, $commentaire, $avant): void {
            if ($fait) {
                $this->poserCoche($affectation, $user, $auteurId, $faitA ?? now());
            } else {
                $affectation->completion()->delete();
                $affectation->unsetRelation('completion');
            }

            $affectation->load(['completion.auteur', 'couple']);
            $apres = $this->snapshot($affectation);

            if ($avant === $apres && blank($commentaire)) {
                return;
            }

            $action = $this->action($avant, $apres, $commentaire);

            AffectationEvenement::query()->create([
                'affectation_id' => $affectation->id,
                'user_id' => $user->id,
                'action' => $action,
                'resume' => $this->resume($user, $action, $avant, $apres, $commentaire),
                'avant' => $avant,
                'apres' => $apres,
                'commentaire' => $commentaire ?: null,
            ]);
        });
    }

    /**
     * @return array{fait: bool, auteur_id: ?int, credit: ?string, fait_a: ?string}
     */
    public function snapshot(Affectation $affectation): array
    {
        $completion = $affectation->completion;

        return [
            'fait' => $completion !== null,
            'auteur_id' => $completion?->auteur_id,
            'credit' => $completion === null
                ? null
                : ($completion->auteur?->name ?? $affectation->couple?->nom),
            'fait_a' => $completion?->fait_a
                ?->timezone(config('app.timezone'))
                ->format('Y-m-d H:i'),
        ];
    }

    private function poserCoche(Affectation $affectation, User $user, ?int $auteurId, Carbon $faitA): void
    {
        $completion = $affectation->completion ?? new Completion(['affectation_id' => $affectation->id]);
        $completion->user_id = $user->id;
        $completion->auteur_id = $auteurId;
        $completion->fait_a = $faitA->copy()->timezone(config('app.timezone'));
        $completion->save();
        $affectation->setRelation('completion', $completion->load('auteur'));
    }

    /**
     * @param  array{fait: bool, auteur_id: ?int, credit: ?string, fait_a: ?string}  $avant
     * @param  array{fait: bool, auteur_id: ?int, credit: ?string, fait_a: ?string}  $apres
     */
    private function action(array $avant, array $apres, ?string $commentaire): string
    {
        if (! $avant['fait'] && $apres['fait']) {
            return 'coche';
        }

        if ($avant['fait'] && ! $apres['fait']) {
            return 'decoche';
        }

        if ($avant === $apres) {
            return 'note';
        }

        return 'modification';
    }

    /**
     * @param  array{fait: bool, auteur_id: ?int, credit: ?string, fait_a: ?string}  $avant
     * @param  array{fait: bool, auteur_id: ?int, credit: ?string, fait_a: ?string}  $apres
     */
    private function resume(User $user, string $action, array $avant, array $apres, ?string $commentaire): string
    {
        $qui = $user->name;

        $texte = match ($action) {
            'coche' => $qui.' a coché · '.($apres['credit'] ?? '—').' à '.($this->heure($apres['fait_a']) ?: '—'),
            'decoche' => $qui.' a retiré la coche (avant : '.($avant['credit'] ?? '—').' à '.($this->heure($avant['fait_a']) ?: '—').')',
            'note' => $qui.' a laissé une note',
            default => $qui.' a modifié · '.$this->diff($avant, $apres),
        };

        if (filled($commentaire) && $action !== 'note') {
            $texte .= ' · « '.$commentaire.' »';
        }

        return $texte;
    }

    /**
     * @param  array{fait: bool, auteur_id: ?int, credit: ?string, fait_a: ?string}  $avant
     * @param  array{fait: bool, auteur_id: ?int, credit: ?string, fait_a: ?string}  $apres
     */
    private function diff(array $avant, array $apres): string
    {
        $bits = [];

        if ($avant['credit'] !== $apres['credit']) {
            $bits[] = 'crédit '.($avant['credit'] ?? '—').' → '.($apres['credit'] ?? '—');
        }

        if ($avant['fait_a'] !== $apres['fait_a']) {
            $bits[] = 'heure '.($this->heure($avant['fait_a']) ?: '—').' → '.($this->heure($apres['fait_a']) ?: '—');
        }

        return $bits === [] ? 'sans changement visible' : implode(', ', $bits);
    }

    private function heure(?string $faitA): ?string
    {
        if ($faitA === null) {
            return null;
        }

        $quand = Carbon::createFromFormat('Y-m-d H:i', $faitA, config('app.timezone'));

        return $quand ? $quand->format('H:i') : null;
    }
}
