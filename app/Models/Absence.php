<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['couple_id', 'debut_a', 'fin_prevue_a', 'declare_par', 'declare_a'])]
class Absence extends Model
{
    protected function casts(): array
    {
        return [
            'debut_a' => 'datetime',
            'fin_prevue_a' => 'datetime',
            'declare_a' => 'datetime',
        ];
    }

    public function couple(): BelongsTo
    {
        return $this->belongsTo(Couple::class);
    }

    public function declarant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'declare_par');
    }

    public function dureeHeures(): float
    {
        return $this->debut_a->diffInMinutes($this->fin_prevue_a) / 60;
    }

    public function estHorsMaison(): bool
    {
        return $this->dureeHeures() >= 24;
    }

    #[Scope]
    protected function horsMaison(Builder $query): void
    {
        $driver = $query->getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $query->whereRaw('(julianday(fin_prevue_a) - julianday(debut_a)) >= 1');

            return;
        }

        $query->whereRaw('TIMESTAMPDIFF(HOUR, debut_a, fin_prevue_a) >= 24');
    }
}
