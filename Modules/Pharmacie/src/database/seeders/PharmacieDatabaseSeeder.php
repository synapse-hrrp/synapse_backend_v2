<?php

namespace Modules\Pharmacie\Database\Seeders;

use Illuminate\Database\Seeder;

class PharmacieDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $this->call([

            DepotSeeder::class,

         ]);
    }
}
