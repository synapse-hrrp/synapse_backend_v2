<?php

namespace Modules\Pharmacie\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $depots = [
            [
                'code' => 'HOP',
                'libelle' => 'Stock Hôpital',
                'description' => 'Dépôt principal pour les ventes hospitalières',
                'actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'RF',
                'libelle' => 'Stock RF',
                'description' => 'Dépôt pour le réseau de distribution RF',
                'actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'GRA',
                'libelle' => 'Stock Gratuité',
                'description' => 'Dépôt pour les distributions gratuites',
                'actif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('depots')->insert($depots);
    }
}