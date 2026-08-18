<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('taches')->where('frequence', 'hebdo')->where('piece', 'Douche')->update(['groupe' => 'A']);
        DB::table('taches')->where('frequence', 'hebdo')->whereIn('piece', ['Salon et Cuisine', 'Toilettes'])->update(['groupe' => 'B']);
        DB::table('taches')->where('frequence', 'hebdo')->whereNull('groupe')->update(['groupe' => 'A']);
    }

    public function down(): void
    {
        DB::table('taches')->where('frequence', 'hebdo')->update(['groupe' => null]);
    }
};
