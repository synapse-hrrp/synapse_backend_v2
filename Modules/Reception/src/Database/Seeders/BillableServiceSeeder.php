<?php

namespace Modules\Reception\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Reception\App\Models\BillableService;

class BillableServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            // ── Consultations ─────────────────────────────────────
            ['code' => 'CONS001', 'libelle' => 'Consultation générale',          'categorie' => 'consultation'],
            ['code' => 'CONS002', 'libelle' => 'Consultation spécialisée',        'categorie' => 'consultation'],
            ['code' => 'CONS003', 'libelle' => 'Consultation pédiatrique',        'categorie' => 'consultation'],
            ['code' => 'CONS004', 'libelle' => 'Consultation gynécologique',      'categorie' => 'consultation'],

            // ── Accouchements ──────────────────────────────────────
            ['code' => 'ACCOU001', 'libelle' => 'Accouchement voie basse',        'categorie' => 'accouchement'],
            ['code' => 'ACCOU002', 'libelle' => 'Césarienne',                     'categorie' => 'accouchement'],
            ['code' => 'ACCOU003', 'libelle' => 'Accouchement instrumental',      'categorie' => 'accouchement'],

            // ── Actes opératoires ──────────────────────────────────
            ['code' => 'OPER001', 'libelle' => 'Appendicectomie',                 'categorie' => 'acte_operatoire'],
            ['code' => 'OPER002', 'libelle' => 'Herniorrhaphie',                  'categorie' => 'acte_operatoire'],
            ['code' => 'OPER003', 'libelle' => 'Cholécystectomie',                'categorie' => 'acte_operatoire'],

            // ── Hospitalisations ───────────────────────────────────
            ['code' => 'HOSP001', 'libelle' => 'Hospitalisation médecine interne','categorie' => 'hospitalisation'],
            ['code' => 'HOSP002', 'libelle' => 'Hospitalisation chirurgie',       'categorie' => 'hospitalisation'],
            ['code' => 'HOSP003', 'libelle' => 'Hospitalisation pédiatrie',       'categorie' => 'hospitalisation'],
            ['code' => 'HOSP004', 'libelle' => 'Hospitalisation maternité',       'categorie' => 'hospitalisation'],

            // ── Laboratoire ───────────────────────────────────────
            ['code' => 'LAB001', 'libelle' => 'Numération Formule Sanguine',      'categorie' => 'laboratory'],
            ['code' => 'LAB002', 'libelle' => 'Glycémie à jeun',                  'categorie' => 'laboratory'],
            ['code' => 'LAB003', 'libelle' => 'Créatininémie',                    'categorie' => 'laboratory'],
            ['code' => 'LAB004', 'libelle' => 'Transaminases ASAT/ALAT',          'categorie' => 'laboratory'],
            ['code' => 'LAB005', 'libelle' => 'Bilan lipidique',                  'categorie' => 'laboratory'],
            ['code' => 'LAB006', 'libelle' => 'Groupe sanguin + Rhésus',          'categorie' => 'laboratory'],
            ['code' => 'LAB007', 'libelle' => 'Test de paludisme (GE/TDR)',        'categorie' => 'laboratory'],
            ['code' => 'LAB008', 'libelle' => 'ECBU (Examen cytobactériologique)','categorie' => 'laboratory'],
            ['code' => 'LAB009', 'libelle' => 'Sérologie HIV',                    'categorie' => 'laboratory'],
            ['code' => 'LAB010', 'libelle' => 'Sérologie Hépatite B',             'categorie' => 'laboratory'],

            // ── Imagerie ─────────────────────────────────────────
            ['code' => 'IMG001', 'libelle' => 'Radiographie thorax',              'categorie' => 'imagerie'],
            ['code' => 'IMG002', 'libelle' => 'Radiographie abdomen sans prépa',  'categorie' => 'imagerie'],
            ['code' => 'IMG003', 'libelle' => 'Échographie abdominale',           'categorie' => 'imagerie'],
            ['code' => 'IMG004', 'libelle' => 'Échographie obstétricale',         'categorie' => 'imagerie'],
            ['code' => 'IMG005', 'libelle' => 'Échographie pelvienne',            'categorie' => 'imagerie'],
            ['code' => 'IMG006', 'libelle' => 'Scanner cérébral',                 'categorie' => 'imagerie'],
            ['code' => 'IMG007', 'libelle' => 'Scanner thoraco-abdominal',        'categorie' => 'imagerie'],
        ];

        foreach ($services as $service) {
            BillableService::firstOrCreate(
                ['code' => $service['code']],
                array_merge($service, ['active' => true])
            );
        }
    }
}