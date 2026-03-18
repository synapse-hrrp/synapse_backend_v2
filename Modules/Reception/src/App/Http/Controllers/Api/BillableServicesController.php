<?php

namespace Modules\Reception\App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Reception\App\Models\BillableService;

class BillableServicesController extends Controller
{
    private function format(BillableService $s): array
    {
        return [
            'id' => $s->id,
            'code' => $s->code,
            'name' => $s->name,               // accessor -> libelle
            'libelle' => $s->libelle,
            'category' => $s->category,       // accessor -> categorie
            'categorie' => $s->categorie,
            'description' => $s->description,

            'is_active' => (bool) $s->is_active, // accessor -> active
            'active' => (bool) $s->active,

            // règles métier
            'requires_appointment' => (bool) $s->requires_appointment,
            'requires_doctor' => (bool) $s->requires_doctor,
            'pay_before_service' => (bool) $s->payment_required_before_service,

            'created_at' => optional($s->created_at)->toISOString(),
            'updated_at' => optional($s->updated_at)->toISOString(),
        ];
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $categorie = $request->query('categorie');
        $active = $request->query('active'); // "1" | "0" | null

        $query = BillableService::query()->orderByDesc('id');

        if ($q !== '') {
            $like = '%' . preg_replace('/\s+/', '%', $q) . '%';
            $query->where(function ($sub) use ($like) {
                $sub->where('code', 'like', $like)
                    ->orWhere('libelle', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }

        if (!empty($categorie)) {
            $query->where('categorie', $categorie);
        }

        if ($active !== null && $active !== '') {
            $query->where('active', (bool) ((int) $active));
        }

        $page = $query->paginate(10);
        $page->getCollection()->transform(fn ($s) => $this->format($s));

        return response()->json([
            'message' => 'Services facturables',
            'data' => $page,
        ]);
    }

    public function show(BillableService $service)
    {
        return response()->json([
            'message' => 'Détails service',
            'data' => $this->format($service),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:billable_services,code'],
            'libelle' => ['required', 'string', 'max:255'],
            'categorie' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],

            'active' => ['nullable', 'boolean'],

            'rendez_vous_obligatoire' => ['nullable', 'boolean'],
            'necessite_medecin' => ['nullable', 'boolean'],
            'paiement_obligatoire_avant_prestation' => ['nullable', 'boolean'],
        ]);

        $service = BillableService::query()->create([
            'code' => $data['code'],
            'libelle' => $data['libelle'],
            'categorie' => $data['categorie'],
            'description' => $data['description'] ?? null,
            'active' => $data['active'] ?? true,

            'rendez_vous_obligatoire' => $data['rendez_vous_obligatoire'] ?? false,
            'necessite_medecin' => $data['necessite_medecin'] ?? false,
            'paiement_obligatoire_avant_prestation' => $data['paiement_obligatoire_avant_prestation'] ?? false,
        ]);

        return response()->json([
            'message' => 'Service créé',
            'data' => $this->format($service),
        ], 201);
    }

    public function update(Request $request, BillableService $service)
    {
        $data = $request->validate([
            'code' => ['sometimes', 'string', 'max:50', Rule::unique('billable_services', 'code')->ignore($service->id)],
            'libelle' => ['sometimes', 'string', 'max:255'],
            'categorie' => ['sometimes', 'string', 'max:50'],
            'description' => ['sometimes', 'nullable', 'string'],

            'active' => ['sometimes', 'boolean'],

            'rendez_vous_obligatoire' => ['sometimes', 'boolean'],
            'necessite_medecin' => ['sometimes', 'boolean'],
            'paiement_obligatoire_avant_prestation' => ['sometimes', 'boolean'],
        ]);

        $service->update($data);

        return response()->json([
            'message' => 'Service mis à jour',
            'data' => $this->format($service->fresh()),
        ]);
    }

    /**
     * ✅ upsert: si code existe -> update, sinon -> create
     * Route: POST /v1/reception/services/upsert
     */
    public function upsert(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'libelle' => ['required', 'string', 'max:255'],
            'categorie' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],

            'active' => ['nullable', 'boolean'],

            'rendez_vous_obligatoire' => ['nullable', 'boolean'],
            'necessite_medecin' => ['nullable', 'boolean'],
            'paiement_obligatoire_avant_prestation' => ['nullable', 'boolean'],
        ]);

        $service = DB::transaction(function () use ($data) {
            $existing = BillableService::query()->where('code', $data['code'])->first();

            if ($existing) {
                $existing->update([
                    'libelle' => $data['libelle'],
                    'categorie' => $data['categorie'],
                    'description' => $data['description'] ?? $existing->description,
                    'active' => $data['active'] ?? $existing->active,
                    'rendez_vous_obligatoire' => $data['rendez_vous_obligatoire'] ?? $existing->rendez_vous_obligatoire,
                    'necessite_medecin' => $data['necessite_medecin'] ?? $existing->necessite_medecin,
                    'paiement_obligatoire_avant_prestation' => $data['paiement_obligatoire_avant_prestation'] ?? $existing->paiement_obligatoire_avant_prestation,
                ]);
                return $existing->fresh();
            }

            return BillableService::query()->create([
                'code' => $data['code'],
                'libelle' => $data['libelle'],
                'categorie' => $data['categorie'],
                'description' => $data['description'] ?? null,
                'active' => $data['active'] ?? true,

                'rendez_vous_obligatoire' => $data['rendez_vous_obligatoire'] ?? false,
                'necessite_medecin' => $data['necessite_medecin'] ?? false,
                'paiement_obligatoire_avant_prestation' => $data['paiement_obligatoire_avant_prestation'] ?? false,
            ]);
        });

        return response()->json([
            'message' => 'Upsert service OK',
            'data' => $this->format($service),
        ], 201);
    }

    public function destroy(BillableService $service)
    {
        // Soft delete (BillableService utilise SoftDeletes)
        $service->delete();

        return response()->json([
            'message' => 'Service supprimé',
            'data' => ['id' => $service->id],
        ]);
    }
}