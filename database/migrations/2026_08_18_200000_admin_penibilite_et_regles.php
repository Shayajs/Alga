<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('est_admin')->default(false)->after('couple_id');
        });

        Schema::table('taches', function (Blueprint $table) {
            $table->unsignedTinyInteger('penibilite')->default(1)->after('heure_limite');
        });

        Schema::table('regles', function (Blueprint $table) {
            $table->string('titre')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('est_admin');
        });

        Schema::table('taches', function (Blueprint $table) {
            $table->dropColumn('penibilite');
        });

        Schema::table('regles', function (Blueprint $table) {
            $table->string('titre')->nullable(false)->change();
        });
    }
};
