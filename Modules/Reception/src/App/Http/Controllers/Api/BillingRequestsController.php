<?php

namespace Modules\Reception\App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Reception\App\Models\DailyRegisterEntry;
use Modules\Reception\App\Models\BillingRequest;
use Modules\Reception\App\Models\BillingRequestItem;
use Modules\Reception\App\Models\TariffItem;
use Modules\Reception\App\Services\FinanceBridgeService;
use RuntimeException;

class BillingRequestsController extends Controller
{
    public function __construct(
        private readonly FinanceBridgeService $financeBridge,
    ) {}

    private function formatBillingRequest(BillingRequest $br, $facture = null): array
    {
        return [
            'id' => $br->id,

            'patient_id' => $br->patient_id,
            'source_module' => $br->source_module,
            'source_ref' => $br->source_ref,
            'requested_by_agent_id' => $br->requested_by_agent_id,
            'status' => $br->status,

            'total_amount' => (float) $br->total_amount,
            'paid_amount' => (float) $br->paid_amount,
            'remaining_amount' => $br->remainingAmount(),

            'items' => $br->items?->map(function ($it) {
                return [
                    'id' => $it->id,
                    'billing_request_id' => $it->billing_request_id,
                    'service_id' => $it->service_id,
                    'tariff_item_id' => $it->tariff_item_id,
                    'qty' => $it->qty,
                    'unit_price' => (float) $it->unit_price,
                    'line_total' => (float) $it->line_total,
                    'notes' => $it->notes,
                    'service' => $it->billableService ? [
                        'id' => $it->billableService->id,
                        'code' => $it->billableService->code,
                        'name' => $it->billableService->libelle,
                        'category' => $it->billableService->categorie,
                    ] : null,
                ];
            })->values(),

            'patient' => $br->patient,
            'requestedByAgent' => $br->requestedByAgent,

            // ✅ BONUS: facture officielle
            'facture_officielle' => $facture ? [
                'numero_global' => $facture->numero_global,
                'date_emission' => $facture->date_emission,
                'total_ttc' => $facture->total_ttc,
                'statut' => $facture->statut,
                'statut_paiement' => $facture->statut_paiement,
            ] : null,

            'created_at' => optional($br->created_at)->toISOString(),
            'updated_at' => optional($br->updated_at)->toISOString(),
        ];
    }

    public function createFromRegister(Request $request, int $id)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.tariff_item_id' => ['required', 'integer', 'exists:tariff_items,id'],
            'items.*.qty' => ['nullable', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        /** @var DailyRegisterEntry $entry */
        $entry = DailyRegisterEntry::query()->with(['tariffPlan'])->findOrFail($id);

        if ($entry->isLocked()) {
            return response()->json(['message' => 'Entrée verrouillée (fermée/annulée).'], 422);
        }

        if (!empty($entry->id_demande_paiement)) {
            return response()->json([
                'message' => 'Cette entrée a déjà une demande de paiement.',
                'data' => ['billing_request_id' => $entry->id_demande_paiement],
            ], 409);
        }

        $user = $request->user() ?: auth('sanctum')->user();
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié.'], 401);
        }

        $requestedBy = (int) $user->id;

        try {
            $result = DB::transaction(function () use ($entry, $data, $requestedBy) {

                // 1) Create BillingRequest
                $br = BillingRequest::query()->create([
                    'id_patient' => (int) $entry->id_patient,
                    'module_source' => 'reception',
                    'ref_source' => (string) $entry->id,
                    'id_agent_demandeur' => $requestedBy,
                    'statut' => BillingRequest::STATUS_PENDING,
                    'montant_total' => 0,
                    'montant_paye' => 0,
                ]);

                // 2) Items
                $total = 0.0;

                foreach ($data['items'] as $it) {
                    $qty = (int) ($it['qty'] ?? 1);
                    if ($qty <= 0) $qty = 1;

                    $tariffItem = TariffItem::query()
                        ->with('service')
                        ->whereKey((int) $it['tariff_item_id'])
                        ->where('active', true)
                        ->first();

                    if (!$tariffItem || !$tariffItem->service || !$tariffItem->service->active) {
                        throw new RuntimeException("Tarif introuvable/inactif ou service inactif.");
                    }

                    if ((int) $tariffItem->tariff_plan_id !== (int) $entry->tariff_plan_id) {
                        throw new RuntimeException("Le tarif sélectionné ne correspond pas au plan de cette entrée.");
                    }

                    $unit = (float) ($tariffItem->prix_unitaire ?? 0);
                    $line = round($qty * $unit, 2);
                    $total += $line;

                    BillingRequestItem::query()->create([
                        'id_demande_facturation' => $br->id,
                        'billable_service_id' => (int) $tariffItem->billable_service_id,
                        'tariff_item_id' => (int) $tariffItem->id,
                        'quantite' => $qty,
                        'prix_unitaire' => $unit,

                        // ✅ FIX: notes (pas remarques)
                        'notes' => $it['notes'] ?? null,
                    ]);
                }

                $br->update(['montant_total' => round($total, 2)]);

                // 3) Link register -> BR
                $entry->update(['id_demande_paiement' => $br->id]);

                // 4) Sync status
                $entry->syncBillingAndStatus();

                // ✅ 5) AUTO: create facture officielle (numero_global)
                $facture = null;
                if ((float) ($br->montant_total ?? 0) > 0) {
                    $facture = $this->financeBridge->ensureFactureOfficielleForBillingRequest($br);
                }

                return [
                    'br' => $br->fresh(['items.billableService', 'patient', 'requestedByAgent']),
                    'facture' => $facture ? $facture->fresh() : null,
                ];
            });

            /** @var BillingRequest $br */
            $br = $result['br'];
            $facture = $result['facture'];

            return response()->json([
                'message' => 'Demande de paiement créée',
                'data' => $this->formatBillingRequest($br, $facture),
            ], 201);

        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(int $id)
    {
        $br = BillingRequest::query()
            ->with(['items.billableService', 'patient', 'requestedByAgent'])
            ->findOrFail($id);

        return response()->json([
            'message' => 'Détails demande de paiement',
            'data' => $this->formatBillingRequest($br),
        ]);
    }
}