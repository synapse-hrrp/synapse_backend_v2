<?php

namespace Modules\Reactifs\App\Services;

use Modules\Reactifs\App\Models\Reactif;
use Modules\Reactifs\App\Models\ReactifStockMouvement;
use Modules\Reactifs\App\Models\ReactifCommandeLigne;
use Modules\Reactifs\App\Models\ReactifCommande;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReactifStockService
{
    public function entreeManuelle(Reactif $reactif, float $quantite, string $motif = null): ReactifStockMouvement
    {
        return DB::transaction(function () use ($reactif, $quantite, $motif) {
            $stockAvant = $reactif->stock_actuel;
            $stockApres = $stockAvant + $quantite;

            $reactif->update(['stock_actuel' => $stockApres]);

            return ReactifStockMouvement::create([
                'reactif_id'     => $reactif->id,
                'type'           => 'entree',
                'quantite'       => $quantite,
                'stock_avant'    => $stockAvant,
                'stock_apres'    => $stockApres,
                'user_id'        => Auth::id(),
                'motif'          => $motif ?? 'Entrée manuelle',
                'date_mouvement' => now(),
            ]);
        });
    }

    public function sortieManuelle(Reactif $reactif, float $quantite, string $motif = null): ReactifStockMouvement
    {
        return DB::transaction(function () use ($reactif, $quantite, $motif) {
            $stockAvant = $reactif->stock_actuel;
            $stockApres = max(0, $stockAvant - $quantite);

            $reactif->update(['stock_actuel' => $stockApres]);

            return ReactifStockMouvement::create([
                'reactif_id'     => $reactif->id,
                'type'           => 'sortie',
                'quantite'       => $quantite,
                'stock_avant'    => $stockAvant,
                'stock_apres'    => $stockApres,
                'user_id'        => Auth::id(),
                'motif'          => $motif ?? 'Sortie manuelle',
                'date_mouvement' => now(),
            ]);
        });
    }

    public function ajustement(Reactif $reactif, float $nouveauStock, string $motif = null): ReactifStockMouvement
    {
        return DB::transaction(function () use ($reactif, $nouveauStock, $motif) {
            $stockAvant = $reactif->stock_actuel;

            $reactif->update(['stock_actuel' => $nouveauStock]);

            return ReactifStockMouvement::create([
                'reactif_id'     => $reactif->id,
                'type'           => 'ajustement',
                'quantite'       => abs($nouveauStock - $stockAvant),
                'stock_avant'    => $stockAvant,
                'stock_apres'    => $nouveauStock,
                'user_id'        => Auth::id(),
                'motif'          => $motif ?? 'Ajustement inventaire',
                'date_mouvement' => now(),
            ]);
        });
    }

    public function receptionnerLigne(ReactifCommandeLigne $ligne, float $quantiteRecue): ReactifStockMouvement
    {
        return DB::transaction(function () use ($ligne, $quantiteRecue) {
            $reactif    = $ligne->reactif;
            $stockAvant = $reactif->stock_actuel;
            $stockApres = $stockAvant + $quantiteRecue;

            $reactif->update(['stock_actuel' => $stockApres]);

            $ligne->update([
                'quantite_recue' => $ligne->quantite_recue + $quantiteRecue,
                'statut'         => ($ligne->quantite_recue + $quantiteRecue) >= $ligne->quantite_commandee
                    ? 'recue'
                    : 'partiellement_recue',
            ]);

            $this->majStatutCommande($ligne->commande_id);

            return ReactifStockMouvement::create([
                'reactif_id'     => $reactif->id,
                'type'           => 'entree',
                'quantite'       => $quantiteRecue,
                'stock_avant'    => $stockAvant,
                'stock_apres'    => $stockApres,
                'reference'      => 'COMMANDE-' . $ligne->commande_id,
                'user_id'        => Auth::id(),
                'motif'          => 'Réception commande #' . $ligne->commande_id,
                'date_mouvement' => now(),
            ]);
        });
    }

    public function perte(Reactif $reactif, float $quantite, string $motif = null): ReactifStockMouvement
    {
        return DB::transaction(function () use ($reactif, $quantite, $motif) {
            $stockAvant = $reactif->stock_actuel;
            $stockApres = max(0, $stockAvant - $quantite);

            $reactif->update(['stock_actuel' => $stockApres]);

            return ReactifStockMouvement::create([
                'reactif_id'     => $reactif->id,
                'type'           => 'perte',
                'quantite'       => $quantite,
                'stock_avant'    => $stockAvant,
                'stock_apres'    => $stockApres,
                'user_id'        => Auth::id(),
                'motif'          => $motif ?? 'Perte / péremption',
                'date_mouvement' => now(),
            ]);
        });
    }

    public function getReactifsEnAlerte(): \Illuminate\Database\Eloquent\Collection
    {
        return Reactif::where('actif', true)
            ->whereColumn('stock_actuel', '<=', 'stock_minimum')
            ->orderBy('stock_actuel')
            ->get();
    }

    private function majStatutCommande(int $commandeId): void
    {
        $lignes = ReactifCommandeLigne::where('commande_id', $commandeId)->get();

        $toutesRecues   = $lignes->every(fn($l) => $l->statut === 'recue');
        $aucuneRecue    = $lignes->every(fn($l) => $l->statut === 'en_attente');
        $partielleRecue = !$toutesRecues && !$aucuneRecue;

        $statut = match(true) {
            $toutesRecues   => 'recue',
            $partielleRecue => 'partiellement_recue',
            default         => 'envoyee',
        };

        ReactifCommande::where('id', $commandeId)->update(['statut' => $statut]);
    }
}