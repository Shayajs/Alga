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

        return $this->completion->auteur?->name ?? $this->couple?->nom ?? '—';
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

        return $user->est_admin || $user->couple_id === $this->couple_id;
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
