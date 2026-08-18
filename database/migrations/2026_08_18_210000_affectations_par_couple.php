<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affectations', function (Blueprint $table) {
            $table->foreignId('couple_id')->nullable()->after('tache_id')->constrained('couples')->cascadeOnDelete();
        });

        foreach (DB::table('affectations')->get() as $row) {
            $coupleId = DB::table('users')->where('id', $row->user_id)->value('couple_id');

            if ($coupleId) {
                DB::table('affectations')->where('id', $row->id)->update(['couple_id' => $coupleId]);
            }
        }

        DB::table('affectations')->whereNull('couple_id')->delete();

        Schema::table('affectations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('completions', function (Blueprint $table) {
            $table->foreignId('auteur_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('completions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('auteur_id');
        });

        Schema::table('affectations', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('tache_id')->constrained('users')->cascadeOnDelete();
        });

        Schema::table('affectations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('couple_id');
        });
    }
};
