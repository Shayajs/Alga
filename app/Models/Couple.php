<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Fillable(['nom'])]
class Couple extends Model
{
    public function membres(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function absences(): HasMany
    {
        return $this->hasMany(Absence::class);
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class);
    }

    public function estAbsentLe(Carbon $moment): bool
    {
        return $this->absences()
            ->horsMaison()
            ->where('debut_a', '<=', $moment)
            ->where('fin_prevue_a', '>=', $moment)
            ->exists();
    }
}
