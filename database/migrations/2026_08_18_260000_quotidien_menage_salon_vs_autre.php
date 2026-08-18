<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $groupeA = [
            'Aspirer le sol',
            'Passer la serpillière',
            'Faire les poussières',
            'Décrocher le linge au matin',
            'Remplir et faire tourner la machine à laver le soir',
        ];
        $groupeB = [
            'Nettoyer le plan de travail',
            'Vider le lave-vaisselle',
            'Vider et faire pendre le linge',
        ];

        DB::table('taches')->where('frequence', 'quotidien')->whereIn('titre', $groupeA)->update(['groupe' => 'A']);
        DB::table('taches')->where('frequence', 'quotidien')->whereIn('titre', $groupeB)->update(['groupe' => 'B']);
    }

    public function down(): void
    {
        DB::table('taches')->where('frequence', 'quotidien')->where('titre', 'Faire les poussières')->update(['groupe' => 'B']);
    }
};
