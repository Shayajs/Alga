<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['titre', 'piece', 'frequence', 'groupe', 'jour_semaine', 'heure_limite', 'penibilite', 'ordre'])]
class Tache extends Model
{
    protected $table = 'taches';

    protected function casts(): array
    {
        return [
            'jour_semaine' => 'integer',
            'penibilite' => 'integer',
        ];
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class);
    }

    public function estHebdomadaire(): bool
    {
        return $this->frequence === 'hebdo';
    }

    public function libelleFrequence(): string
    {
        return config('maison.frequences.'.$this->frequence, $this->frequence);
    }

    public function aUnGroupe(): bool
    {
        return in_array($this->frequence, ['quotidien', 'hebdo'], true);
    }

    public function libelleGroupe(): string
    {
        if ($this->frequence === 'hebdo') {
            return config('maison.lots_hebdo.'.($this->groupe ?: 'A'), $this->piece);
        }

        if ($this->frequence === 'quotidien') {
            return config('maison.lots_quotidien.'.($this->groupe ?: 'A'), 'Aujourd’hui');
        }

        return $this->piece;
    }

    public function libelleLot(): string
    {
        return $this->libelleGroupe();
    }

    public function cleAffichage(): string
    {
        return $this->frequence.'|'.$this->libelleLot();
    }
}
