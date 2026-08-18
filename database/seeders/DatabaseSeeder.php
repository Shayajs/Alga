<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(FamilleSeeder::class);

        if (config('famille.seed_fictif')) {
            $this->call(DonneesDevSeeder::class);
        }
    }
}
