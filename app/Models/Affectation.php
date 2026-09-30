<?php

namespace App\Models;

use App\Support\JourMaison;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

#[Fillable(['tache_id', 'couple_id', 'date'])]
class Affectation extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public const RELATIONS_BOARD = [
        'tache',
        'couple.membres',
        'completion.auteur',
        'completion.user.couple',
        'completion.affectation.couple',
        'evenements.user',
    ];

    public function tache(): BelongsTo
    {
        return $this->belongsTo(Tache::class);
    }

    public function couple(): BelongsTo
    {
        return $this->belongsTo(Couple::class);
    }

    public function completion(): HasOne
    {
        return $this->hasOne(Completion::class);
    }

    public function evenements(): HasMany
    {
        return $this->hasMany(AffectationEvenement::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    public function estFaite(): bool
    {
        return $this->completion !== null;
    }

    public function libelleFait(): string
    {
        if ($this->completion === null) {
            return '';
        }

        $this->completion->setRelation('affectation', $this);

        return $this->completion->libelleAffiche();
    }

    public function estVolee(): bool
    {
        $voleur = $this->completion?->user?->couple_id;

        return $this->completion !== null
            && $voleur !== null
            && $voleur !== $this->couple_id;
    }

    public function voleeParCouple(?int $coupleId): bool
    {
        if ($coupleId === null || ! $this->estVolee()) {
            return false;
        }

        return $this->completion?->user?->couple_id === $coupleId;
    }

    public function peutEtreCocheePar(?User $user): bool
    {
        return $this->peutEtreModifieePar($user);
    }

    public function peutEtreModifieePar(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $user->est_admin
            || $user->couple_id === $this->couple_id
            || $this->voleeParCouple($user->couple_id);
    }

    public function cleOuverture(): string
    {
        $this->loadMissing('tache');

        if ($this->tache->aUnGroupe()) {
            return $this->tache->frequence.'-'.($this->tache->groupe ?: 'A');
        }

        return Str::slug($this->tache->piece);
    }

    public function limiteAt(): Carbon
    {
        $jour = $this->date->copy()->startOfDay();

        if ($this->tache->frequence === 'hebdo') {
            $jour = $this->date->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay();
        }

        if ($this->tache->frequence === 'mensuel') {
            $jour = $this->date->copy()->endOfMonth()->startOfDay();
        }

        return JourMaison::fin($jour);
    }

    public function estEnRetard(): bool
    {
        if ($this->estFaite()) {
            return false;
        }

        return JourMaison::maintenant()->gte($this->limiteAt());
    }

    public function libelleStatut(): string
    {
        return match ($this->statut()) {
            'fait' => 'Fait',
            'a_faire' => 'À faire',
            'en_retard' => 'Pas coché',
            'avance' => 'Avance',
            'volee' => 'Faite par l’autre',
            'prise' => 'Leur tâche, par nous',
            default => $this->statut(),
        };
    }

    public function codeStatut(): string
    {
        return match ($this->statut()) {
            'fait' => 'DONE',
            'a_faire' => 'TODO',
            'en_retard' => 'LATE',
            'avance' => 'AHEAD',
            'volee' => 'TAKEN',
            'prise' => 'CLAIM',
            default => strtoupper($this->statut()),
        };
    }

    public function estEnAvance(): bool
    {
        if (! $this->estFaite()) {
            return false;
        }

        return JourMaison::maintenant()->lt(JourMaison::debut($this->date->copy()->startOfDay()));
    }

    public function statut(): string
    {
        if ($this->estVolee()) {
            $viewer = auth()->user()?->couple_id;
            $voleur = $this->completion?->user?->couple_id;

            if ($viewer !== null && $voleur === $viewer && $viewer !== $this->couple_id) {
                return 'prise';
            }

            return 'volee';
        }

        if ($this->estEnAvance()) {
            return 'avance';
        }

        if ($this->estFaite()) {
            return 'fait';
        }

        if ($this->estEnRetard()) {
            return 'en_retard';
        }

        return 'a_faire';
    }

    /**
     * @param  iterable<string>  $statuts
     */
    public static function toneParmi(iterable $statuts): string
    {
        $liste = collect($statuts);

        foreach (['en_retard', 'a_faire', 'volee', 'prise', 'avance', 'fait'] as $statut) {
            if ($liste->contains($statut)) {
                return $statut;
            }
        }

        return 'vide';
    }

    #[Scope]
    protected function visiblesPour(Builder $query, Carbon $jour): void
    {
        $lundi = $jour->copy()->startOfWeek(Carbon::MONDAY);
        $mois = $jour->copy()->startOfMonth();

        $query->where(function (Builder $outer) use ($jour, $lundi, $mois): void {
            $outer->where(function (Builder $q) use ($jour): void {
                $q->whereDate('date', $jour)
                    ->whereHas('tache', fn (Builder $t) => $t->where('frequence', 'quotidien'));
            })->orWhere(function (Builder $q) use ($lundi): void {
                $q->whereDate('date', $lundi)
                    ->whereHas('tache', fn (Builder $t) => $t->where('frequence', 'hebdo'));
            })->orWhere(function (Builder $q) use ($mois): void {
                $q->whereDate('date', $mois)
                    ->whereHas('tache', fn (Builder $t) => $t->where('frequence', 'mensuel'));
            });
        });
    }
}
