<?php

use App\Services\Repartiteur;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('taches')) {
            return;
        }

        $existe = DB::table('taches')
            ->where('titre', 'Faire partir le lave-vaisselle le soir')
            ->exists();

        if (! $existe) {
            $ordre = (int) DB::table('taches')->where('groupe', 'B')->where('frequence', 'quotidien')->max('ordre');

            DB::table('taches')->insert([
                'titre' => 'Faire partir le lave-vaisselle le soir',
                'piece' => 'Salon et Cuisine',
                'frequence' => 'quotidien',
                'groupe' => 'B',
                'jour_semaine' => null,
                'heure_limite' => '21:00',
                'penibilite' => 1,
                'ordre' => $ordre > 0 ? $ordre + 1 : 9,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        app(Repartiteur::class)->generer();
    }

    public function down(): void
    {
        DB::table('taches')->where('titre', 'Faire partir le lave-vaisselle le soir')->delete();
    }
};
