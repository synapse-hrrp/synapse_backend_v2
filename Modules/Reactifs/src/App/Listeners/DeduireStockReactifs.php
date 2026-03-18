<?php

namespace Modules\Reactifs\App\Listeners; 

use Modules\Reactifs\App\Events\ExamenTermine;
use Modules\Reactifs\App\Models\Reactif;
use Modules\Reactifs\App\Models\ReactifExamenType;
use Modules\Reactifs\App\Models\ReactifStockMouvement;
use Modules\Reactifs\App\Models\ReactifConsommation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeduireStockReactifs
{
    public function handle(ExamenTermine $event): void
    {
        $liaisons = ReactifExamenType::where('examen_type_id', $event->examenTypeId)
            ->where('actif', true)
            ->with('reactif')
            ->get();

        if ($liaisons->isEmpty()) {
            return;
        }

        foreach ($liaisons as $liaison) {
            $this->deduireReactif($liaison, $event);
        }
    }

    private function deduireReactif(ReactifExamenType $liaison, ExamenTermine $event): void
    {
        DB::transaction(function () use ($liaison, $event) {
            $reactif = Reactif::lockForUpdate()->find($liaison->reactif_id);

            if (!$reactif || !$reactif->actif) {
                return;
            }

            $stockAvant = $reactif->stock_actuel;
            $quantite   = $liaison->quantite_utilisee;
            $stockApres = max(0, $stockAvant - $quantite);

            $reactif->update(['stock_actuel' => $stockApres]);

            $mouvement = ReactifStockMouvement::create([
                'reactif_id'     => $reactif->id,
                'type'           => 'consommation',
                'quantite'       => $quantite,
                'stock_avant'    => $stockAvant,
                'stock_apres'    => $stockApres,
                'reference'      => 'EXAMEN-' . $event->examenId,
                'user_id'        => $event->userId,
                'motif'          => 'Consommation automatique examen #' . $event->examenId,
                'date_mouvement' => now(),
            ]);

            ReactifConsommation::create([
                'reactif_id'         => $reactif->id,
                'examen_id'          => $event->examenId,
                'examen_type_id'     => $event->examenTypeId,
                'quantite_consommee' => $quantite,
                'stock_avant'        => $stockAvant,
                'stock_apres'        => $stockApres,
                'mouvement_id'       => $mouvement->id,
                'consomme_le'        => now(),
            ]);

            if ($stockApres <= $reactif->stock_minimum) {
                Log::warning("STOCK BAS - Réactif [{$reactif->code}] {$reactif->nom} : stock={$stockApres} {$reactif->unite} / minimum={$reactif->stock_minimum} {$reactif->unite}");
            }
        });
    }
}