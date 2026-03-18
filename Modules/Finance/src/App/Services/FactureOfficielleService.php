<?php

namespace Modules\Finance\App\Services;

use Modules\Finance\App\Models\FactureCounter;  // ✅ Changé ici
use Illuminate\Support\Facades\DB;
use Modules\Finance\App\Models\FactureOfficielle;

class FactureOfficielleService
{
    public function createIfNotExists(
        string $moduleSource,
        string $tableSource,
        int $sourceId,
        float $totalTtc,
        ?string $clientNom = null,
        ?string $clientRef = null,
        ?string $dateEmission = null
    ): FactureOfficielle {
        return DB::transaction(function () use (
            $moduleSource, $tableSource, $sourceId, $totalTtc, $clientNom, $clientRef, $dateEmission
        ) {

            $existing = FactureOfficielle::query()
                ->where('module_source', $moduleSource)
                ->where('table_source', $tableSource)
                ->where('source_id', $sourceId)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $numero = $this->nextNumeroGlobal();

           return FactureOfficielle::create([
                'numero_global'    => $numero,
                'module_source'    => $moduleSource,
                'table_source'     => $tableSource,
                'source_id'        => $sourceId,

                // ✅ IMPORTANT: si la facture est liée aux billing requests
                'billing_request_id' => $tableSource === 't_billing_requests' ? $sourceId : null,

                'total_ht'         => 0,
                'total_tva'        => 0,
                'total_ttc'        => $totalTtc,
                'client_nom'       => $clientNom,
                'client_reference' => $clientRef,
                'date_emission'    => $dateEmission ? now()->parse($dateEmission)->toDateString() : now()->toDateString(),
                'statut'           => 'EMISE',

                // ⚠️ petit point: si facture créée = pas forcément payée
                // mets plutôt NON_PAYE par défaut
                'statut_paiement'  => 'NON_PAYE',
            ]);
        });
    }

    private function nextNumeroGlobal(): string
    {
        $annee = (int) now()->format('Y');

        return DB::transaction(function () use ($annee) {

            FactureCounter::query()->updateOrCreate(
                ['annee' => $annee],
                ['compteur' => DB::raw('compteur')]
            );

            $counter = FactureCounter::query()
                ->where('annee', $annee)
                ->lockForUpdate()
                ->firstOrFail();

            $counter->increment('compteur');
            $counter->refresh();

            $seq = str_pad((string) $counter->compteur, 6, '0', STR_PAD_LEFT);

            return "FAC-{$annee}-{$seq}";
        });
    }
}