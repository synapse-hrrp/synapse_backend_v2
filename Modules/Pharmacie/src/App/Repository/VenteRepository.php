<?php

namespace Modules\Pharmacie\App\Repository;

use Modules\Pharmacie\App\Models\Vente;
use Modules\Pharmacie\App\Models\LigneVente;
use Modules\Pharmacie\App\Models\LigneVenteStock;
use Modules\Pharmacie\App\Models\MouvementStock;
use Illuminate\Support\Facades\DB;

class VenteRepository
{
    /**
     * Créer une vente
     */
    public function creerVente(array $data): Vente
    {
        return Vente::create($data);
    }

    /**
     * Créer ligne de vente
     */
    public function creerLigneVente(array $data): LigneVente
    {
        return LigneVente::create($data);
    }

    /**
     * Créer ligne_vente_stock (traçabilité lots)
     */
    public function creerLigneVenteStock(array $data): LigneVenteStock
    {
        return LigneVenteStock::create($data);
    }

    /**
     * Créer mouvement stock
     */
    public function creerMouvement(array $data): MouvementStock
    {
        return MouvementStock::create($data);
    }

    /**
     * Récupérer vente avec relations
     */
    public function getVenteAvecLots(int $venteId)
    {
        return Vente::with([
            'lignes.lots.stock',
            'depot',
            'user'
        ])->findOrFail($venteId);
    }

    /**
     * Annuler une vente
     */
    public function annulerVente(int $venteId): bool
    {
        return Vente::where('id', $venteId)->update(['statut' => 'ANNULEE']);
    }
}