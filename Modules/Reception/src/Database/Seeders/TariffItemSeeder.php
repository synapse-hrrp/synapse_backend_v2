<?php

namespace Modules\Reception\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Reception\App\Models\BillableService;
use Modules\Reception\App\Models\TariffItem;
use Modules\Reception\App\Models\TariffPlan;

class TariffItemSeeder extends Seeder
{
    public function run(): void
    {
        // Récupérer les plans
        $standard    = TariffPlan::where('nom', 'Standard')->first();
        $assure      = TariffPlan::where('nom', 'Assuré')->first();
        $urgence     = TariffPlan::where('nom', 'Urgence')->first();

        if (!$standard || !$assure || !$urgence) {
            $this->command->error('Plans tarifaires manquants — lancez TariffPlanSeeder d\'abord.');
            return;
        }

        // Prix par service — [code => [standard, assure, urgence]]
        $prix = [
            // Consultations
            'CONS001' => [5000,   3000,  8000],
            'CONS002' => [10000,  6000, 15000],
            'CONS003' => [5000,   3000,  8000],
            'CONS004' => [8000,   5000, 12000],

            // Accouchements
            'ACCOU001' => [50000,  35000,  80000],
            'ACCOU002' => [150000, 100000, 200000],
            'ACCOU003' => [80000,  55000, 120000],

            // Actes opératoires
            'OPER001' => [200000, 150000, 300000],
            'OPER002' => [150000, 100000, 250000],
            'OPER003' => [250000, 180000, 350000],

            // Hospitalisations (par jour)
            'HOSP001' => [15000,  10000,  20000],
            'HOSP002' => [20000,  15000,  30000],
            'HOSP003' => [15000,  10000,  20000],
            'HOSP004' => [18000,  12000,  25000],

            // Laboratoire
            'LAB001' => [5000,  3500,  7000],
            'LAB002' => [2000,  1500,  3000],
            'LAB003' => [2500,  2000,  3500],
            'LAB004' => [5000,  3500,  7000],
            'LAB005' => [8000,  6000, 10000],
            'LAB006' => [3000,  2000,  4500],
            'LAB007' => [2000,  1500,  3000],
            'LAB008' => [5000,  3500,  7000],
            'LAB009' => [5000,  3500,  7000],
            'LAB010' => [5000,  3500,  7000],

            // Imagerie
            'IMG001' => [10000,  7000, 15000],
            'IMG002' => [10000,  7000, 15000],
            'IMG003' => [20000, 15000, 30000],
            'IMG004' => [20000, 15000, 30000],
            'IMG005' => [20000, 15000, 30000],
            'IMG006' => [80000, 60000, 100000],
            'IMG007' => [100000, 75000, 130000],
        ];

        foreach ($prix as $code => $tarifs) {
            $service = BillableService::where('code', $code)->first();

            if (!$service) continue;

            // Plan Standard
            TariffItem::firstOrCreate(
                [
                    'tariff_plan_id'      => $standard->id,
                    'billable_service_id' => $service->id,
                ],
                ['prix_unitaire' => $tarifs[0], 'active' => true]
            );

            // Plan Assuré
            TariffItem::firstOrCreate(
                [
                    'tariff_plan_id'      => $assure->id,
                    'billable_service_id' => $service->id,
                ],
                ['prix_unitaire' => $tarifs[1], 'active' => true]
            );

            // Plan Urgence
            TariffItem::firstOrCreate(
                [
                    'tariff_plan_id'      => $urgence->id,
                    'billable_service_id' => $service->id,
                ],
                ['prix_unitaire' => $tarifs[2], 'active' => true]
            );
        }
    }
}