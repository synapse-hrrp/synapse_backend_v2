<?php

namespace Modules\Laboratoire\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Laboratoire\App\Models\Examen;
use Modules\Laboratoire\App\Models\ExamenRequest;
use Modules\Reactifs\App\Events\ExamenTermine; 

class ExamenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        // $this->authorize('create', Examen::class);

        $request->validate([
            'examen_request_id' => ['required', 'integer', 'exists:examen_requests,id'],
            'agent_id'          => ['nullable', 'integer', 'exists:t_agents,id'],
        ]);

        $examenRequest = ExamenRequest::find($request->examen_request_id);

        if (!$examenRequest->estAutorise()) {
            return response()->json([
                'success' => false,
                'message' => 'La demande n\'est pas encore autorisée.',
            ], 422);
        }

        $examen = Examen::create([
            'examen_request_id' => $request->examen_request_id,
            'agent_id'          => $request->agent_id,
            'status'            => 'en_cours',
            'started_at'        => now(),
        ]);

        $examenRequest->update(['status' => 'in_progress']);

        return response()->json([
            'success' => true,
            'message' => 'Examen démarré avec succès.',
            'data'    => $examen,
        ], 201);
    }

    public function terminer(Request $request, Examen $examen): JsonResponse
    {
        // $this->authorize('terminer', $examen);

        $request->validate([
            'observations' => ['nullable', 'string'],
            'resultats'    => ['nullable', 'array'],
        ]);

        $examen->update([
            'observations' => $request->observations,
            'status'       => 'termine',
            'finished_at'  => now(),
        ]);

        $examen->request->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        // ── Déduction automatique du stock des réactifs ──────────────
        event(new ExamenTermine(
            examenId:     $examen->id,
            examenTypeId: $examen->request->examen_type_id,
            userId:       auth()->id(),
        ));

        return response()->json([
            'success' => true,
            'message' => 'Examen terminé avec succès.',
            'data'    => $examen,
        ]);
    }
}