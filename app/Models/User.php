<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'pseudo', 'email', 'password', 'couple_id', 'est_admin', 'pin'])]
#[Hidden(['password', 'pin', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'pin' => 'hashed',
            'est_admin' => 'boolean',
        ];
    }

    public function couple(): BelongsTo
    {
        return $this->belongsTo(Couple::class);
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class, 'couple_id', 'couple_id');
    }

    public function completions(): HasMany
    {
        return $this->hasMany(Completion::class);
    }

    public function aUnMotDePasse(): bool
    {
        return filled($this->getRawOriginal('password'));
    }

    public static function parPseudo(string $pseudo): ?self
    {
        $cle = mb_strtolower(trim($pseudo));

        if ($cle === '') {
            return null;
        }

        return static::query()
            ->where(function (Builder $query) use ($cle): void {
                $query->whereRaw('LOWER(pseudo) = ?', [$cle])
                    ->orWhereRaw('LOWER(name) = ?', [$cle]);
            })
            ->first();
    }

    public function initiales(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];
        $letters = array_map(fn (string $p) => mb_strtoupper(mb_substr($p, 0, 1)), array_slice($parts, 0, 2));

        return implode('', $letters) ?: '?';
    }
}
