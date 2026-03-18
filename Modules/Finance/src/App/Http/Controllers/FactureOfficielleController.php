<?php

namespace Modules\Finance\App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Finance\App\Models\FactureOfficielle;
use Carbon\Carbon;

class FactureOfficielleController extends Controller
{
    /**
     * GET /api/v1/finance/factures-officielles
     * Query:
     * - module_source
     * - q (numero_global / client_nom / client_reference)
     * - date_from, date_to
     * - per_page
     */
    public function index(Request $request)
    {
        $v = $request->validate([
            'module_source' => 'nullable|string|max:30',
            'q'            => 'nullable|string|max:100',
            'date_from'    => 'nullable|date',
            'date_to'      => 'nullable|date',
            'per_page'     => 'nullable|integer|min:1|max:200',
        ]);

        $perPage = (int) ($v['per_page'] ?? 20);

        $q = FactureOfficielle::query()->orderByDesc('id');

        if (!empty($v['module_source'])) {
            $q->where('module_source', $v['module_source']);
        }

        if (!empty($v['q'])) {
            $term = trim($v['q']);
            $q->where(function ($w) use ($term) {
                $w->where('numero_global', 'like', "%{$term}%")
                  ->orWhere('client_nom', 'like', "%{$term}%")
                  ->orWhere('client_reference', 'like', "%{$term}%");
            });
        }

        if (!empty($v['date_from']) || !empty($v['date_to'])) {
            $from = !empty($v['date_from'])
                ? Carbon::parse($v['date_from'])->startOfDay()
                : Carbon::now()->subYears(10)->startOfDay();

            $to = !empty($v['date_to'])
                ? Carbon::parse($v['date_to'])->endOfDay()
                : Carbon::now()->endOfDay();

            // date_emission cast en date => ok
            $q->whereBetween('date_emission', [$from->toDateString(), $to->toDateString()]);
        }

        // ✅ on garde ton paginate brut (comme avant)
        return response()->json($q->paginate($perPage));
    }

    /**
     * GET /api/v1/finance/factures-officielles/{numero_global}
     */
    public function show(string $numero_global)
    {
        $facture = FactureOfficielle::query()
            ->where('numero_global', $numero_global)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $facture,
        ]);
    }

    /**
     * ✅ OPTION A
     * GET /api/v1/finance/factures-officielles/by-billing-request/{billingRequestId}
     *
     * Permet de retrouver la facture officielle (numero_global) via billing_request_id
     * pour l'afficher dans le registre (au lieu de "BR #12").
     */
    public function byBillingRequest(int $billingRequestId)
    {
        $facture = FactureOfficielle::query()
            ->where('billing_request_id', $billingRequestId)
            ->first();

        return response()->json([
            'success' => true,
            'data' => $facture ? [
                'numero_global' => $facture->numero_global,
                'date_emission' => $facture->date_emission,
                'total_ttc' => $facture->total_ttc,
                'statut' => $facture->statut,
                'statut_paiement' => $facture->statut_paiement,
            ] : null,
        ]);
    }
}