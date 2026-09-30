<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['affectation_id', 'user_id', 'auteur_id', 'credit_externe', 'fait_a'])]
class Completion extends Model
{
    public const CREDITS_EXTERNES = ['Maman', 'Papa', 'Autre personne'];

    protected function casts(): array
    {
        return [
            'fait_a' => 'datetime',
        ];
    }

    public function affectation(): BelongsTo
    {
        return $this->belongsTo(Affectation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }

    public function libelleAffiche(): string
    {
        if (filled($this->credit_externe)) {
            return $this->credit_externe;
        }

        if ($this->auteur) {
            return $this->auteur->name;
        }

        if ($this->user && $this->affectation && $this->user->couple_id !== $this->affectation->couple_id) {
            return $this->user->couple?->nom ?? $this->user->name;
        }

        return $this->affectation?->couple?->nom
            ?? $this->user?->couple?->nom
            ?? $this->user?->name
            ?? '—';
    }
}
