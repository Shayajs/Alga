<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affectation_evenements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affectation_id')->constrained('affectations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 32);
            $table->string('resume');
            $table->json('avant')->nullable();
            $table->json('apres')->nullable();
            $table->string('commentaire')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['affectation_id', 'created_at']);
        });

        $lignes = DB::table('completions')
            ->join('affectations', 'affectations.id', '=', 'completions.affectation_id')
            ->leftJoin('users as auteurs', 'auteurs.id', '=', 'completions.auteur_id')
            ->leftJoin('couples', 'couples.id', '=', 'affectations.couple_id')
            ->leftJoin('users as saisis', 'saisis.id', '=', 'completions.user_id')
            ->select([
                'completions.affectation_id',
                'completions.user_id',
                'completions.auteur_id',
                'completions.fait_a',
                'auteurs.name as auteur_nom',
                'couples.nom as couple_nom',
                'saisis.name as saisi_nom',
            ])
            ->get();

        foreach ($lignes as $ligne) {
            $credit = $ligne->auteur_nom ?: $ligne->couple_nom ?: '—';
            $quand = Carbon::parse($ligne->fait_a)->timezone(config('app.timezone'));

            DB::table('affectation_evenements')->insert([
                'affectation_id' => $ligne->affectation_id,
                'user_id' => $ligne->user_id,
                'action' => 'coche',
                'resume' => ($ligne->saisi_nom ?: 'Quelqu’un').' a coché · '.$credit.' à '.$quand->format('H:i'),
                'avant' => json_encode(['fait' => false, 'auteur_id' => null, 'credit' => null, 'fait_a' => null], JSON_UNESCAPED_UNICODE),
                'apres' => json_encode([
                    'fait' => true,
                    'auteur_id' => $ligne->auteur_id,
                    'credit' => $credit,
                    'fait_a' => $quand->format('Y-m-d H:i'),
                ], JSON_UNESCAPED_UNICODE),
                'commentaire' => null,
                'created_at' => $ligne->fait_a,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('affectation_evenements');
    }
};
