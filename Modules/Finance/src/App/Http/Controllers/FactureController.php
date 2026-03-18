<?php

namespace Modules\Finance\App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FactureController extends Controller
{
    private function paidAggQuery()
    {
        return DB::table('t_finance_paiements')
            ->selectRaw("module_source, table_source, source_id, COALESCE(SUM(montant),0) AS total_paye")
            ->where('statut', 'valide')
            ->groupBy('module_source', 'table_source', 'source_id');
    }

    private function receptionQuery($paidAgg)
    {
        return DB::table('t_billing_requests AS s')
            ->leftJoinSub($paidAgg, 'p', function ($join) {
                $join->on('p.source_id', '=', 's.id')
                    ->where('p.table_source', '=', 't_billing_requests')
                    ->where('p.module_source', '=', 'reception');
            })
            ->selectRaw("
                s.id AS source_id,
                'reception' AS module_source,
                't_billing_requests' AS table_source,
                COALESCE(s.ref_source, CONCAT('BR-', s.id)) AS numero,
                s.statut AS statut_source,
                s.montant_total AS total_du,
                COALESCE(p.total_paye, 0) AS total_paye,
                GREATEST(ROUND(s.montant_total - COALESCE(p.total_paye, 0), 2), 0) AS reste,
                CASE
                    WHEN COALESCE(p.total_paye, 0) <= 0 THEN 'NON_PAYE'
                    WHEN COALESCE(p.total_paye, 0) < s.montant_total THEN 'PARTIEL'
                    ELSE 'PAYE'
                END AS statut_finance,
                COALESCE(s.created_at, s.updated_at) AS date_facture,

                CASE
                    WHEN COALESCE(p.total_paye, 0) <= 0 THEN 'pending'
                    WHEN COALESCE(p.total_paye, 0) < s.montant_total THEN 'partial'
                    ELSE 'paid'
                END AS statut_source_normalise,

                CASE
                    WHEN s.statut = 'cancelled' THEN 0
                    WHEN (s.statut = 'paid' AND COALESCE(p.total_paye, 0) < s.montant_total) THEN 1
                    WHEN (s.statut = 'pending' AND COALESCE(p.total_paye, 0) > 0) THEN 1
                    ELSE 0
                END AS incoherence_source
            ");
    }

    private function pharmacieQuery($paidAgg)
    {
        return DB::table('ventes AS s')
            ->leftJoinSub($paidAgg, 'p', function ($join) {
                $join->on('p.source_id', '=', 's.id')
                    ->where('p.table_source', '=', 'ventes')
                    ->where('p.module_source', '=', 'pharmacie');
            })
            ->selectRaw("
                s.id AS source_id,
                'pharmacie' AS module_source,
                'ventes' AS table_source,
                COALESCE(s.numero, CONCAT('V-', s.id)) AS numero,
                s.statut AS statut_source,
                s.montant_ttc AS total_du,
                COALESCE(p.total_paye, 0) AS total_paye,
                GREATEST(ROUND(s.montant_ttc - COALESCE(p.total_paye, 0), 2), 0) AS reste,
                CASE
                    WHEN COALESCE(p.total_paye, 0) <= 0 THEN 'NON_PAYE'
                    WHEN COALESCE(p.total_paye, 0) < s.montant_ttc THEN 'PARTIEL'
                    ELSE 'PAYE'
                END AS statut_finance,
                COALESCE(s.date_vente, s.created_at) AS date_facture,

                CASE
                    WHEN s.statut = 'ANNULEE' THEN 'ANNULEE'
                    WHEN COALESCE(p.total_paye, 0) >= s.montant_ttc AND s.montant_ttc > 0 THEN 'PAYEE'
                    ELSE 'EN_ATTENTE'
                END AS statut_source_normalise,

                CASE
                    WHEN s.statut = 'ANNULEE' THEN 0
                    WHEN (s.statut = 'PAYEE' AND COALESCE(p.total_paye, 0) < s.montant_ttc) THEN 1
                    ELSE 0
                END AS incoherence_source
            ");
    }

    public function index(Request $request)
    {
        $v = $request->validate([
            'module_source'  => 'nullable|in:reception,pharmacie',
            'statut_finance' => 'nullable|in:NON_PAYE,PARTIEL,PAYE',
            'q'              => 'nullable|string|max:100',
            'date_from'      => 'nullable|date',
            'date_to'        => 'nullable|date',
            'per_page'       => 'nullable|integer|min:1|max:200',
        ]);

        $perPage = (int) ($v['per_page'] ?? 20);

        $paidAgg   = $this->paidAggQuery();
        $reception = $this->receptionQuery($paidAgg);
        $pharmacie = $this->pharmacieQuery($paidAgg);

        $union = !empty($v['module_source'])
            ? ($v['module_source'] === 'reception' ? $reception : $pharmacie)
            : $reception->unionAll($pharmacie);

        $base = DB::query()->fromSub($union, 'factures');

        if (!empty($v['q'])) {
            $q = trim($v['q']);
            $base->where('numero', 'like', "%{$q}%");
        }

        if (!empty($v['statut_finance'])) {
            $base->where('statut_finance', $v['statut_finance']);
        }

        if (!empty($v['date_from']) || !empty($v['date_to'])) {
            $from = !empty($v['date_from'])
                ? Carbon::parse($v['date_from'])->startOfDay()
                : Carbon::now()->subYears(10)->startOfDay();

            $to = !empty($v['date_to'])
                ? Carbon::parse($v['date_to'])->endOfDay()
                : Carbon::now()->endOfDay();

            $base->whereBetween('date_facture', [$from, $to]);
        }

        $base->orderByDesc('date_facture')->orderByDesc('source_id');

        return response()->json($base->paginate($perPage));
    }

    public function show(Request $request, string $module_source, int $source_id)
    {
        abort_unless(in_array($module_source, ['reception', 'pharmacie'], true), 422, 'module_source invalide.');

        $paidAgg   = $this->paidAggQuery();
        $reception = $this->receptionQuery($paidAgg);
        $pharmacie = $this->pharmacieQuery($paidAgg);

        $union = $module_source === 'reception' ? $reception : $pharmacie;

        $facture = DB::query()
            ->fromSub($union, 'factures')
            ->where('module_source', $module_source)
            ->where('source_id', $source_id)
            ->first();

        abort_if(!$facture, 404, 'Facture introuvable.');

        $paiements = DB::table('t_finance_paiements as p')
            ->leftJoin('users as u', 'u.id', '=', 'p.encaisse_par_user_id')
            ->select('p.*', 'u.name as encaisse_par_nom')
            ->where('p.module_source', $module_source)
            ->where('p.table_source', $facture->table_source)
            ->where('p.source_id', $source_id)
            ->orderByDesc('p.id')
            ->get();

        $audits = DB::table('t_finance_audits as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
            ->select('a.*', 'u.name as user_nom')
            ->where('a.table_source', $facture->table_source)
            ->where('a.source_id', $source_id)
            ->orderByDesc('a.id')
            ->get()
            ->map(function ($a) {
                $a->payload = $a->payload ? json_decode($a->payload, true) : null;
                return $a;
            });

        return response()->json([
            'facture'   => $facture,
            'paiements' => $paiements,
            'audits'    => $audits,
        ]);
    }
}