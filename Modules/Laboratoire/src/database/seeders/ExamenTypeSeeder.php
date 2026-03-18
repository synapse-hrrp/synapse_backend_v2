<?php

namespace Modules\Laboratoire\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Laboratoire\App\Models\ExamenType;

class ExamenTypeSeeder extends Seeder
{
    public function run(): void
    {
        $examens = [
            // ── Hématologie ───────────────────────────────────────
            [
                'nom'          => 'Numération Formule Sanguine',
                'code'         => 'NFS',
                'categorie'    => 'hematologie',
                'delai_heures' => 4,
                'instructions' => null,
            ],
            [
                'nom'          => 'Groupe sanguin + Rhésus',
                'code'         => 'GS-RH',
                'categorie'    => 'hematologie',
                'delai_heures' => 2,
                'instructions' => null,
            ],
            [
                'nom'          => 'Taux de prothrombine (TP)',
                'code'         => 'TP',
                'categorie'    => 'hematologie',
                'delai_heures' => 4,
                'instructions' => null,
            ],

            // ── Biochimie ─────────────────────────────────────────
            [
                'nom'          => 'Glycémie à jeun',
                'code'         => 'GLY',
                'categorie'    => 'biochimie',
                'delai_heures' => 2,
                'instructions' => 'À jeun depuis 8 heures minimum',
            ],
            [
                'nom'          => 'Créatininémie',
                'code'         => 'CREAT',
                'categorie'    => 'biochimie',
                'delai_heures' => 2,
                'instructions' => null,
            ],
            [
                'nom'          => 'Transaminases ASAT/ALAT',
                'code'         => 'TRANS',
                'categorie'    => 'biochimie',
                'delai_heures' => 4,
                'instructions' => 'À jeun depuis 4 heures minimum',
            ],
            [
                'nom'          => 'Bilan lipidique',
                'code'         => 'LIPID',
                'categorie'    => 'biochimie',
                'delai_heures' => 4,
                'instructions' => 'À jeun depuis 12 heures minimum',
            ],
            [
                'nom'          => 'Urée sanguine',
                'code'         => 'UREE',
                'categorie'    => 'biochimie',
                'delai_heures' => 2,
                'instructions' => null,
            ],

            // ── Parasitologie ─────────────────────────────────────
            [
                'nom'          => 'Goutte épaisse / TDR paludisme',
                'code'         => 'GE-TDR',
                'categorie'    => 'parasitologie',
                'delai_heures' => 1,
                'instructions' => null,
            ],
            [
                'nom'          => 'Examen parasitologique des selles',
                'code'         => 'EPS',
                'categorie'    => 'parasitologie',
                'delai_heures' => 24,
                'instructions' => 'Recueil des selles le matin',
            ],

            // ── Microbiologie ─────────────────────────────────────
            [
                'nom'          => 'ECBU — Examen cytobactériologique des urines',
                'code'         => 'ECBU',
                'categorie'    => 'microbiologie',
                'delai_heures' => 48,
                'instructions' => 'Prélèvement mi-jet du matin, avant tout traitement antibiotique',
            ],
            [
                'nom'          => 'Hémoculture',
                'code'         => 'HEMOC',
                'categorie'    => 'microbiologie',
                'delai_heures' => 72,
                'instructions' => 'Prélèvement avant antibiothérapie, en période de frissons/fièvre',
            ],

            // ── Immunologie / Sérologie ───────────────────────────
            [
                'nom'          => 'Sérologie HIV (test rapide)',
                'code'         => 'HIV',
                'categorie'    => 'immunologie',
                'delai_heures' => 1,
                'instructions' => null,
            ],
            [
                'nom'          => 'Sérologie Hépatite B (AgHBs)',
                'code'         => 'HBS',
                'categorie'    => 'immunologie',
                'delai_heures' => 2,
                'instructions' => null,
            ],
            [
                'nom'          => 'Sérologie Hépatite C',
                'code'         => 'HCV',
                'categorie'    => 'immunologie',
                'delai_heures' => 2,
                'instructions' => null,
            ],
            [
                'nom'          => 'Test de grossesse (β-HCG)',
                'code'         => 'HCG',
                'categorie'    => 'immunologie',
                'delai_heures' => 1,
                'instructions' => 'Prélèvement urinaire du matin de préférence',
            ],
            [
                'nom'          => 'TPHA / VDRL (Syphilis)',
                'code'         => 'SYPHILIS',
                'categorie'    => 'immunologie',
                'delai_heures' => 4,
                'instructions' => null,
            ],
        ];

        foreach ($examens as $examen) {
            ExamenType::firstOrCreate(
                ['code' => $examen['code']],
                array_merge($examen, ['active' => true])
            );
        }
    }
}