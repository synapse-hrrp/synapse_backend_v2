<?php

namespace Modules\Pharmacie\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Users\App\Models\User;                 // ✅ bon User (avec roles(), hasPermission(), etc.)
use Modules\Users\App\Models\Role;
use Modules\Users\App\Models\Fonctionnalite;
use Modules\Pharmacie\App\Models\Produit;
use Modules\Pharmacie\App\Models\Depot;
use Modules\Pharmacie\App\Models\Fournisseur;
use Modules\Pharmacie\App\Models\Stock;

class PharmacieTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Depot $depot;
    protected Fournisseur $fournisseur;
    protected Produit $produit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $role = Role::firstOrCreate(
            ['label' => 'pharmacie_test'],
            ['description' => 'Role Test Pharmacie']
        );

        $this->user->roles()->syncWithoutDetaching([$role->id]);

        $techLabels = [
            'pharmacie.receptions.create',
            'pharmacie.ventes.create',
            'pharmacie.ventes.annuler',
        ];

        $foncIds = [];
        foreach ($techLabels as $tech) {
            $fonc = Fonctionnalite::firstOrCreate(
                ['tech_label' => $tech],
                [
                    'label' => $tech, // adapte si champ différent
                    'actif' => true,  // adapte si champ différent
                ]
            );

            $foncIds[] = $fonc->id;
        }

        $role->fonctionnalites()->syncWithoutDetaching($foncIds);
        $this->user->flushPermissionsCache();

        $this->depot = Depot::firstOrCreate(
            ['code' => 'HOP_TEST'],
            [
                'libelle' => 'Stock Hôpital Test',
                'actif'   => true,
            ]
        );

        $this->fournisseur = Fournisseur::create([
            'nom'   => 'Fournisseur Test ' . uniqid(),
            'actif' => true,
        ]);

        $this->produit = Produit::firstOrCreate(
            ['code' => 'TEST001'],
            [
                'nom'       => 'Paracétamol 500mg Test',
                'prix_achat' => 100,
                'taxable'   => true,
                'actif'     => true,
            ]
        );
    }

    public function test_reception_augmente_stock(): void
    {
        $lot = 'LOT001-' . uniqid();

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/pharmacie/receptions', [
                'fournisseur_id' => $this->fournisseur->id,
                'lignes' => [
                    [
                        'produit_id'      => $this->produit->id,
                        'depot_id'        => $this->depot->id,
                        'quantite'        => 50,
                        'numero_lot'      => $lot,
                        'date_peremption' => now()->addYear()->toDateString(),
                        'prix_achat'      => 100,
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('stocks', [
            'produit_id' => $this->produit->id,
            'depot_id'   => $this->depot->id,
            'numero_lot' => $lot,
            'quantite'   => 50,
        ]);
    }

    public function test_vente_applique_fefo(): void
    {
        $lotAncien = 'LOT_ANCIEN_' . uniqid();
        $lotRecent = 'LOT_RECENT_' . uniqid();

        Stock::create([
            'produit_id'      => $this->produit->id,
            'depot_id'        => $this->depot->id,
            'numero_lot'      => $lotAncien,
            'date_peremption' => now()->addMonths(3)->toDateString(),
            'quantite'        => 10,
            'prix_achat'      => 100,
        ]);

        Stock::create([
            'produit_id'      => $this->produit->id,
            'depot_id'        => $this->depot->id,
            'numero_lot'      => $lotRecent,
            'date_peremption' => now()->addMonths(12)->toDateString(),
            'quantite'        => 10,
            'prix_achat'      => 100,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/pharmacie/ventes', [
                'depot_id' => $this->depot->id,
                'type'     => 'VENTE',
                'lignes'   => [
                    [
                        'produit_id' => $this->produit->id,
                        'quantite'   => 8,
                    ],
                ],
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('stocks', [
            'numero_lot' => $lotAncien,
            'quantite'   => 2,
        ]);

        $this->assertDatabaseHas('stocks', [
            'numero_lot' => $lotRecent,
            'quantite'   => 10,
        ]);
    }

    public function test_vente_bloque_lot_perime(): void
    {
        $lotPerime = 'LOT_PERIME_' . uniqid();

        Stock::create([
            'produit_id'      => $this->produit->id,
            'depot_id'        => $this->depot->id,
            'numero_lot'      => $lotPerime,
            'date_peremption' => now()->subDay()->toDateString(),
            'quantite'        => 100,
            'prix_achat'      => 100,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/pharmacie/ventes', [
                'depot_id' => $this->depot->id,
                'type'     => 'VENTE',
                'lignes'   => [
                    [
                        'produit_id' => $this->produit->id,
                        'quantite'   => 5,
                    ],
                ],
            ]);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);

        $this->assertDatabaseHas('stocks', [
            'numero_lot' => $lotPerime,
            'quantite'   => 100,
        ]);
    }

    public function test_annulation_remet_stock_correctement(): void
    {
        $lotTest = 'LOT_TEST_' . uniqid();

        Stock::create([
            'produit_id'      => $this->produit->id,
            'depot_id'        => $this->depot->id,
            'numero_lot'      => $lotTest,
            'date_peremption' => now()->addYear()->toDateString(),
            'quantite'        => 20,
            'prix_achat'      => 100,
        ]);

        $venteResponse = $this->actingAs($this->user)
            ->postJson('/api/v1/pharmacie/ventes', [
                'depot_id' => $this->depot->id,
                'type'     => 'VENTE',
                'lignes'   => [
                    [
                        'produit_id' => $this->produit->id,
                        'quantite'   => 5,
                    ],
                ],
            ]);

        $venteResponse->assertStatus(201);
        $venteId = $venteResponse->json('data.id');

        $this->assertDatabaseHas('stocks', [
            'numero_lot' => $lotTest,
            'quantite'   => 15,
        ]);

        $annulResponse = $this->actingAs($this->user)
            ->postJson("/api/v1/pharmacie/ventes/{$venteId}/annuler");

        $annulResponse->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('stocks', [
            'numero_lot' => $lotTest,
            'quantite'   => 20,
        ]);

        $this->assertDatabaseHas('ventes', [
            'id'     => $venteId,
            'statut' => 'ANNULEE',
        ]);
    }
}