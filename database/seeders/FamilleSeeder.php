<?php

namespace Database\Seeders;

use App\Models\Couple;
use App\Models\Regle;
use App\Models\Tache;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FamilleSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->whereIn('name', ['Papa', 'Maman', 'Shaya'])->delete();
        Couple::query()->whereIn('nom', ['Parents', 'Shaya & Cannelle'])->delete();

        $couples = [
            'Lucas & Cannelle' => ['Lucas', 'Cannelle'],
            'Nathan & Stacy' => ['Nathan', 'Stacy'],
        ];

        foreach ($couples as $nomCouple => $noms) {
            $couple = Couple::query()->firstOrCreate(['nom' => $nomCouple]);

            foreach ($noms as $nom) {
                $user = User::query()->firstOrCreate(
                    ['email' => Str::lower($nom).'@alga.local'],
                    [
                        'couple_id' => $couple->id,
                        'name' => $nom,
                        'pseudo' => Str::lower($nom),
                        'password' => null,
                        'est_admin' => $nom === 'Lucas',
                    ]
                );

                $user->forceFill([
                    'couple_id' => $couple->id,
                    'name' => $nom,
                    'pseudo' => Str::lower($nom),
                    'est_admin' => $nom === 'Lucas',
                ])->save();
            }
        }

        $cles = [];
        foreach ($this->catalogueTaches() as $index => $data) {
            $cles[] = $data['piece'].'|'.$data['titre'];
            Tache::query()->updateOrCreate(
                ['titre' => $data['titre'], 'piece' => $data['piece']],
                $data + ['ordre' => $index + 1]
            );
        }

        Tache::query()->get()->each(function (Tache $tache) use ($cles): void {
            if (! in_array($tache->piece.'|'.$tache->titre, $cles, true)) {
                $tache->delete();
            }
        });

        Regle::query()->delete();
        foreach ($this->catalogueRegles() as $index => $data) {
            Regle::query()->create($data + ['ordre' => $index + 1, 'titre' => null]);
        }
    }

    /**
     * @return list<array{titre: string, piece: string, frequence: string, groupe: ?string, jour_semaine: ?int, heure_limite: string, penibilite: int}>
     */
    private function catalogueTaches(): array
    {
        return [
            ['titre' => 'Aspirer le sol', 'piece' => 'Salon et Cuisine', 'frequence' => 'quotidien', 'groupe' => 'A', 'jour_semaine' => null, 'heure_limite' => '21:00', 'penibilite' => 2],
            ['titre' => 'Passer la serpillière', 'piece' => 'Salon et Cuisine', 'frequence' => 'quotidien', 'groupe' => 'A', 'jour_semaine' => null, 'heure_limite' => '21:00', 'penibilite' => 2],
            ['titre' => 'Faire les poussières', 'piece' => 'Salon et Cuisine', 'frequence' => 'quotidien', 'groupe' => 'A', 'jour_semaine' => null, 'heure_limite' => '21:00', 'penibilite' => 2],
            ['titre' => 'Décrocher le linge au matin', 'piece' => 'Linge', 'frequence' => 'quotidien', 'groupe' => 'A', 'jour_semaine' => null, 'heure_limite' => '11:00', 'penibilite' => 1],
            ['titre' => 'Remplir et faire tourner la machine à laver le soir', 'piece' => 'Linge', 'frequence' => 'quotidien', 'groupe' => 'A', 'jour_semaine' => null, 'heure_limite' => '21:00', 'penibilite' => 2],

            ['titre' => 'Nettoyer le plan de travail', 'piece' => 'Salon et Cuisine', 'frequence' => 'quotidien', 'groupe' => 'B', 'jour_semaine' => null, 'heure_limite' => '21:00', 'penibilite' => 1],
            ['titre' => 'Vider le lave-vaisselle', 'piece' => 'Salon et Cuisine', 'frequence' => 'quotidien', 'groupe' => 'B', 'jour_semaine' => null, 'heure_limite' => '21:00', 'penibilite' => 1],
            ['titre' => 'Faire partir le lave-vaisselle le soir', 'piece' => 'Salon et Cuisine', 'frequence' => 'quotidien', 'groupe' => 'B', 'jour_semaine' => null, 'heure_limite' => '21:00', 'penibilite' => 1],
            ['titre' => 'Vider et faire pendre le linge', 'piece' => 'Linge', 'frequence' => 'quotidien', 'groupe' => 'B', 'jour_semaine' => null, 'heure_limite' => '12:00', 'penibilite' => 2],

            ['titre' => 'Nettoyer les vitres', 'piece' => 'Salon et Cuisine', 'frequence' => 'hebdo', 'groupe' => 'A', 'jour_semaine' => null, 'heure_limite' => '21:00', 'penibilite' => 3],
            ['titre' => 'Passer les grilles de la hotte dans le lave-vaisselle', 'piece' => 'Salon et Cuisine', 'frequence' => 'hebdo', 'groupe' => 'A', 'jour_semaine' => null, 'heure_limite' => '21:00', 'penibilite' => 2],

            ['titre' => 'Nettoyer la vitre et les miroirs', 'piece' => 'Salle de bain', 'frequence' => 'hebdo', 'groupe' => 'B', 'jour_semaine' => null, 'heure_limite' => '21:00', 'penibilite' => 3],
            ['titre' => 'Nettoyer les lavabos', 'piece' => 'Salle de bain', 'frequence' => 'hebdo', 'groupe' => 'B', 'jour_semaine' => null, 'heure_limite' => '21:00', 'penibilite' => 2],
            ['titre' => 'Nettoyer le sol', 'piece' => 'Salle de bain', 'frequence' => 'hebdo', 'groupe' => 'B', 'jour_semaine' => null, 'heure_limite' => '21:00', 'penibilite' => 3],

            ['titre' => 'Mettre de la javel', 'piece' => 'Toilettes', 'frequence' => 'hebdo', 'groupe' => 'B', 'jour_semaine' => null, 'heure_limite' => '21:00', 'penibilite' => 2],
            ['titre' => 'Nettoyer le sol', 'piece' => 'Toilettes', 'frequence' => 'hebdo', 'groupe' => 'B', 'jour_semaine' => null, 'heure_limite' => '21:00', 'penibilite' => 2],
        ];
    }

    /**
     * @return list<array{piece: string, contenu: string}>
     */
    private function catalogueRegles(): array
    {
        return [
            ['piece' => 'Salon et Cuisine', 'contenu' => 'Avant de quitter la cuisine, assiettes, verres et couverts vont dans le lave-vaisselle. Machine pleine : on la lance. Machine finie : on la vide.'],
            ['piece' => 'Salon et Cuisine', 'contenu' => 'Le plan de travail se quitte propre et dégagé.'],
            ['piece' => 'Salon et Cuisine', 'contenu' => 'Ce qui a servi dans le salon ne passe pas la nuit au milieu de la pièce.'],
            ['piece' => 'Salle de bain', 'contenu' => 'Après la douche, on relève le tapis pour qu’il sèche.'],
            ['piece' => 'Salle de bain', 'contenu' => 'On ouvre la fenêtre. Pas de tapis collé au sol mouillé.'],
            ['piece' => 'Toilettes', 'contenu' => 'S’il reste moins d’un quart de rouleau, on met le suivant tout de suite. On ne laisse jamais le support vide.'],
            ['piece' => 'Salon et Cuisine', 'contenu' => 'Quotidien : un couple fait le ménage du salon (aspirer, serpillière, poussières, linge matin et soir). L’autre fait cuisine et linge à pendre. Le lendemain, on inverse.'],
            ['piece' => 'Linge', 'contenu' => 'Un couple décroche le matin et lance la machine le soir. L’autre vide et pend. Ça tourne chaque jour.'],
        ];
    }
}
