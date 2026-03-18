<?php

namespace Modules\Reception\App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Reception\App\Models\DailyRegisterEntry;
use Modules\Reception\App\Services\BillingRequestBuilderService;
use Modules\Reception\App\Services\FinanceBridgeService;

class RegisterBillingController extends Controller
{
    public function __construct(
        private readonly BillingRequestBuilderService $builder,
        private readonly FinanceBridgeService $financeBridge,
    ) {}

    public function generate(Request $request, int $entryId)
    {
        $entry = DailyRegisterEntry::query()->findOrFail($entryId);

        if ($entry->isLocked()) {
            return response()->json(['success' => false, 'message' => 'Entrée verrouillée.'], 422);
        }

        // 1) BR (peut retourner null si paiement non obligatoire)
        $billing = $this->builder->generateBillingRequest($entryId);

        // 2) Sync statut registre
        $entry->syncBillingAndStatus();

        // ✅ Si aucun paiement requis => pas de BR => pas de facture
        if (!$billing) {
            return response()->json([
                'success' => true,
                'data' => [
                    'billing_request' => null,
                    'finance' => [
                        'facture_officielle' => null,
                        'paid_amount' => 0,
                        'payment_status' => null,
                        'module_source' => FinanceBridgeService::MODULE_SOURCE,
                        'table_source' => FinanceBridgeService::TABLE_SOURCE,
                        'source_id' => null,
                    ],
                ],
            ]);
        }

        // ✅ 3) AUTO: crée ou récupère la facture officielle (numero_global)
        $facture = null;
        if ((float) ($billing->montant_total ?? 0) > 0) {
            $facture = $this->financeBridge->ensureFactureOfficielleForBillingRequest($billing);
        }

        // 4) Finance summary
        $paidAmount = $this->financeBridge->totalPaidForBillingRequest($billing);
        $paymentStatus = $this->financeBridge->computePaymentStatus($billing);

        return response()->json([
            'success' => true,
            'data' => [
                'billing_request' => $billing->load('items'),
                'finance' => [
                    'facture_officielle' => $facture,
                    'numero_global' => $facture?->numero_global,
                    'paid_amount' => $paidAmount,
                    'payment_status' => $paymentStatus,
                    'module_source' => FinanceBridgeService::MODULE_SOURCE,
                    'table_source' => FinanceBridgeService::TABLE_SOURCE,
                    'source_id' => $billing->id,
                ],
            ],
        ]);
    }
}