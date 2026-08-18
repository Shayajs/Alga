<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['piece', 'titre', 'contenu', 'ordre'])]
class Regle extends Model
{
    protected $table = 'regles';
}
