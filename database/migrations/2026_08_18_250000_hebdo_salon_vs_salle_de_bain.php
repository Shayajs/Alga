<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('taches')->where('piece', 'Douche')->update(['piece' => 'Salle de bain']);
        DB::table('regles')->where('piece', 'Douche')->update(['piece' => 'Salle de bain']);

        DB::table('taches')->where('frequence', 'hebdo')->where('piece', 'Salon et Cuisine')->update(['groupe' => 'A']);
        DB::table('taches')->where('frequence', 'hebdo')->whereIn('piece', ['Salle de bain', 'Toilettes'])->update(['groupe' => 'B']);
    }

    public function down(): void
    {
        DB::table('taches')->where('piece', 'Salle de bain')->update(['piece' => 'Douche']);
        DB::table('regles')->where('piece', 'Salle de bain')->update(['piece' => 'Douche']);

        DB::table('taches')->where('frequence', 'hebdo')->where('piece', 'Salle de bain')->update(['groupe' => 'A']);
        DB::table('taches')->where('frequence', 'hebdo')->whereIn('piece', ['Salon et Cuisine', 'Toilettes'])->update(['groupe' => 'B']);
    }
};
