<?php

use Illuminate\Support\Facades\Route;
use Modules\Pharmacie\App\Http\Controllers\Api\VenteController;
use Modules\Pharmacie\App\Http\Controllers\Api\ReceptionController;
use Modules\Pharmacie\App\Http\Controllers\Api\StockController;
use Modules\Pharmacie\App\Http\Controllers\Api\RapportController;
use Modules\Pharmacie\App\Http\Controllers\Api\ProduitController;
use Modules\Pharmacie\App\Http\Controllers\Api\FournisseurController;
use Modules\Pharmacie\App\Http\Controllers\Api\DepotController;
use Modules\Pharmacie\App\Http\Controllers\Api\CommandeController;
use Modules\Pharmacie\App\Http\Controllers\Api\DashboardController;
use Modules\Pharmacie\App\Http\Controllers\Api\PermissionController;
use Modules\Pharmacie\App\Http\Controllers\Api\AuditController;
use Modules\Pharmacie\App\Http\Controllers\Api\EtiquetteController;
use Modules\Pharmacie\App\Http\Controllers\Api\ConsommationController;
use Modules\Pharmacie\App\Http\Controllers\Api\CommandeAutoController;
use Modules\Pharmacie\App\Http\Controllers\Api\ExportController;
use Modules\Pharmacie\App\Http\Controllers\Api\ScanController;

/**
 * Toutes les routes Pharmacie sous /api/v1 comme les autres modules.
 */
Route::prefix('api/v1')->group(function () {

    /**
     * ✅ ROUTE INTERNE FINANCE (SANS auth:sanctum)
     */
    Route::prefix('pharmacie/internal')
        ->middleware(['finance.internal'])
        ->group(function () {
            Route::post('ventes/{vente}/valider', [VenteController::class, 'valider']);
        });

    /**
     * ✅ ROUTES NORMALES (FRONT)
     */
    Route::prefix('pharmacie')
        ->middleware(['auth:sanctum'])
        ->group(function () {

            // ========================================
            // ADMIN PHARMACIE
            // ========================================
            Route::prefix('admin')
                ->middleware('pharmacie.permission:pharmacie.admin.all')
                ->group(function () {
                    Route::get('roles', [PermissionController::class, 'roles']);
                    Route::get('permissions', [PermissionController::class, 'permissions']);
                    Route::post('assign-role', [PermissionController::class, 'assignRole']);
                    Route::post('remove-role', [PermissionController::class, 'removeRole']);
                });

            Route::get('my-permissions', [PermissionController::class, 'myPermissions']);
            Route::get('dashboard', [DashboardController::class, 'index'])
                ->middleware('pharmacie.permission:pharmacie.dashboard.view');

            // ========================================
            // PRODUITS
            // ========================================
            Route::prefix('produits')->group(function () {
                
                // ✅ ROUTES SPÉCIFIQUES EN PREMIER (AVANT /{id})
                
                // Infos stock du produit
                Route::get('/{id}/stock', [ProduitController::class, 'getStock'])
                    ->middleware('pharmacie.permission:pharmacie.stocks.view');
                
                // Historique des prix (réceptions)
                Route::get('/{id}/historique-prix', [ProduitController::class, 'getHistoriquePrix'])
                    ->middleware('pharmacie.permission:pharmacie.produits.view');
                
                // ✅ ROUTES CRUD STANDARD
                
                Route::get('/', [ProduitController::class, 'index'])
                    ->middleware('pharmacie.permission:pharmacie.produits.view');
                
                Route::post('/', [ProduitController::class, 'store'])
                    ->middleware('pharmacie.permission:pharmacie.produits.create');
                
                // ⚠️ Routes avec {id} EN DERNIER
                Route::get('/{id}', [ProduitController::class, 'show'])
                    ->middleware('pharmacie.permission:pharmacie.produits.view');
                
                Route::put('/{id}', [ProduitController::class, 'update'])
                    ->middleware('pharmacie.permission:pharmacie.produits.edit');
                
                Route::delete('/{id}', [ProduitController::class, 'destroy'])
                    ->middleware('pharmacie.permission:pharmacie.produits.delete');
            });

            // ========================================
            // FOURNISSEURS
            // ========================================
            Route::get('fournisseurs', [FournisseurController::class, 'index'])
                ->middleware('pharmacie.permission:pharmacie.fournisseurs.view');
            Route::post('fournisseurs', [FournisseurController::class, 'store'])
                ->middleware('pharmacie.permission:pharmacie.fournisseurs.create');
            Route::get('fournisseurs/{id}', [FournisseurController::class, 'show'])
                ->middleware('pharmacie.permission:pharmacie.fournisseurs.view');
            Route::put('fournisseurs/{id}', [FournisseurController::class, 'update'])
                ->middleware('pharmacie.permission:pharmacie.fournisseurs.edit');
            Route::delete('fournisseurs/{id}', [FournisseurController::class, 'destroy'])
                ->middleware('pharmacie.permission:pharmacie.fournisseurs.delete');

            // ========================================
            // DÉPÔTS
            // ========================================
            Route::get('depots', [DepotController::class, 'index'])
                ->middleware('pharmacie.permission:pharmacie.depots.view');
            Route::get('depots/{id}', [DepotController::class, 'show'])
                ->middleware('pharmacie.permission:pharmacie.depots.view');
            Route::post('depots', [DepotController::class, 'store'])
                ->middleware('pharmacie.permission:pharmacie.depots.create');
            Route::put('depots/{id}', [DepotController::class, 'update'])
                ->middleware('pharmacie.permission:pharmacie.depots.edit');
            Route::delete('depots/{id}', [DepotController::class, 'destroy'])
                ->middleware('pharmacie.permission:pharmacie.depots.delete');

            // ========================================
            // COMMANDES
            // ========================================
            Route::get('commandes', [CommandeController::class, 'index'])
                ->middleware('pharmacie.permission:pharmacie.commandes.view');
            Route::post('commandes', [CommandeController::class, 'store'])
                ->middleware('pharmacie.permission:pharmacie.commandes.create');
            Route::get('commandes/{id}', [CommandeController::class, 'show'])
                ->middleware('pharmacie.permission:pharmacie.commandes.view');
            Route::post('commandes/{commande}/lignes', [CommandeController::class, 'ajouterLigne'])
                ->middleware('pharmacie.permission:pharmacie.commandes.edit');

            // ========================================
            // RÉCEPTIONS
            // ========================================
            Route::post('receptions', [ReceptionController::class, 'store'])
                ->middleware('pharmacie.permission:pharmacie.receptions.create');
            Route::get('receptions', [ReceptionController::class, 'index'])
                ->middleware('pharmacie.permission:pharmacie.receptions.view');
            Route::get('receptions/{id}', [ReceptionController::class, 'show'])
                ->middleware('pharmacie.permission:pharmacie.receptions.view');
            Route::post('receptions/{id}/annuler', [ReceptionController::class, 'annuler'])
                ->middleware('pharmacie.permission:pharmacie.receptions.annuler');

            // ========================================
            // VENTES
            // ========================================
            Route::get('ventes', [VenteController::class, 'index'])
                ->middleware('pharmacie.permission:pharmacie.ventes.view');
            Route::get('ventes/{id}', [VenteController::class, 'show'])
                ->middleware('pharmacie.permission:pharmacie.ventes.view');
            Route::post('ventes', [VenteController::class, 'store'])
                ->middleware('pharmacie.permission:pharmacie.ventes.create');
            Route::post('ventes/{vente}/annuler', [VenteController::class, 'annuler'])
                ->middleware('pharmacie.permission:pharmacie.ventes.annuler');
            Route::post('ventes/{vente}/valider', [VenteController::class, 'valider'])
                ->middleware('pharmacie.permission:pharmacie.admin.all');

            // ========================================
            // STOCKS
            // ========================================
            Route::prefix('stocks')->group(function () {
                
                // ✅ ROUTES SPÉCIFIQUES EN PREMIER (AVANT /{id})
                
                // Statistiques
                Route::get('/statistiques', [StockController::class, 'statistiques'])
                    ->middleware('pharmacie.permission:pharmacie.stocks.view');
                
                // Alertes péremption
                Route::get('/perimes', [StockController::class, 'perimes'])
                    ->middleware('pharmacie.permission:pharmacie.stocks.alertes');
                
                Route::get('/proches', [StockController::class, 'proches'])
                    ->middleware('pharmacie.permission:pharmacie.stocks.alertes');
                
                Route::get('/peremption/proches', [StockController::class, 'prochesPeremption'])
                    ->middleware('pharmacie.permission:pharmacie.stocks.alertes');
                
                Route::get('/bon', [StockController::class, 'bon'])
                    ->middleware('pharmacie.permission:pharmacie.stocks.view');
                
                // Seuils
                Route::get('/seuil-min', [StockController::class, 'seuilMin'])
                    ->middleware('pharmacie.permission:pharmacie.stocks.seuils');
                
                Route::get('/seuil-max', [StockController::class, 'seuilMax'])
                    ->middleware('pharmacie.permission:pharmacie.stocks.seuils');
                
                // Filtres par dépôt/produit
                Route::get('/depot/{depot_id}', [StockController::class, 'parDepot'])
                    ->middleware('pharmacie.permission:pharmacie.stocks.view');
                
                Route::get('/produit/{produit_id}', [StockController::class, 'parProduit'])
                    ->middleware('pharmacie.permission:pharmacie.stocks.view');
                
                // ✅ NOUVEAU : Modifier prix de vente (AVANT /{id})
                Route::put('/{id}/prix-vente', [StockController::class, 'modifierPrixVente'])
                    ->middleware('pharmacie.permission:pharmacie.stocks.edit');
                
                // ✅ ROUTES CRUD EN DERNIER
                
                Route::get('/', [StockController::class, 'index'])
                    ->middleware('pharmacie.permission:pharmacie.stocks.view');
                
                Route::post('/', [StockController::class, 'store'])
                    ->middleware('pharmacie.permission:pharmacie.stocks.create');
                
                // ⚠️ Routes avec {id} TOUJOURS EN DERNIER
                Route::get('/{id}', [StockController::class, 'show'])
                    ->middleware('pharmacie.permission:pharmacie.stocks.view');
                
                Route::put('/{id}', [StockController::class, 'update'])
                    ->middleware('pharmacie.permission:pharmacie.stocks.edit');
                
                Route::delete('/{id}', [StockController::class, 'destroy'])
                    ->middleware('pharmacie.permission:pharmacie.stocks.delete');
            });

            // ========================================
            // NOTIFICATIONS
            // ========================================
            Route::post('notifications/test-alerte', [StockController::class, 'envoyerAlerteTest'])
                ->middleware('pharmacie.permission:pharmacie.admin.all');

            // ========================================
            // RAPPORTS
            // ========================================
            Route::prefix('rapports')->group(function () {
                Route::get('ventes/jour', [RapportController::class, 'ventesJour'])
                    ->middleware('pharmacie.permission:pharmacie.rapports.view');
                Route::get('ventes/semaine', [RapportController::class, 'ventesSemaine'])
                    ->middleware('pharmacie.permission:pharmacie.rapports.view');
                Route::get('ventes/mois', [RapportController::class, 'ventesMois'])
                    ->middleware('pharmacie.permission:pharmacie.rapports.view');
                Route::get('stock-restant', [RapportController::class, 'stockRestant'])
                    ->middleware('pharmacie.permission:pharmacie.rapports.view');
            });

            // ========================================
            // EXPORTS EXCEL
            // ========================================
            Route::prefix('exports')->group(function () {
                Route::get('ventes/jour', [RapportController::class, 'exportVentesJour'])
                    ->middleware('pharmacie.permission:pharmacie.rapports.export');
                Route::get('ventes/semaine', [RapportController::class, 'exportVentesSemaine'])
                    ->middleware('pharmacie.permission:pharmacie.rapports.export');
                Route::get('ventes/mois', [RapportController::class, 'exportVentesMois'])
                    ->middleware('pharmacie.permission:pharmacie.rapports.export');
                Route::get('stocks/alertes', [RapportController::class, 'exportAlertes'])
                    ->middleware('pharmacie.permission:pharmacie.rapports.export');
                Route::get('stocks/complet', [RapportController::class, 'exportStocksComplet'])
                    ->middleware('pharmacie.permission:pharmacie.rapports.export');
            });

            // ========================================
            // AUDIT / HISTORIQUE
            // ========================================
            Route::prefix('audit')
                ->middleware('pharmacie.permission:pharmacie.admin.all')
                ->group(function () {
                    Route::get('/', [AuditController::class, 'index']);
                    Route::get('stats', [AuditController::class, 'stats']);
                    Route::get('recent/{limit?}', [AuditController::class, 'recent']);
                    Route::get('export', [AuditController::class, 'export']);
                    Route::get('{type}/{id}', [AuditController::class, 'show']);
                });

            // ========================================
            // SCAN CODE-BARRES
            // ========================================
            Route::prefix('scan')->group(function () {
                Route::post('/', [ScanController::class, 'scanner'])
                    ->middleware('pharmacie.permission:pharmacie.ventes.create');
                Route::post('/multiple', [ScanController::class, 'scannerMultiple'])
                    ->middleware('pharmacie.permission:pharmacie.stocks.view');
                Route::post('/verifier-disponibilite', [ScanController::class, 'verifierDisponibilite'])
                    ->middleware('pharmacie.permission:pharmacie.ventes.create');
                Route::post('/associer', [ScanController::class, 'associerCodeBarre'])
                    ->middleware('pharmacie.permission:pharmacie.produits.edit');
                Route::post('/generer-code-interne', [ScanController::class, 'genererCodeInterne'])
                    ->middleware('pharmacie.permission:pharmacie.produits.edit');
                Route::get('/rechercher', [ScanController::class, 'rechercherSimilaires'])
                    ->middleware('pharmacie.permission:pharmacie.produits.view');
                Route::post('/valider-ean13', [ScanController::class, 'validerEAN13'])
                    ->middleware('pharmacie.permission:pharmacie.produits.view');
            });

            // ========================================
            // COMMANDES AUTOMATIQUES
            // ========================================
            Route::prefix('commandes-auto')->group(function () {
                Route::post('/verifier-tous', [CommandeAutoController::class, 'verifierTous'])
                    ->middleware('pharmacie.permission:pharmacie.admin.all');
                Route::post('/verifier', [CommandeAutoController::class, 'verifier'])
                    ->middleware('pharmacie.permission:pharmacie.commandes.view');
                Route::get('/en-attente', [CommandeAutoController::class, 'enAttente'])
                    ->middleware('pharmacie.permission:pharmacie.commandes.view');
                Route::post('/{id}/valider', [CommandeAutoController::class, 'valider'])
                    ->middleware('pharmacie.permission:pharmacie.commandes.edit');
                Route::get('/statistiques', [CommandeAutoController::class, 'statistiques'])
                    ->middleware('pharmacie.permission:pharmacie.commandes.view');
            });

            // ========================================
            // CONSOMMATIONS & ANALYSE
            // ========================================
            Route::prefix('consommations')->group(function () {
                Route::post('/analyser', [ConsommationController::class, 'analyser'])
                    ->middleware('pharmacie.permission:pharmacie.rapports.view');
                Route::post('/analyser-tous', [ConsommationController::class, 'analyserTous'])
                    ->middleware('pharmacie.permission:pharmacie.admin.all');
                Route::post('/enregistrer-semaine', [ConsommationController::class, 'enregistrerSemaine'])
                    ->middleware('pharmacie.permission:pharmacie.admin.all');
                Route::get('/surconsommation', [ConsommationController::class, 'detecterSurconsommation'])
                    ->middleware('pharmacie.permission:pharmacie.rapports.view');
            });

            // ========================================
            // EXPORTS COMMANDES & RÉCEPTIONS
            // ========================================
            Route::post('/commandes/{id}/exporter', [ExportController::class, 'exporterCommande'])
                ->middleware('pharmacie.permission:pharmacie.rapports.export');
            Route::post('/receptions/{id}/exporter', [ExportController::class, 'exporterReception'])
                ->middleware('pharmacie.permission:pharmacie.rapports.export');
            Route::get('/exports/download', [ExportController::class, 'telecharger'])
                ->middleware('pharmacie.permission:pharmacie.rapports.export');
        });
});