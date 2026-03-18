<?php

namespace Modules\Imagerie\Database\Seeders;

use Illuminate\Database\Seeder;

class ImagerieDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ImagerieTypeSeeder::class,
        ]);
    }
}