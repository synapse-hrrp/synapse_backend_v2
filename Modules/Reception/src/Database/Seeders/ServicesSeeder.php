<?php

namespace Modules\Reception\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Reception\App\Models\Service;

class ServicesSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'code' => 'CONS_GEN',
                'name' => 'Consultation générale',
                'category' => 'consultation',
                'default_price' => 5000,
                'requires_doctor' => true,
            ],
            [
                'code' => 'CONS_GYNE',
                'name' => 'Consultation gynécologique',
                'category' => 'consultation',
                'default_price' => 7000,
                'requires_doctor' => true,
            ],
            [
                'code' => 'LAB_NFS',
                'name' => 'NFS',
                'category' => 'laboratoire',
                'default_price' => 3000,
            ],
            [
                'code' => 'IMG_ECHO',
                'name' => 'Échographie',
                'category' => 'imagerie',
                'default_price' => 8000,
                'requires_doctor' => true,
            ],
            [
                'code' => 'MAT_ACCOU',
                'name' => 'Accouchement',
                'category' => 'maternite',
                'default_price' => 50000,
                'requires_doctor' => true,
            ],
        ];

        foreach ($services as $service) {
            Service::firstOrCreate(
                ['code' => $service['code']],
                array_merge([
                    'payment_required_before_service' => true,
                    'requires_appointment' => false,
                    'is_active' => true,
                ], $service)
            );
        }
    }
}
