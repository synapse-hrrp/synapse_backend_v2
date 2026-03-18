<?php

namespace Modules\Reception\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Reception\App\Models\TariffPlan;

class TariffPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'nom'         => 'Standard',
                'type'        => 'standard',
                'description' => 'Tarif standard pour patients ordinaires',
                'active'      => true,
            ],
            [
                'nom'         => 'Assuré',
                'type'        => 'assure',
                'description' => 'Tarif pour patients couverts par assurance',
                'active'      => true,
            ],
            [
                'nom'         => 'Urgence',
                'type'        => 'urgence',
                'description' => 'Tarif pour prises en charge urgentes',
                'active'      => true,
            ],
            [
                'nom'         => 'Conventionné',
                'type'        => 'conventionne',
                'description' => 'Tarif pour accords institutionnels',
                'active'      => true,
            ],
            [
                'nom'         => 'Gratuit',
                'type'        => 'gratuit',
                'description' => 'Prise en charge gratuite',
                'active'      => true,
            ],
        ];

        foreach ($plans as $plan) {
            TariffPlan::firstOrCreate(
                ['nom' => $plan['nom']],
                $plan
            );
        }
    }
}