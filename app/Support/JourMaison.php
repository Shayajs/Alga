<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class JourMaison
{
    public const HEURE_CHANGEMENT = 3;

    public static function maintenant(): Carbon
    {
        return now()->timezone(config('app.timezone'));
    }

    /**
     * Jour opérationnel : avant 03:00 on est encore sur la veille.
     */
    public static function actuel(?Carbon $instant = null): Carbon
    {
        $instant = ($instant ?? static::maintenant())->copy()->timezone(config('app.timezone'));
        $jour = $instant->copy()->startOfDay();

        if ($instant->hour < self::HEURE_CHANGEMENT) {
            $jour->subDay();
        }

        return $jour;
    }

    public static function debut(?Carbon $jourOperationnel = null): Carbon
    {
        return ($jourOperationnel ?? static::actuel())->copy()->startOfDay()->setTime(self::HEURE_CHANGEMENT, 0, 0);
    }

    public static function fin(?Carbon $jourOperationnel = null): Carbon
    {
        return static::debut($jourOperationnel)->addDay();
    }

    public static function fuseau(): string
    {
        return now()->timezone(config('app.timezone'))->format('P');
    }
}
