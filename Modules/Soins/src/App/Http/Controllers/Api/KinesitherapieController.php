<?php

namespace Modules\Soins\App\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Soins\App\Models\Kinesitherapie;
use Modules\Soins\App\Models\KinesitherapieRequest;

class KinesitherapieController extends Controller
{
    use AuthorizesRequests;

    /**
     * GET /api/v1/soins/kinesitherapies
     * Historique des kinés terminées
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Kinesitherapie::class);

        $q = Kinesitherapie::query()->with(['request']);

        // uniquement les séances terminées
        $q->where('status', 'termine');

        // filtre par patient_id via la demande liée
        if ($request->filled('patient_id')) {
            $q->whereHas('request', function ($sub) use ($request) {
                $sub->where('patient_id', (int) $request->patient_id);
            });
        }

        // filtre dates
        if ($request->filled('date_from')) {
            $q->whereDate('finished_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $q->whereDate('finished_at', '<=', $request->date_to);
        }

        // recherche texte
        if ($request->filled('q')) {
            $search = trim((string) $request->q);

            $q->where(function ($w) use ($search) {
                $w->where('observations', 'like', "%{$search}%")
                  ->orWhereHas('request', function ($sub) use ($search) {
                      $sub->where('type_reeducation', 'like', "%{$search}%")
                          ->orWhere('motif', 'like', "%{$search}%")
                          ->orWhere('notes', 'like', "%{$search}%");
                  });
            });
        }

        $items = $q->orderByDesc('finished_at')->get();

        return response()->json([
            'success' => true,
            'data'    => $items,
        ]);
    }

    /**
     * GET /api/v1/soins/kinesitherapies/{kinesitherapie}
     * Détail d'une kiné
     */
    public function show(Kinesitherapie $kinesitherapie): JsonResponse
    {
        $this->authorize('view', $kinesitherapie);

        return response()->json([
            'success' => true,
            'data'    => $kinesitherapie->load(['request']),
        ]);
    }

    /**
     * POST /api/v1/soins/kinesitherapies
     * Démarrer une séance
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Kinesitherapie::class);

        $validated = $request->validate([
            'kinesitherapie_request_id' => ['required', 'integer', 'exists:kinesitherapie_requests,id'],
            'agent_id'                  => ['nullable', 'integer', 'exists:t_agents,id'],
        ]);

        $kineRequest = KinesitherapieRequest::find($validated['kinesitherapie_request_id']);

        if (!$kineRequest || !$kineRequest->estAutorise()) {
            return response()->json([
                'success' => false,
                'message' => 'La demande n\'est pas encore autorisée.',
            ], 422);
        }

        $kinesitherapie = Kinesitherapie::create([
            'kinesitherapie_request_id' => $validated['kinesitherapie_request_id'],
            'agent_id'                  => $validated['agent_id'] ?? null,
            'status'                    => 'en_cours',
            'started_at'                => now(),
        ]);

        $kineRequest->update([
            'status' => 'in_progress',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kinésithérapie démarrée avec succès.',
            'data'    => $kinesitherapie,
        ], 201);
    }

    /**
     * PUT /api/v1/soins/kinesitherapies/{kinesitherapie}/terminer
     * Terminer une séance
     */
    public function terminer(Request $request, Kinesitherapie $kinesitherapie): JsonResponse
    {
        $this->authorize('terminer', $kinesitherapie);

        $validated = $request->validate([
            'observations' => ['nullable', 'string'],
        ]);

        $kinesitherapie->update([
            'observations' => $validated['observations'] ?? null,
            'status'       => 'termine',
            'finished_at'  => now(),
        ]);

        $kinesitherapie->request->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kinésithérapie terminée avec succès.',
            'data'    => $kinesitherapie->load(['request']),
        ]);
    }
}