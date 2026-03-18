<?php

namespace Modules\Reception\App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Reception\App\Models\DailyRegisterEntry;
use Modules\Reception\App\Services\RegisterEntryItemService;
use Modules\Reception\App\Services\BillingRequestBuilderService;
use Modules\Reception\App\Services\FinanceBridgeService;
use RuntimeException;

class RegisterController extends Controller
{
    public function __construct(
        private readonly RegisterEntryItemService $entryItems,
        private readonly BillingRequestBuilderService $billingBuilder,
        private readonly FinanceBridgeService $financeBridge,
    ) {}

    private function baseWith(): array
    {
        return [
            'patient:id,personne_id,nip',
            'patient.personne:id,nom,prenom',

            // ✅ creator = User
            'createdByAgent:id,name,email',

            'tariffPlan:id,nom,type,active,paiement_obligatoire',

            // ✅ items + service + tariff item
            'items.billableService:id,code,libelle,categorie,active,rendez_vous_obligatoire,necessite_medecin,paiement_obligatoire_avant_prestation',
            'items.tariffItem:id,tariff_plan_id,billable_service_id,prix_unitaire,active',
        ];
    }

    private function patientDisplay($patient): string
    {
        $p = $patient?->personne;
        $display = trim(($p?->prenom ?? '') . ' ' . ($p?->nom ?? ''));
        return $display !== '' ? $display : ('Patient #' . ($patient?->id ?? 'N/A'));
    }

    private function formatEntry(DailyRegisterEntry $entry): array
    {
        $patient = $entry->patient;
        $arrivalIso = $entry->date_arrivee ? $entry->date_arrivee->toISOString() : null;

        return [
            'id' => $entry->id,
            'patient_id' => $entry->patient_id,
            'patient' => [
                'id' => $patient?->id,
                'personne_id' => $patient?->personne_id,
                'nip' => $patient?->nip ?? null,
                'display' => $this->patientDisplay($patient),
            ],

            'created_by_agent_id' => $entry->created_by_agent_id,
            'createdByAgent' => $entry->createdByAgent ? [
                'id' => $entry->createdByAgent->id,
                'name' => $entry->createdByAgent->name,
                'email' => $entry->createdByAgent->email,
            ] : null,

            'arrival_at' => $arrivalIso,
            'reason' => $entry->reason,
            'is_emergency' => (bool) $entry->is_emergency,

            'status' => $entry->status,
            'billing_request_id' => $entry->billing_request_id,

            'tariff_plan_id' => $entry->tariff_plan_id,
            'tariffPlan' => $entry->tariffPlan ? [
                'id' => $entry->tariffPlan->id,
                'name' => $entry->tariffPlan->nom,
                'type' => $entry->tariffPlan->type,
                'payment_required' => (bool) $entry->tariffPlan->paiement_obligatoire,
            ] : null,

            'items' => $entry->items?->map(function ($it) {
                return [
                    'id' => $it->id,

                    // via accessor
                    'daily_register_entry_id' => $it->daily_register_entry_id,
                    'service_id' => $it->service_id,
                    'tariff_item_id' => $it->tariff_item_id,

                    'qty' => $it->qty,
                    'unit_price' => $it->unit_price,
                    'line_total' => $it->line_total,
                    'notes' => $it->notes,

                    // debug optionnel
                    'billable_service_id' => $it->billable_service_id ?? $it->service_id,
                    'quantite' => $it->quantite ?? $it->qty,
                    'prix_unitaire' => $it->prix_unitaire ?? $it->unit_price,
                    'remarques' => $it->remarques ?? $it->notes,

                    'service' => $it->billableService ? [
                        'id' => $it->billableService->id,
                        'code' => $it->billableService->code,
                        'name' => $it->billableService->libelle,
                        'category' => $it->billableService->categorie,
                        'is_active' => $it->billableService->active,
                        'requires_appointment' => (bool) $it->billableService->rendez_vous_obligatoire,
                        'requires_doctor' => (bool) $it->billableService->necessite_medecin,
                        'pay_before_service' => (bool) $it->billableService->paiement_obligatoire_avant_prestation,
                    ] : null,
                ];
            })->values(),

            'created_at' => optional($entry->created_at)->toISOString(),
            'updated_at' => optional($entry->updated_at)->toISOString(),
        ];
    }

    public function index(Request $request)
    {
        $query = DailyRegisterEntry::query()
            ->with($this->baseWith())
            ->orderByDesc('id');

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->filled('status')) {
            $query->where('statut', $request->status);
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->q);

            $query->where(function ($sub) use ($q) {
                $sub->where('motif', 'like', "%{$q}%")
                    ->orWhereHas('patient.personne', function ($p) use ($q) {
                        $p->where('nom', 'like', "%{$q}%")
                          ->orWhere('prenom', 'like', "%{$q}%");
                    })
                    ->orWhereHas('patient', function ($p) use ($q) {
                        $p->where('nip', 'like', "%{$q}%");
                    });
            });
        }

        $page = $query->paginate(10);
        $page->getCollection()->transform(fn ($e) => $this->formatEntry($e));

        return response()->json([
            'message' => 'Registre journalier',
            'data' => $page,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:t_patients,id'],
            'arrival_at' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'is_emergency' => ['nullable', 'boolean'],

            'tariff_plan_id' => ['required', 'integer', 'exists:tariff_plans,id'],

            'items' => ['nullable', 'array', 'min:1'],
            'items.*.tariff_item_id' => ['nullable', 'integer', 'exists:tariff_items,id'],
            'items.*.service_id' => ['nullable', 'integer', 'exists:billable_services,id'],
            'items.*.qty' => ['nullable', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        foreach (($data['items'] ?? []) as $idx => $it) {
            if (empty($it['tariff_item_id']) && empty($it['service_id'])) {
                return response()->json([
                    'message' => "items[$idx] doit contenir tariff_item_id ou service_id"
                ], 422);
            }
        }

        $userId = auth()->id();
        if (!$userId) {
            return response()->json(['message' => 'Utilisateur non authentifié.'], 401);
        }

        try {
            $result = DB::transaction(function () use ($data, $userId) {

                // 1) Create entry
                $entry = DailyRegisterEntry::query()->create([
                    'id_patient' => $data['patient_id'],
                    'id_agent_createur' => $userId,
                    'date_arrivee' => $data['arrival_at'] ?? now(),
                    'motif' => $data['reason'] ?? null,
                    'urgence' => $data['is_emergency'] ?? false,

                    // provisoire (sync après)
                    'statut' => DailyRegisterEntry::STATUS_AWAITING_PAYMENT,

                    'tariff_plan_id' => (int) $data['tariff_plan_id'],
                ]);

                // 2) Add items
                foreach (($data['items'] ?? []) as $it) {
                    $this->entryItems->addItemCompat(
                        entryId: $entry->id,
                        tariffItemId: $it['tariff_item_id'] ?? null,
                        billableServiceId: $it['service_id'] ?? null,
                        qty: (int) ($it['qty'] ?? 1),
                        notes: $it['notes'] ?? null
                    );
                }

                // 3) Sync initial status
                $entry->syncBillingAndStatus();

                // 4) ✅ AUTO: create BillingRequest + FactureOfficielle (numero_global)
                $billing = null;
                $facture = null;

                // Payment required? then build BR (builder returns null if not required)
                $billing = $this->billingBuilder->generateBillingRequest($entry->id);

                if ($billing) {
                    // Create/return facture officielle (numero_global)
                    // ⚠️ throws if montant_total <= 0 (your FinanceBridgeService rule)
                    if ((float) ($billing->montant_total ?? 0) > 0) {
                        $facture = $this->financeBridge->ensureFactureOfficielleForBillingRequest($billing);
                    }

                    // re-sync (paid/partial/unpaid effects)
                    $entry->syncBillingAndStatus();
                }

                // fresh
                $entry = $entry->fresh($this->baseWith());

                return [
                    'entry' => $entry,
                    'billing' => $billing ? $billing->fresh(['items', 'patient', 'requestedByAgent']) : null,
                    'facture' => $facture ? $facture->fresh() : null,
                ];
            });

            /** @var DailyRegisterEntry $entry */
            $entry = $result['entry'];
            $facture = $result['facture'];

            return response()->json([
                'message' => 'Entrée registre créée',
                'data' => array_merge(
                    $this->formatEntry($entry),
                    [
                        // ✅ bonus front: directement exploitable
                        'numero_global' => $facture?->numero_global,
                        'facture_officielle' => $facture ? [
                            'numero_global' => $facture->numero_global,
                            'date_emission' => $facture->date_emission,
                            'total_ttc' => $facture->total_ttc,
                            'statut' => $facture->statut,
                            'statut_paiement' => $facture->statut_paiement,
                        ] : null,
                    ]
                ),
            ], 201);

        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(int $id)
    {
        $entry = DailyRegisterEntry::query()
            ->with($this->baseWith())
            ->findOrFail($id);

        return response()->json([
            'message' => 'Détails registre',
            'data' => $this->formatEntry($entry),
        ]);
    }

    public function patientsToday(Request $request)
    {
        $day = $request->query('date')
            ? Carbon::parse($request->query('date'))->toDateString()
            : now()->toDateString();

        $query = DailyRegisterEntry::query()
            ->with($this->baseWith())
            ->whereDate('created_at', $day)
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('statut', $request->query('status'));
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->q);

            $query->where(function ($sub) use ($q) {
                $sub->where('motif', 'like', "%{$q}%")
                    ->orWhereHas('patient.personne', function ($p) use ($q) {
                        $p->where('nom', 'like', "%{$q}%")
                          ->orWhere('prenom', 'like', "%{$q}%");
                    })
                    ->orWhereHas('patient', function ($p) use ($q) {
                        $p->where('nip', 'like', "%{$q}%");
                    });
            });
        }

        $entries = $query->get()->map(fn ($e) => $this->formatEntry($e))->values();

        return response()->json([
            'message' => 'Patients du jour',
            'data' => [
                'date' => $day,
                'total' => $entries->count(),
                'entries' => $entries,
            ],
        ]);
    }
}