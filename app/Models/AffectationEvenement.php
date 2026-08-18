<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['affectation_id', 'user_id', 'action', 'resume', 'avant', 'apres', 'commentaire', 'created_at'])]
class AffectationEvenement extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'avant' => 'array',
            'apres' => 'array',
            'created_at' => 'datetime',
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

    public function horaire(): string
    {
        return $this->created_at
            ->timezone(config('app.timezone'))
            ->translatedFormat('D j M · H:i');
    }
}
