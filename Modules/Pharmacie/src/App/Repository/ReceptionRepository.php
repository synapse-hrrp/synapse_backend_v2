<?php

namespace Modules\Pharmacie\App\Repository;

use Modules\Pharmacie\App\Models\Reception;
use Modules\Pharmacie\App\Models\LigneReception;
use Modules\Pharmacie\App\Models\Commande;
use Modules\Pharmacie\App\Models\LigneCommande;

class ReceptionRepository
{
    /**
     * Créer réception
     */
    public function creerReception(array $data): Reception
    {
        return Reception::create($data);
    }

    /**
     * Créer ligne réception
     */
    public function creerLigneReception(array $data): LigneReception
    {
        return LigneReception::create($data);
    }

    /**
     * Mettre à jour quantité reçue dans ligne_commande
     */
    public function mettreAJourQuantiteRecue(int $ligneCommandeId, int $quantite): void
    {
        LigneCommande::where('id', $ligneCommandeId)
            ->increment('quantite_recue', $quantite);
    }

    /**
     * Vérifier et mettre à jour statut commande
     */
    public function mettreAJourStatutCommande(int $commandeId): void
    {
        $commande = Commande::with('lignes')->findOrFail($commandeId);

        $complete = true;
        $partielle = false;

        foreach ($commande->lignes as $ligne) {

            // Si une ligne n'est pas totalement reçue
            if ($ligne->quantite_recue < $ligne->quantite_commandee) {
                $complete = false;
            }

            // Si au moins une ligne a été reçue
            if ($ligne->quantite_recue > 0) {
                $partielle = true;
            }
        }

        if ($complete) {
            $commande->update([
                'statut' => 'LIVREE'
            ]);
        } elseif ($partielle) {
            $commande->update([
                'statut' => 'LIVREE_PARTIELLE'
            ]);
        } else {
            // Aucune réception
            $commande->update([
                'statut' => 'ENVOYEE'
            ]);
        }
    }
}