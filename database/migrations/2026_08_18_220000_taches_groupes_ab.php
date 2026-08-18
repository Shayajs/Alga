<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taches', function (Blueprint $table) {
            $table->string('groupe', 1)->nullable()->after('frequence');
        });

        $groupeA = ['Aspirer le sol', 'Passer la serpillière'];
        $groupeB = ['Nettoyer le plan de travail', 'Vider le lave-vaisselle', 'Faire les poussières'];

        DB::table('taches')->where('frequence', 'quotidien')->whereIn('titre', $groupeA)->update(['groupe' => 'A']);
        DB::table('taches')->where('frequence', 'quotidien')->whereIn('titre', $groupeB)->update(['groupe' => 'B']);
        DB::table('taches')->where('frequence', 'quotidien')->whereNull('groupe')->update(['groupe' => 'A']);
    }

    public function down(): void
    {
        Schema::table('taches', function (Blueprint $table) {
            $table->dropColumn('groupe');
        });
    }
};
