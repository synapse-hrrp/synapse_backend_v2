<?php

namespace Modules\Reception\Database\Seeders;

use Illuminate\Database\Seeder;

class ReceptionDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TariffPlanSeeder::class,
            BillableServiceSeeder::class,
            TariffItemSeeder::class, // ← après les deux précédents
        ]);
    }
}