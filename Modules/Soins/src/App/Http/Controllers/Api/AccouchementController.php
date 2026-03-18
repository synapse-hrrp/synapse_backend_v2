<?php

namespace Modules\Soins\App\Http\Controllers\Api;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Soins\App\Models\Accouchement;
use Modules\Soins\App\Models\AccouchementRequest;

class AccouchementController extends Controller
{
    use AuthorizesRequests;
    
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Accouchement::class);

        $q = Accouchement::query()->with(['request']);

        // ✅ seulement terminés
        $q->where('status', 'termine');

        // ✅ filtre patient_id via request relation
        if ($request->filled('patient_id')) {
            $q->whereHas('request', function ($sub) use ($request) {
                $sub->where('patient_id', (int) $request->patient_id);
            });
        }

        // ✅ filtre dates (sur finished_at)
        if ($request->filled('date_from')) {
            $q->whereDate('finished_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $q->whereDate('finished_at', '<=', $request->date_to);
        }

        // optionnel: recherche texte
        if ($request->filled('q')) {
            $search = trim((string) $request->q);
            $q->where(function ($w) use ($search) {
                $w->where('type_accouchement', 'like', "%{$search}%")
                ->orWhere('observations', 'like', "%{$search}%");
            });
        }

        $items = $q->orderByDesc('finished_at')->get();

        return response()->json(['success' => true, 'data' => $items]);
    }

    public function show(Accouchement $accouchement): JsonResponse
    {
        $this->authorize('view', $accouchement);

        return response()->json([
            'success' => true,
            'data' => $accouchement->load(['request', 'declarationNaissance']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Accouchement::class);

        $request->validate([
            'accouchement_request_id' => ['required', 'integer', 'exists:accouchement_requests,id'],
            'agent_id'                => ['nullable', 'integer', 'exists:t_agents,id'],
        ]);

        $accRequest = AccouchementRequest::find($request->accouchement_request_id);

        if (!$accRequest->estAutorise()) {
            return response()->json([
                'success' => false,
                'message' => 'La demande n\'est pas encore autorisée.',
            ], 422);
        }

        $accouchement = Accouchement::create([
            'accouchement_request_id' => $request->accouchement_request_id,
            'agent_id'                => $request->agent_id,
            'status'                  => 'en_cours',
            'debut_travail_at'        => now(),
        ]);

        $accRequest->update(['status' => 'in_progress']);

        return response()->json([
            'success' => true,
            'message' => 'Accouchement démarré avec succès.',
            'data'    => $accouchement,
        ], 201);
    }

    public function terminer(Request $request, Accouchement $accouchement): JsonResponse
    {
        $this->authorize('terminer', $accouchement);

        $request->validate([
            'type_accouchement'     => ['required', 'in:voie_basse,cesarienne,voie_basse_instrumentale'],
            'nombre_nouveau_nes'    => ['required', 'integer', 'min:1'],
            'poids_naissance'       => ['nullable', 'integer'],
            'apgar_1min'            => ['nullable', 'integer', 'min:0', 'max:10'],
            'apgar_5min'            => ['nullable', 'integer', 'min:0', 'max:10'],
            'sexe_nouveau_ne'       => ['nullable', 'in:masculin,feminin,indetermine'],
            'terme_semaines'        => ['nullable', 'integer'],
            'complications'         => ['boolean'],
            'details_complications' => ['nullable', 'string'],
            'observations'          => ['nullable', 'string'],
        ]);

        $accouchement->update([
            'type_accouchement'     => $request->type_accouchement,
            'nombre_nouveau_nes'    => $request->nombre_nouveau_nes,
            'poids_naissance'       => $request->poids_naissance,
            'apgar_1min'            => $request->apgar_1min,
            'apgar_5min'            => $request->apgar_5min,
            'sexe_nouveau_ne'       => $request->sexe_nouveau_ne,
            'terme_semaines'        => $request->terme_semaines,
            'complications'         => $request->complications ?? false,
            'details_complications' => $request->details_complications,
            'observations'          => $request->observations,
            'status'                => 'termine',
            'naissance_at'          => now(),
            'finished_at'           => now(),
        ]);

        $accouchement->request->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Accouchement terminé avec succès.',
            'data'    => $accouchement,
        ]);
    }
}