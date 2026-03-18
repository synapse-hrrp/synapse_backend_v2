<?php

namespace Modules\Soins\App\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Soins\App\Models\Pansement;
use Modules\Soins\App\Models\PansementRequest;

class PansementController extends Controller
{
    use AuthorizesRequests;

    /**
     * POST /api/v1/soins/pansements
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Pansement::class);

        $request->validate([
            'pansement_request_id' => ['required', 'integer', 'exists:pansement_requests,id'],
            'agent_id'             => ['nullable', 'integer', 'exists:t_agents,id'],
        ]);

        $pansementRequest = PansementRequest::find($request->pansement_request_id);

        if (!$pansementRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Demande de pansement introuvable.',
            ], 404);
        }

        if (!$pansementRequest->estAutorise()) {
            return response()->json([
                'success' => false,
                'message' => 'La demande n\'est pas encore autorisée.',
            ], 422);
        }

        $pansement = Pansement::create([
            'pansement_request_id' => $request->pansement_request_id,
            'agent_id'             => $request->agent_id,
            'status'               => 'en_cours',
            'started_at'           => now(),
        ]);

        $pansementRequest->update([
            'status' => 'in_progress',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pansement démarré avec succès.',
            'data'    => $pansement,
        ], 201);
    }

    /**
     * PUT /api/v1/soins/pansements/{pansement}/terminer
     */
    public function terminer(Request $request, Pansement $pansement): JsonResponse
    {
        $this->authorize('terminer', $pansement);

        $request->validate([
            'observations' => ['nullable', 'string'],
        ]);

        $pansement->update([
            'observations' => $request->observations,
            'status'       => 'termine',
            'finished_at'  => now(),
        ]);

        $pansement->request->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pansement terminé avec succès.',
            'data'    => $pansement->load(['request']),
        ]);
    }

    /**
     * GET /api/v1/soins/pansements
     * Historique des pansements terminés
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Pansement::class);

        $query = Pansement::query()
            ->with(['request'])
            ->where('status', 'termine');

        if ($request->filled('patient_id')) {
            $query->whereHas('request', function ($sub) use ($request) {
                $sub->where('patient_id', (int) $request->patient_id);
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('finished_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('finished_at', '<=', $request->date_to);
        }

        if ($request->filled('q')) {
            $search = trim((string) $request->q);

            $query->where(function ($q) use ($search) {
                $q->where('observations', 'like', "%{$search}%")
                  ->orWhereHas('request', function ($sub) use ($search) {
                      $sub->where('type_pansement', 'like', "%{$search}%")
                          ->orWhere('zone_anatomique', 'like', "%{$search}%")
                          ->orWhere('notes', 'like', "%{$search}%");
                  });
            });
        }

        $items = $query
            ->orderByDesc('finished_at')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $items,
        ]);
    }

    /**
     * GET /api/v1/soins/pansements/{pansement}
     * Détail d'un pansement
     */
    public function show(Pansement $pansement): JsonResponse
    {
        $this->authorize('view', $pansement);

        return response()->json([
            'success' => true,
            'data'    => $pansement->load(['request']),
        ]);
    }
}