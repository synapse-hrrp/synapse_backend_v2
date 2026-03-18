<?php

namespace Modules\Laboratoire\Database\Seeders;

use Illuminate\Database\Seeder;

class LaboratoireDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ExamenTypeSeeder::class,
        ]);
    }
}