<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['affectation_id', 'user_id', 'auteur_id', 'fait_a'])]
class Completion extends Model
{
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
        if ($this->auteur) {
            return $this->auteur->name;
        }

        return $this->affectation?->couple?->nom
            ?? $this->user?->couple?->nom
            ?? $this->user?->name
            ?? '—';
    }
}
