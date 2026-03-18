<?php

namespace Modules\Pharmacie\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PharmaciePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Créer les permissions
        $permissions = [
            // Produits
            'pharmacie.produits.view',
            'pharmacie.produits.create',
            'pharmacie.produits.edit',
            'pharmacie.produits.delete',

            // Fournisseurs
            'pharmacie.fournisseurs.view',
            'pharmacie.fournisseurs.create',
            'pharmacie.fournisseurs.edit',
            'pharmacie.fournisseurs.delete',

            // Commandes
            'pharmacie.commandes.view',
            'pharmacie.commandes.create',
            'pharmacie.commandes.edit',
            'pharmacie.commandes.delete',

            // Réceptions
            'pharmacie.receptions.view',
            'pharmacie.receptions.create',

            // Ventes
            'pharmacie.ventes.view',
            'pharmacie.ventes.create',
            'pharmacie.ventes.annuler',

            // Stock
            'pharmacie.stocks.view',
            'pharmacie.stocks.alertes',
            'pharmacie.stocks.seuils',

            // Rapports
            'pharmacie.rapports.view',
            'pharmacie.rapports.export',

            // Dashboard
            'pharmacie.dashboard.view',

            // Administration
            'pharmacie.admin.all',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Créer les rôles et assigner les permissions
        $this->createRoles();
    }

    private function createRoles(): void
    {
        // 1. Pharmacien Chef (tout accès)
        $pharmacienChef = Role::firstOrCreate(['name' => 'Pharmacien Chef']);
        $pharmacienChef->givePermissionTo(Permission::where('name', 'like', 'pharmacie.%')->get());

        // 2. Pharmacien
        $pharmacien = Role::firstOrCreate(['name' => 'Pharmacien']);
        $pharmacien->givePermissionTo([
            'pharmacie.produits.view',
            'pharmacie.fournisseurs.view',
            'pharmacie.commandes.view',
            'pharmacie.commandes.create',
            'pharmacie.receptions.view',
            'pharmacie.receptions.create',
            'pharmacie.ventes.view',
            'pharmacie.ventes.create',
            'pharmacie.stocks.view',
            'pharmacie.stocks.alertes',
            'pharmacie.rapports.view',
            'pharmacie.dashboard.view',
        ]);

        // 3. Vendeur
        $vendeur = Role::firstOrCreate(['name' => 'Vendeur Pharmacie']);
        $vendeur->givePermissionTo([
            'pharmacie.produits.view',
            'pharmacie.ventes.view',
            'pharmacie.ventes.create',
            'pharmacie.stocks.view',
        ]);

        // 4. Gestionnaire Stock
        $gestionnaireStock = Role::firstOrCreate(['name' => 'Gestionnaire Stock']);
        $gestionnaireStock->givePermissionTo([
            'pharmacie.produits.view',
            'pharmacie.produits.create',
            'pharmacie.produits.edit',
            'pharmacie.fournisseurs.view',
            'pharmacie.fournisseurs.create',
            'pharmacie.commandes.view',
            'pharmacie.commandes.create',
            'pharmacie.receptions.view',
            'pharmacie.receptions.create',
            'pharmacie.stocks.view',
            'pharmacie.stocks.alertes',
            'pharmacie.stocks.seuils',
            'pharmacie.rapports.view',
            'pharmacie.rapports.export',
            'pharmacie.dashboard.view',
        ]);

        // 5. Consultation (lecture seule)
        $consultation = Role::firstOrCreate(['name' => 'Consultation Pharmacie']);
        $consultation->givePermissionTo([
            'pharmacie.produits.view',
            'pharmacie.fournisseurs.view',
            'pharmacie.commandes.view',
            'pharmacie.receptions.view',
            'pharmacie.ventes.view',
            'pharmacie.stocks.view',
            'pharmacie.rapports.view',
            'pharmacie.dashboard.view',
        ]);
    }
}