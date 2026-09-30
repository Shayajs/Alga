<?php

namespace Tests\Feature;

use App\Models\Absence;
use App\Models\Affectation;
use App\Models\AffectationEvenement;
use App\Models\Completion;
use App\Models\Regle;
use App\Models\Tache;
use App\Models\User;
use App\Services\JournalAffectation;
use App\Services\Repartiteur;
use App\Support\JourMaison;
use Database\Seeders\FamilleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AlgaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FamilleSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_pwa_est_branchee(): void
    {
        $this->assertFileExists(public_path('manifest.webmanifest'));
        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('icon-512.png'));
        $this->assertFileExists(public_path('offline.html'));

        $manifest = json_decode((string) file_get_contents(public_path('manifest.webmanifest')), true);
        $this->assertSame('Alga', $manifest['name']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('/', $manifest['start_url']);

        $this->get('/')
            ->assertOk()
            ->assertSee('manifest.webmanifest', false)
            ->assertSee('js/pwa.js', false)
            ->assertSee('apple-mobile-web-app-capable', false);
    }

    public function test_l_accueil_montre_la_journee(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('HOUSE COMMAND CENTER')
            ->assertSee('DONE')
            ->assertSee('À faire')
            ->assertDontSee('THE OTHER CELL')
            ->assertDontSee('modale-autre-cell');
    }

    public function test_l_accueil_connecte_ouvre_l_autre_cellule_en_modale(): void
    {
        app(Repartiteur::class)->generer();
        $lucas = User::query()->where('name', 'Lucas')->firstOrFail();

        $this->actingAs($lucas)
            ->get('/')
            ->assertOk()
            ->assertSee('YOUR CELL')
            ->assertSee('THE OTHER CELL')
            ->assertSee('modale-autre-cell', false)
            ->assertSee('Nathan & Stacy')
            ->assertSee('lecture seule');
    }

    public function test_premiere_connexion_cree_le_mot_de_passe(): void
    {
        $lucas = User::query()->where('name', 'Lucas')->firstOrFail();
        $this->assertFalse($lucas->aUnMotDePasse());

        $this->post('/connexion', [
            'pseudo' => 'lucas',
            'password' => 'maisonalga',
            'password_confirmation' => 'maisonalga',
        ])->assertRedirect(route('accueil'));

        $this->assertAuthenticatedAs($lucas->fresh());
        $this->assertTrue($lucas->fresh()->aUnMotDePasse());
    }

    public function test_connexion_avec_mot_de_passe_existant(): void
    {
        $lucas = User::query()->where('name', 'Lucas')->firstOrFail();
        $lucas->password = 'maisonalga';
        $lucas->save();

        $this->postJson('/connexion/etat', ['pseudo' => 'Lucas'])
            ->assertOk()
            ->assertJson(['existe' => true, 'premier' => false]);

        $this->post('/connexion', [
            'pseudo' => 'lucas',
            'password' => 'maisonalga',
        ])->assertRedirect(route('accueil'));

        $this->assertAuthenticatedAs($lucas);
    }

    public function test_marquer_fait_horodate_et_fige(): void
    {
        $maintenant = now()->setTime(20, 12);
        Carbon::setTestNow($maintenant);

        $lucas = User::query()->where('name', 'Lucas')->firstOrFail();
        $tache = Tache::query()->firstOrFail();
        $affectation = Affectation::query()->create([
            'tache_id' => $tache->id,
            'couple_id' => $lucas->couple_id,
            'date' => $maintenant->toDateString(),
        ]);

        $this->actingAs($lucas)
            ->post(route('completions.store', $affectation))
            ->assertRedirect();

        $completion = Completion::query()->firstOrFail();
        $this->assertTrue($completion->fait_a->equalTo($maintenant));
        $this->assertSame($lucas->id, $completion->user_id);
        $this->assertNull($completion->auteur_id);
        $this->assertSame('Lucas & Cannelle', $completion->fresh()->load(['auteur', 'affectation.couple'])->libelleAffiche());

        $this->actingAs($lucas)
            ->post(route('completions.store', $affectation))
            ->assertSessionHas('erreur');

        $this->assertSame(1, Completion::query()->count());
    }

    public function test_absence_refusee_sous_24_heures(): void
    {
        $lucas = User::query()->where('name', 'Lucas')->firstOrFail();

        $this->actingAs($lucas)
            ->post('/absences', [
                'couple_id' => $lucas->couple_id,
                'debut_a' => '2026-08-18T10:00',
                'fin_prevue_a' => '2026-08-18T22:00',
            ])
            ->assertSessionHasErrors('fin_prevue_a');

        $this->assertSame(0, Absence::query()->count());
    }

    public function test_absence_couple_horodatee(): void
    {
        Carbon::setTestNow('2026-08-18 21:30:00');
        $lucas = User::query()->where('name', 'Lucas')->firstOrFail();

        $this->actingAs($lucas)
            ->post('/absences', [
                'couple_id' => $lucas->couple_id,
                'debut_a' => '2026-08-19T08:00',
                'fin_prevue_a' => '2026-08-21T08:00',
            ])
            ->assertRedirect();

        $absence = Absence::query()->firstOrFail();
        $this->assertTrue($absence->estHorsMaison());
        $this->assertTrue($absence->declare_a->equalTo(Carbon::parse('2026-08-18 21:30:00')));
        $this->assertSame($lucas->id, $absence->declare_par);
    }

    public function test_le_jour_maison_bascule_a_3h(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-19 02:59:00', 'Europe/Paris'));
        $this->assertSame('2026-08-18', JourMaison::actuel()->toDateString());

        Carbon::setTestNow(Carbon::parse('2026-08-19 03:00:00', 'Europe/Paris'));
        $this->assertSame('2026-08-19', JourMaison::actuel()->toDateString());
    }

    public function test_avance_devient_fait_a_3h(): void
    {
        $lucas = User::query()->where('name', 'Lucas')->firstOrFail();
        $tache = Tache::query()->where('frequence', 'quotidien')->firstOrFail();
        $affectation = Affectation::query()->create([
            'tache_id' => $tache->id,
            'couple_id' => $lucas->couple_id,
            'date' => '2026-08-19',
        ]);
        Completion::query()->create([
            'affectation_id' => $affectation->id,
            'user_id' => $lucas->id,
            'fait_a' => Carbon::parse('2026-08-18 22:10:00', 'Europe/Paris'),
        ]);

        $affectation->load(['tache', 'completion']);

        Carbon::setTestNow(Carbon::parse('2026-08-19 02:59:00', 'Europe/Paris'));
        $this->assertSame('avance', $affectation->statut());

        Carbon::setTestNow(Carbon::parse('2026-08-19 03:00:00', 'Europe/Paris'));
        $this->assertSame('fait', $affectation->statut());
    }

    public function test_veille_non_cochee_est_en_retard_apres_3h(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-19 03:00:00', 'Europe/Paris'));
        $lucas = User::query()->where('name', 'Lucas')->firstOrFail();
        $tache = Tache::query()->where('frequence', 'quotidien')->firstOrFail();
        $affectation = Affectation::query()->create([
            'tache_id' => $tache->id,
            'couple_id' => $lucas->couple_id,
            'date' => '2026-08-18',
        ]);
        $affectation->load('tache');

        $this->assertSame('en_retard', $affectation->statut());
    }

    public function test_admin_reserve_a_lucas(): void
    {
        $this->get('/admin')->assertNotFound();

        $cannelle = User::query()->where('name', 'Cannelle')->firstOrFail();
        $this->actingAs($cannelle)->get('/admin')->assertNotFound();
        $this->actingAs($cannelle)->get(route('admin.taches.index'))->assertNotFound();

        $lucas = User::query()->where('name', 'Lucas')->firstOrFail();
        $this->actingAs($lucas)->get('/admin')->assertOk()->assertSee('ALGA SYS');
        $this->actingAs($lucas)->get(route('admin.taches.index'))
            ->assertOk()
            ->assertSee('GROUPE A')
            ->assertSee('GROUPE B')
            ->assertSee('Ménage du salon')
            ->assertSee('Cuisine et linge');
    }

    public function test_une_regle_s_enregistre_en_ajax(): void
    {
        $lucas = User::query()->where('name', 'Lucas')->firstOrFail();
        $regle = Regle::query()->firstOrFail();

        $this->actingAs($lucas)
            ->put(route('admin.regles.update', $regle), [
                'piece' => $regle->piece,
                'contenu' => 'Le plan de travail se quitte propre.',
                'ordre' => $regle->ordre,
            ], [
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSame('Le plan de travail se quitte propre.', $regle->fresh()->contenu);
    }

    public function test_une_tache_s_enregistre_meme_avec_heures_completes(): void
    {
        $lucas = User::query()->where('name', 'Lucas')->firstOrFail();
        $tache = Tache::query()->where('titre', 'Aspirer le sol')->firstOrFail();

        $this->actingAs($lucas)
            ->from(route('admin.taches.index'))
            ->put(route('admin.taches.update', $tache), [
                'titre' => 'Aspirer le sol',
                'piece' => $tache->piece,
                'frequence' => 'quotidien',
                'groupe' => 'A',
                'heure_limite' => '20:30:00',
                'penibilite' => 2,
                'ordre' => $tache->ordre,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame('20:30', substr((string) $tache->fresh()->heure_limite, 0, 5));
    }

    public function test_une_tache_s_enregistre_en_ajax(): void
    {
        $lucas = User::query()->where('name', 'Lucas')->firstOrFail();
        $tache = Tache::query()->where('titre', 'Aspirer le sol')->firstOrFail();

        $this->actingAs($lucas)
            ->put(route('admin.taches.update', $tache), [
                'titre' => 'Aspirer le salon',
                'piece' => $tache->piece,
                'frequence' => 'quotidien',
                'groupe' => 'A',
                'heure_limite' => '21:00:00',
                'penibilite' => 3,
                'ordre' => $tache->ordre,
            ], [
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSame('Aspirer le salon', $tache->fresh()->titre);
        $this->assertSame(3, $tache->fresh()->penibilite);
    }

    public function test_le_credit_par_defaut_est_le_couple(): void
    {
        $lucas = User::query()->where('name', 'Lucas')->firstOrFail();
        $nathan = User::query()->where('name', 'Nathan')->firstOrFail();
        $tache = Tache::query()->firstOrFail();
        $affectation = Affectation::query()->create([
            'tache_id' => $tache->id,
            'couple_id' => $lucas->couple_id,
            'date' => now()->toDateString(),
        ]);

        $this->assertTrue($affectation->peutEtreCocheePar($lucas));
        $this->assertFalse($affectation->peutEtreCocheePar($nathan));

        $auCouple = Completion::query()->create([
            'affectation_id' => $affectation->id,
            'user_id' => $lucas->id,
            'auteur_id' => null,
            'fait_a' => now(),
        ]);
        $this->assertSame('Lucas & Cannelle', $auCouple->load(['auteur', 'affectation.couple'])->libelleAffiche());
    }

    public function test_on_peut_credit_un_membre_du_couple(): void
    {
        $lucas = User::query()->where('name', 'Lucas')->firstOrFail();
        $tache = Tache::query()->firstOrFail();
        $affectation = Affectation::query()->create([
            'tache_id' => $tache->id,
            'couple_id' => $lucas->couple_id,
            'date' => now()->toDateString(),
        ]);

        $completion = Completion::query()->create([
            'affectation_id' => $affectation->id,
            'user_id' => $lucas->id,
            'auteur_id' => $lucas->id,
            'fait_a' => now(),
        ]);

        $this->assertSame('Lucas', $completion->load(['auteur', 'affectation.couple'])->libelleAffiche());
    }

    public function test_groupes_ab_s_echangent_chaque_jour(): void
    {
        $lundi = Carbon::parse('2026-08-17', 'Europe/Paris')->startOfDay();
        app(Repartiteur::class)->generer($lundi, $lundi->copy()->addDay());

        $coupleALundi = Affectation::query()
            ->whereDate('date', $lundi)
            ->whereHas('tache', fn ($q) => $q->where('frequence', 'quotidien')->where('groupe', 'A'))
            ->value('couple_id');
        $coupleBLundi = Affectation::query()
            ->whereDate('date', $lundi)
            ->whereHas('tache', fn ($q) => $q->where('frequence', 'quotidien')->where('groupe', 'B'))
            ->value('couple_id');

        $this->assertNotNull($coupleALundi);
        $this->assertNotSame($coupleALundi, $coupleBLundi);

        $mardi = $lundi->copy()->addDay();
        $coupleAMardi = Affectation::query()
            ->whereDate('date', $mardi)
            ->whereHas('tache', fn ($q) => $q->where('frequence', 'quotidien')->where('groupe', 'A'))
            ->value('couple_id');

        $this->assertSame($coupleBLundi, $coupleAMardi);

        $titresParCoupleLundi = Affectation::query()
            ->whereDate('date', $lundi)
            ->whereHas('tache', fn ($q) => $q->where('frequence', 'quotidien'))
            ->with('tache')
            ->get()
            ->groupBy('couple_id')
            ->map(fn ($lignes) => $lignes->pluck('tache.groupe')->unique()->values());

        $this->assertTrue($titresParCoupleLundi->every(fn ($groupes) => $groupes->count() === 1));

        $titresLundi = Affectation::query()
            ->whereDate('date', $lundi)
            ->where('couple_id', $coupleALundi)
            ->whereHas('tache', fn ($q) => $q->where('frequence', 'quotidien'))
            ->with('tache')
            ->get()
            ->pluck('tache.titre');
        $titresMardi = Affectation::query()
            ->whereDate('date', $mardi)
            ->where('couple_id', $coupleALundi)
            ->whereHas('tache', fn ($q) => $q->where('frequence', 'quotidien'))
            ->with('tache')
            ->get()
            ->pluck('tache.titre');

        $this->assertTrue($titresLundi->contains('Aspirer le sol'));
        $this->assertTrue($titresLundi->contains('Passer la serpillière'));
        $this->assertTrue($titresLundi->contains('Faire les poussières'));
        $this->assertFalse($titresMardi->contains('Aspirer le sol'));
        $this->assertTrue($titresMardi->contains('Nettoyer le plan de travail'));
        $this->assertTrue($titresMardi->contains('Vider le lave-vaisselle'));
        $this->assertTrue($titresMardi->contains('Faire partir le lave-vaisselle le soir'));
    }

    public function test_le_linge_est_dans_la_rotation_quotidienne(): void
    {
        $this->assertSame('A', Tache::query()->where('titre', 'Aspirer le sol')->value('groupe'));
        $this->assertSame('A', Tache::query()->where('titre', 'Passer la serpillière')->value('groupe'));
        $this->assertSame('A', Tache::query()->where('titre', 'Faire les poussières')->value('groupe'));
        $this->assertSame('A', Tache::query()->where('titre', 'Décrocher le linge au matin')->value('groupe'));
        $this->assertSame('A', Tache::query()->where('titre', 'Remplir et faire tourner la machine à laver le soir')->value('groupe'));
        $this->assertSame('B', Tache::query()->where('titre', 'Nettoyer le plan de travail')->value('groupe'));
        $this->assertSame('B', Tache::query()->where('titre', 'Vider le lave-vaisselle')->value('groupe'));
        $this->assertSame('B', Tache::query()->where('titre', 'Faire partir le lave-vaisselle le soir')->value('groupe'));
        $this->assertSame('B', Tache::query()->where('titre', 'Vider et faire pendre le linge')->value('groupe'));
    }

    public function test_la_semaine_est_un_kanban(): void
    {
        $lucas = User::query()->where('name', 'Lucas')->firstOrFail();
        app(Repartiteur::class)->generer();

        $this->actingAs($lucas)
            ->get(route('tableau.historique'))
            ->assertOk()
            ->assertDontSee('Groupe A')
            ->assertDontSee('Groupe B')
            ->assertSee('Tous les jours')
            ->assertSee('Ménage du salon')
            ->assertSee('Cuisine et linge')
            ->assertSee('kanban', false);
    }

    public function test_groupes_ab_hebdo_s_echangent_chaque_semaine(): void
    {
        $lundi = Carbon::parse('2026-08-17', 'Europe/Paris')->startOfDay();
        $lundiSuivant = $lundi->copy()->addWeek();
        app(Repartiteur::class)->generer($lundi, $lundiSuivant);

        $coupleA = Affectation::query()
            ->whereDate('date', $lundi)
            ->whereHas('tache', fn ($q) => $q->where('frequence', 'hebdo')->where('groupe', 'A'))
            ->value('couple_id');
        $coupleB = Affectation::query()
            ->whereDate('date', $lundi)
            ->whereHas('tache', fn ($q) => $q->where('frequence', 'hebdo')->where('groupe', 'B'))
            ->value('couple_id');

        $this->assertNotNull($coupleA);
        $this->assertNotSame($coupleA, $coupleB);

        $this->assertTrue(
            Tache::query()->where('frequence', 'hebdo')->where('piece', 'Salon et Cuisine')->get()->every(fn (Tache $t) => $t->groupe === 'A')
        );
        $this->assertTrue(
            Tache::query()->where('frequence', 'hebdo')->whereIn('piece', ['Salle de bain', 'Toilettes'])->get()->every(fn (Tache $t) => $t->groupe === 'B')
        );

        $coupleASuivant = Affectation::query()
            ->whereDate('date', $lundiSuivant)
            ->whereHas('tache', fn ($q) => $q->where('frequence', 'hebdo')->where('groupe', 'A'))
            ->value('couple_id');

        $this->assertSame($coupleB, $coupleASuivant);
    }

    public function test_chaque_changement_est_horodate(): void
    {
        Carbon::setTestNow('2026-08-18 20:12:00');
        $lucas = User::query()->where('name', 'Lucas')->firstOrFail();
        $stacy = User::query()->where('name', 'Stacy')->firstOrFail();
        $tache = Tache::query()->firstOrFail();
        $affectation = Affectation::query()->create([
            'tache_id' => $tache->id,
            'couple_id' => $lucas->couple_id,
            'date' => now()->toDateString(),
        ]);

        $this->assertFalse($affectation->peutEtreModifieePar($stacy));
        $this->assertTrue($affectation->peutEtreModifieePar($lucas));

        $journal = app(JournalAffectation::class);
        $journal->appliquer($affectation, $lucas, true, $lucas->id, now());

        $affectation->refresh()->load(['completion', 'evenements']);
        $this->assertTrue($affectation->estFaite());
        $this->assertSame($lucas->id, $affectation->completion->auteur_id);
        $this->assertSame('coche', $affectation->evenements->first()->action);
        $this->assertTrue($affectation->evenements->first()->created_at->equalTo(Carbon::parse('2026-08-18 20:12:00')));

        Carbon::setTestNow('2026-08-18 21:03:00');
        $journal->appliquer(
            $affectation->fresh(['completion.auteur', 'couple']),
            $lucas,
            true,
            null,
            now()->setTime(19, 40),
            'mauvaise personne',
        );

        $affectation->refresh()->load(['completion', 'evenements']);
        $this->assertNull($affectation->completion->auteur_id);
        $this->assertSame('19:40', $affectation->completion->fait_a->timezone('Europe/Paris')->format('H:i'));
        $this->assertSame(2, $affectation->evenements->count());
        $this->assertSame('modification', $affectation->evenements->first()->action);
        $this->assertTrue($affectation->evenements->first()->created_at->equalTo(Carbon::parse('2026-08-18 21:03:00')));

        $journal->appliquer($affectation->fresh(['completion.auteur', 'couple']), $lucas, false, null, null, 'erreur de clic');
        $this->assertFalse($affectation->fresh()->estFaite());
        $this->assertSame(3, AffectationEvenement::query()->where('affectation_id', $affectation->id)->count());
        $this->assertSame('decoche', $affectation->evenements()->first()->action);
    }

    public function test_les_taches_du_planning_sont_modifiables(): void
    {
        $lucas = User::query()->where('name', 'Lucas')->firstOrFail();
        app(Repartiteur::class)->generer();

        $this->actingAs($lucas)
            ->get(route('tableau.aujourdhui'))
            ->assertOk()
            ->assertSee('Fait')
            ->assertSee('Modifier')
            ->assertSee('modale', false)
            ->assertSee('Historique')
            ->assertSee('Demain')
            ->assertSee('Maman')
            ->assertSee('Papa')
            ->assertSee('Autre personne')
            ->assertSee('On a fait leur job')
            ->assertSee('modale-lot-autre', false)
            ->assertDontSee('lot-autre/fait', false);
    }

    public function test_la_semaine_montre_le_passe_et_la_suivante(): void
    {
        Carbon::setTestNow('2026-08-19 12:00:00');
        app(Repartiteur::class)->generer();
        $lucas = User::query()->where('name', 'Lucas')->firstOrFail();

        $this->actingAs($lucas)
            ->get(route('tableau.historique', ['semaine' => '2026-08-10']))
            ->assertOk()
            ->assertSee('Semaine passée')
            ->assertSee('Semaine suivante')
            ->assertSee('10/8')
            ->assertSee('Revenir à cette semaine');

        $this->actingAs($lucas)
            ->get(route('tableau.historique', ['semaine' => '2026-08-24']))
            ->assertOk()
            ->assertSee('Semaine prochaine')
            ->assertSee('24/8')
            ->assertDontSee('Semaine suivante');
    }

    public function test_on_peut_faire_le_job_de_l_autre_equipe(): void
    {
        Carbon::setTestNow('2026-08-18 20:00:00');
        app(Repartiteur::class)->generer();
        $lucas = User::query()->where('name', 'Lucas')->firstOrFail();
        $stacy = User::query()->where('name', 'Stacy')->firstOrFail();

        $leur = Affectation::query()
            ->whereDate('date', '2026-08-18')
            ->where('couple_id', $stacy->couple_id)
            ->whereHas('tache', fn ($q) => $q->where('frequence', 'quotidien'))
            ->firstOrFail();

        $this->actingAs($stacy)
            ->post(route('completions.voler', $leur))
            ->assertSessionHas('erreur');

        $this->actingAs($lucas)
            ->post(route('completions.voler', $leur))
            ->assertRedirect()
            ->assertSessionHas('ok');

        $leur->refresh()->load(['completion.user', 'couple', 'tache']);

        $this->actingAs($lucas);
        $this->assertSame('prise', $leur->statut());

        $this->actingAs($stacy);
        $this->assertSame('volee', $leur->statut());

        $this->actingAs($lucas)
            ->get(route('tableau.aujourdhui'))
            ->assertSee('task-prise', false)
            ->assertSee('Leur tâche, par nous');

        $this->actingAs($stacy)
            ->get(route('tableau.aujourdhui'))
            ->assertSee('task-volee', false)
            ->assertSee('Faite par l’autre');
    }

    public function test_maman_papa_et_autre_peuvent_etre_credites(): void
    {
        Carbon::setTestNow('2026-08-18 20:12:00');
        $lucas = User::query()->where('name', 'Lucas')->firstOrFail();
        $tache = Tache::query()->firstOrFail();
        $affectation = Affectation::query()->create([
            'tache_id' => $tache->id,
            'couple_id' => $lucas->couple_id,
            'date' => '2026-08-18',
        ]);

        $this->actingAs($lucas)
            ->post(route('completions.store', $affectation), ['qui' => 'x-Maman'])
            ->assertRedirect();

        $completion = Completion::query()->firstOrFail();
        $this->assertSame('Maman', $completion->credit_externe);
        $this->assertNull($completion->auteur_id);
        $this->assertSame('Maman', $completion->fresh()->load(['auteur', 'affectation.couple', 'user.couple'])->libelleAffiche());

        $this->actingAs($lucas)
            ->put(route('affectations.update', $affectation), [
                'fait' => '1',
                'qui' => 'x-Papa',
                'fait_a' => '2026-08-18T19:40',
            ])
            ->assertRedirect();

        $this->assertSame('Papa', $affectation->fresh()->completion->credit_externe);
    }
}
