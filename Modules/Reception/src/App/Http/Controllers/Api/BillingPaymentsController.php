<?php

namespace Modules\Reception\App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Finance\App\Models\FinancePayment;
use Modules\Reception\App\Models\BillingRequest;
use Modules\Reception\App\Models\DailyRegisterEntry;
use Modules\Reception\App\Services\FinanceBridgeService;

class BillingPaymentsController extends Controller
{
    /**
     * Trouve une session finance ouverte pour un user + poste + module_scope,
     * sinon en crée une.
     */
    private function ensureFinanceSessionId(int $userId, string $poste = 'API', ?string $moduleScope = null): int
    {
        // session ouverte = fermee_le IS NULL
        $existingId = DB::table('t_finance_sessions')
            ->where('user_id', $userId)
            ->where('poste', $poste)
            ->whereNull('fermee_le')
            ->when($moduleScope !== null, fn($q) => $q->where('module_scope', $moduleScope))
            ->value('id');

        if ($existingId) {
            return (int) $existingId;
        }

        $now = now();

        // cle_ouverture unique (colonne unique)
        $cle = 'OPEN-' . $userId . '-' . $poste . '-' . $now->format('YmdHis') . '-' . random_int(100, 999);

        return (int) DB::table('t_finance_sessions')->insertGetId([
            'user_id'      => $userId,
            'poste'        => $poste,
            'module_scope' => $moduleScope,
            'ouverte_le'   => $now,
            'cle_ouverture'=> $cle,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);
    }

    public function pay(Request $request, int $billingRequestId)
    {
        $data = $request->validate([
            'amount'    => ['required', 'numeric', 'min:0.01'],
            'method'    => ['required', 'string', 'max:30'],     // ex: CASH, MOMO, CARD...
            'reference' => ['nullable', 'string', 'max:100'],
            'notes'     => ['nullable', 'string', 'max:255'],
            // optionnel: si tu veux laisser le front choisir le poste
            'poste'     => ['nullable', 'string', 'max:50'],
        ]);

        $user = $request->user();
        $cashierUserId = (int) ($user?->id ?? 0);

        if ($cashierUserId <= 0) {
            return response()->json(['message' => 'Utilisateur non authentifié.'], 401);
        }

        $poste = $data['poste'] ?? 'API';

        $result = DB::transaction(function () use ($billingRequestId, $data, $cashierUserId, $poste) {

            /** @var BillingRequest $br */
            $br = BillingRequest::query()->lockForUpdate()->findOrFail($billingRequestId);

            // ⚠️ ton modèle/DB utilise "statut"
            if (($br->statut ?? null) === BillingRequest::STATUS_CANCELLED) {
                abort(409, "Demande annulée, paiement impossible.");
            }

            // ✅ session caisse (NOT NULL dans t_finance_paiements)
            $sessionId = $this->ensureFinanceSessionId(
                userId: $cashierUserId,
                poste: $poste,
                moduleScope: FinanceBridgeService::MODULE_SOURCE // "reception"
            );

            // ✅ créer paiement finance
            FinancePayment::query()->create([
                'session_id'            => $sessionId,
                'encaisse_par_user_id'  => $cashierUserId,
                'module_source'         => FinanceBridgeService::MODULE_SOURCE, // reception
                'table_source'          => FinanceBridgeService::TABLE_SOURCE,  // t_billing_requests
                'source_id'             => $br->id,
                'montant'               => (float) $data['amount'],
                'mode'                  => $data['method'],
                'reference'             => $data['reference'] ?? null,
                'statut'                => 'valide',
            ]);

            // ✅ recalcul payé depuis Finance (source de vérité)
            $paid  = FinancePayment::totalPaye(FinanceBridgeService::TABLE_SOURCE, (int) $br->id);
            $total = (float) ($br->montant_total ?? 0); // ✅ DB: montant_total

            // nouveau statut billing_request
            $newStatus = $paid + 0.00001 >= $total
                ? BillingRequest::STATUS_PAID
                : BillingRequest::STATUS_PARTIALLY_PAID;

            $br->update([
                'montant_paye' => $paid,
                'statut'       => $newStatus,
            ]);

            // ✅ Sync registre si module_source = reception
            $sourceModule = $br->module_source ?? null; // ✅ DB: module_source
            $sourceRef    = $br->ref_source ?? null;    // ✅ DB: ref_source

            if ($sourceModule === FinanceBridgeService::MODULE_SOURCE && !empty($sourceRef)) {
                $entryId = (int) $sourceRef;

                $entry = DailyRegisterEntry::query()->lockForUpdate()->find($entryId);

                if ($entry && !$entry->isLocked()) {
                    $entry->update([
                        'id_demande_paiement' => $br->id,
                        'statut' => ($newStatus === BillingRequest::STATUS_PAID)
                            ? DailyRegisterEntry::STATUS_PAID
                            : DailyRegisterEntry::STATUS_AWAITING_PAYMENT,
                    ]);
                }
            }

            // retourne la demande à jour
            return $br->fresh(['items']);
        });

        return response()->json([
            'message' => 'Paiement enregistré',
            'data' => $result,
        ]);
    }
}