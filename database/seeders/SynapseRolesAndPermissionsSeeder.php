<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SynapseRolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Tables nécessaires
        foreach (['roles', 'fonctionnalites', 'roles_fonctionnalites'] as $t) {
            if (!Schema::hasTable($t)) {
                $this->command?->error("Table manquante: {$t}");
                return;
            }
        }

        // -------------------------
        // 1) Permissions (tech_label)
        // -------------------------
        $caisseAbilities = [
            'caisse.access',
            'caisse.session.manage',
            'caisse.session.view',
            'caisse.report.view',
            'caisse.reglement.create',
            'caisse.audit.view',
            'caisse.report.global',
        ];

        $perms = [
            // Alias simples
            'patients.read', 'patients.write',
            'visites.read',  'visites.write',

            // lookups front
            'medecins.read', 'personnels.read', 'services.read', 'tarifs.read',

            // Patients
            'patients.view', 'patients.create', 'patients.update', 'patients.delete', 'patients.orient',

            // ✅ PERSONNES (AJOUT)
            'personnes.view', 'personnes.create', 'personnes.update', 'personnes.delete',

            // Consultations
            'consultations.view', 'consultations.create', 'consultations.update', 'consultations.delete',

            // Examens
            'examen.view', 'examen.request.create', 'examen.result.write', 'examen.create',

            // Examens par service
            'medecine.examen.create','aru.examen.create','gynecologie.examen.create','maternite.examen.create',
            'pediatrie.examen.create','sanitaire.examen.create','consultations.examen.create','smi.examen.create',

            // Tarifs
            'tarif.view', 'tarif.create', 'tarif.update', 'tarif.delete',
            'tarifs.view', 'tarifs.create', 'tarifs.update', 'tarifs.delete',

            // Pharmacie (ancien / compat)
            'pharma.stock.view', 'pharma.sale.create', 'pharma.ordonnance.validate',

            // ✅ Pharmacie (nouveau module / pour middleware pharmacie.permission:*)
            'pharmacie.dashboard.view',

            'pharmacie.stocks.view',
            'pharmacie.stocks.alertes',
            'pharmacie.stocks.seuils',

            'pharmacie.produits.view',
            'pharmacie.produits.create',
            'pharmacie.produits.edit',
            'pharmacie.produits.delete',

            'pharmacie.fournisseurs.view',
            'pharmacie.fournisseurs.create',
            'pharmacie.fournisseurs.edit',
            'pharmacie.fournisseurs.delete',

            'pharmacie.commandes.view',
            'pharmacie.commandes.create',
            'pharmacie.commandes.edit',

            'pharmacie.receptions.view',
            'pharmacie.receptions.create',
            'pharmacie.receptions.annuler',

            'pharmacie.ventes.view',
            'pharmacie.ventes.create',
            'pharmacie.ventes.annuler',

            'pharmacie.rapports.view',
            'pharmacie.rapports.export',

            // ✅ Dépôts (CRUD)
            'pharmacie.depots.view',
            'pharmacie.depots.create',
            'pharmacie.depots.edit',
            'pharmacie.depots.delete',

            'pharmacie.admin.all',

            // Finance / Caisse
            'caisse.facture.view', 'caisse.facture.create',
            'caisse.reglement.view', 'caisse.reglement.create', 'caisse.reglement.validate',

            // Pansements
            'pansement.view', 'pansement.create', 'pansement.update', 'pansement.delete',

            // CRUDs
            'medecins.view', 'medecins.create', 'medecins.update', 'medecins.delete',
            'personnels.view', 'personnels.create', 'personnels.update', 'personnels.delete',
            'services.view', 'services.create', 'services.update', 'services.delete',

            // Autres modules
            'aru.view', 'aru.create', 'aru.update', 'aru.delete',
            'medecine.view', 'medecine.create', 'medecine.update', 'medecine.delete',
            'kinesitherapie.view', 'kinesitherapie.create', 'kinesitherapie.update', 'kinesitherapie.delete',
            'gestion-malade.view', 'gestion-malade.create', 'gestion-malade.update', 'gestion-malade.delete',
            'sanitaire.view', 'sanitaire.create', 'sanitaire.update', 'sanitaire.delete',
            'gynecologie.view', 'gynecologie.create', 'gynecologie.update', 'gynecologie.delete',
            'maternite.view', 'maternite.create', 'maternite.update', 'maternite.delete',
            'pediatrie.view', 'pediatrie.create', 'pediatrie.update', 'pediatrie.delete',
            'smi.view', 'smi.create', 'smi.update', 'smi.delete',
            'bloc-operatoire.view', 'bloc-operatoire.create', 'bloc-operatoire.update', 'bloc-operatoire.delete',
            'logistique.view', 'logistique.create', 'logistique.update', 'logistique.delete',

            // Admin
            'users.view', 'users.create', 'users.update', 'users.delete',
            'roles.view', 'roles.create', 'roles.assign',

            // Stats
            'stats.view',

            // Pourcentage
            'pourcentage.view', 'pourcentage.update',
        ];

        $perms = array_values(array_unique(array_merge($perms, $caisseAbilities)));

        // Insère les fonctionnalités (tech_label)
        foreach ($perms as $p) {
            DB::table('fonctionnalites')->updateOrInsert(
                ['tech_label' => $p],
                [
                    'label'      => $p,
                    'modules_id'  => Schema::hasColumn('fonctionnalites', 'modules_id') ? null : null,
                    'parent'      => Schema::hasColumn('fonctionnalites', 'parent') ? null : null,
                    'updated_at'  => Schema::hasColumn('fonctionnalites', 'updated_at') ? now() : null,
                    'created_at'  => Schema::hasColumn('fonctionnalites', 'created_at') ? now() : null,
                ]
            );
        }

        // Map tech_label -> id
        $permIds = DB::table('fonctionnalites')
            ->whereIn('tech_label', $perms)
            ->pluck('id', 'tech_label')
            ->toArray();

        // -------------------------
        // 2) Rôles -> permissions
        // -------------------------
        $roles = [
            // ✅ admin a tout
            'admin' => $perms,

            'reception' => [
                'patients.read','patients.write',
                'patients.view','patients.create','patients.orient',
                'personnes.view','personnes.create','personnes.update','personnes.delete',
                'visites.read','visites.write',
                'medecins.read','personnels.read','services.read','tarifs.read',
                'stats.view',
            ],

            'medecin' => [
                'patients.read',
                'consultations.view','consultations.create','consultations.update',
                'examen.create',
                'medecine.examen.create','aru.examen.create','gynecologie.examen.create',
                'maternite.examen.create','pediatrie.examen.create','sanitaire.examen.create',
                'consultations.examen.create','smi.examen.create',
                'stats.view',
            ],

            'infirmier' => [
                'patients.read',
                'pansement.view','pansement.create','pansement.update',
                'stats.view',
            ],

            'laborantin' => [
                'examen.view','examen.create','examen.request.create','examen.result.write',
                'stats.view','patients.read',
                'services.read','tarifs.read','medecins.read',
            ],

            // ✅ Pharmacien: ancien + nouveau module Pharmacie
            'pharmacien' => [
                // Anciennes (compat)
                'pharma.stock.view','pharma.sale.create','pharma.ordonnance.validate',

                // Nouvelles permissions pharmacie.*
                'pharmacie.dashboard.view',

                'pharmacie.stocks.view',
                'pharmacie.stocks.alertes',
                'pharmacie.stocks.seuils',

                'pharmacie.produits.view',

                'pharmacie.fournisseurs.view',

                'pharmacie.commandes.view',
                'pharmacie.commandes.create',
                'pharmacie.commandes.edit',

                'pharmacie.receptions.view',
                'pharmacie.receptions.create',
                'pharmacie.receptions.annuler',

                'pharmacie.ventes.view',
                'pharmacie.ventes.create',
                'pharmacie.ventes.annuler',

                'pharmacie.rapports.view',
                'pharmacie.rapports.export',

                'pharmacie.depots.view',
                'pharmacie.depots.create',
                'pharmacie.depots.edit',
                'pharmacie.depots.delete',

                'stats.view',
            ],

            'caissier' => [
                'caisse.access',
                'caisse.session.view',
                'caisse.session.manage',
                'caisse.report.view',
                'caisse.reglement.view',
                'caisse.reglement.create',
                'caisse.reglement.validate',
                'visites.read','stats.view',
                'services.read','tarifs.read','medecins.read','personnels.read',
            ],

            'caissier_service' => [
                'caisse.access',
                'caisse.session.view',
                'caisse.session.manage',
                'caisse.reglement.view',
                'caisse.reglement.create',
                'caisse.report.view',
            ],

            'caissier_general' => [
                'caisse.access',
                'caisse.session.view',
                'caisse.session.manage',
                'caisse.reglement.view',
                'caisse.reglement.create',
                'caisse.report.view',
                'caisse.report.global',
            ],

            'admin_caisse' => [
                'caisse.access',
                'caisse.session.view',
                'caisse.report.view',
                'caisse.report.global',
                'caisse.audit.view',
            ],

            'gestionnaire' => [
                'users.view',
                'stats.view',
            ],
        ];

        // Colonnes pivot (adaptatif)
        $rfTable = 'roles_fonctionnalites';
        $rfRoleCol = Schema::hasColumn($rfTable, 'roles_id')
            ? 'roles_id'
            : (Schema::hasColumn($rfTable, 'role_id') ? 'role_id' : null);

        $rfFoncCol = Schema::hasColumn($rfTable, 'fonc_id')
            ? 'fonc_id'
            : (Schema::hasColumn($rfTable, 'fonctionnalite_id') ? 'fonctionnalite_id' : null);

        if (!$rfRoleCol || !$rfFoncCol) {
            $this->command?->error("Colonnes inattendues dans roles_fonctionnalites. Attendu roles_id & fonc_id.");
            return;
        }

        foreach ($roles as $roleLabel => $allowedTechLabels) {
            // Crée rôle
            DB::table('roles')->updateOrInsert(
                ['label' => $roleLabel],
                ['description' => ucfirst(str_replace('_', ' ', $roleLabel))]
            );

            $roleId = DB::table('roles')->where('label', $roleLabel)->value('id');

            // ✅ purge liaisons existantes
            DB::table($rfTable)->where($rfRoleCol, $roleId)->delete();

            // Attache permissions au rôle
            foreach ($allowedTechLabels as $tech) {
                if (!isset($permIds[$tech])) {
                    continue;
                }

                $payload = [
                    $rfRoleCol => $roleId,
                    $rfFoncCol => $permIds[$tech],
                ];

                if (Schema::hasColumn($rfTable, 'deleted_at')) $payload['deleted_at'] = null;
                if (Schema::hasColumn($rfTable, 'created_at')) $payload['created_at'] = now();
                if (Schema::hasColumn($rfTable, 'updated_at')) $payload['updated_at'] = now();

                DB::table($rfTable)->insert($payload);
            }
        }

        $this->command?->info("✅ SynapseRolesAndPermissionsSeeder : rôles + fonctionnalités + liaisons créés.");
    }
}
