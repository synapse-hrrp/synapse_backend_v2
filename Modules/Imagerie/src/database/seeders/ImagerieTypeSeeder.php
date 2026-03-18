<?php

namespace Modules\Imagerie\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Imagerie\App\Models\ImagerieType;

class ImagerieTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            // ── Radiographie ──────────────────────────────────────
            [
                'nom'                => 'Radiographie thorax',
                'code'               => 'RX-THORAX',
                'categorie'          => 'radiographie',
                'delai_heures'       => 1,
                'preparation'        => null,
                'contre_indications' => 'Grossesse (1er trimestre)',
            ],
            [
                'nom'                => 'Radiographie abdomen sans préparation',
                'code'               => 'RX-ASP',
                'categorie'          => 'radiographie',
                'delai_heures'       => 1,
                'preparation'        => null,
                'contre_indications' => 'Grossesse (1er trimestre)',
            ],
            [
                'nom'                => 'Radiographie des membres',
                'code'               => 'RX-MEMBRE',
                'categorie'          => 'radiographie',
                'delai_heures'       => 1,
                'preparation'        => null,
                'contre_indications' => 'Grossesse (1er trimestre)',
            ],
            [
                'nom'                => 'Radiographie du bassin',
                'code'               => 'RX-BASSIN',
                'categorie'          => 'radiographie',
                'delai_heures'       => 1,
                'preparation'        => null,
                'contre_indications' => 'Grossesse',
            ],

            // ── Échographie ───────────────────────────────────────
            [
                'nom'                => 'Échographie abdominale',
                'code'               => 'ECHO-ABD',
                'categorie'          => 'echographie',
                'delai_heures'       => 2,
                'preparation'        => 'À jeun depuis 4 heures. Vessie pleine.',
                'contre_indications' => null,
            ],
            [
                'nom'                => 'Échographie obstétricale',
                'code'               => 'ECHO-OBS',
                'categorie'          => 'echographie',
                'delai_heures'       => 2,
                'preparation'        => 'Vessie pleine pour le 1er trimestre',
                'contre_indications' => null,
            ],
            [
                'nom'                => 'Échographie pelvienne',
                'code'               => 'ECHO-PELV',
                'categorie'          => 'echographie',
                'delai_heures'       => 2,
                'preparation'        => 'Vessie pleine',
                'contre_indications' => null,
            ],
            [
                'nom'                => 'Échographie cardiaque (Échocardiographie)',
                'code'               => 'ECHO-CARD',
                'categorie'          => 'echographie',
                'delai_heures'       => 2,
                'preparation'        => null,
                'contre_indications' => null,
            ],
            [
                'nom'                => 'Échographie des parties molles',
                'code'               => 'ECHO-PM',
                'categorie'          => 'echographie',
                'delai_heures'       => 2,
                'preparation'        => null,
                'contre_indications' => null,
            ],

            // ── Scanner ───────────────────────────────────────────
            [
                'nom'                => 'Scanner cérébral',
                'code'               => 'TDM-CRANE',
                'categorie'          => 'scanner',
                'delai_heures'       => 4,
                'preparation'        => 'Retirer bijoux et objets métalliques',
                'contre_indications' => 'Grossesse, allergie produit de contraste',
            ],
            [
                'nom'                => 'Scanner thoracique',
                'code'               => 'TDM-THORAX',
                'categorie'          => 'scanner',
                'delai_heures'       => 4,
                'preparation'        => 'À jeun depuis 4 heures si injection de contraste',
                'contre_indications' => 'Grossesse, insuffisance rénale sévère',
            ],
            [
                'nom'                => 'Scanner abdomino-pelvien',
                'code'               => 'TDM-ABD',
                'categorie'          => 'scanner',
                'delai_heures'       => 4,
                'preparation'        => 'À jeun depuis 4 heures',
                'contre_indications' => 'Grossesse, allergie produit de contraste',
            ],
        ];

        foreach ($types as $type) {
            ImagerieType::firstOrCreate(
                ['code' => $type['code']],
                array_merge($type, ['active' => true])
            );
        }
    }
}