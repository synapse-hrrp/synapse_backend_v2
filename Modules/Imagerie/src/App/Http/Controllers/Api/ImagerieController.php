<?php

namespace Modules\Imagerie\App\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Imagerie\App\Models\Imagerie;
use Modules\Imagerie\App\Models\ImagerieRequest;
use Modules\Imagerie\App\Models\ImagerieResultat;

class ImagerieController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Imagerie::class);

        $request->validate([
            'imagerie_request_id' => ['required', 'integer', 'exists:imagerie_requests,id'],
            'agent_id'            => ['nullable', 'integer', 'exists:t_agents,id'],
            'appareil'            => ['nullable', 'string', 'max:100'],
            'salle'               => ['nullable', 'string', 'max:50'],
            'produit_contraste'   => ['boolean'],
            'type_contraste'      => ['nullable', 'string', 'max:100'],
        ]);

        $imagerieRequest = ImagerieRequest::find($request->imagerie_request_id);

        if (!$imagerieRequest->estAutorise()) {
            return response()->json([
                'success' => false,
                'message' => 'La demande n\'est pas encore autorisée.',
            ], 422);
        }

        $imagerie = Imagerie::create([
            'imagerie_request_id' => $request->imagerie_request_id,
            'agent_id'            => $request->agent_id,
            'appareil'            => $request->appareil,
            'salle'               => $request->salle,
            'produit_contraste'   => $request->produit_contraste ?? false,
            'type_contraste'      => $request->type_contraste,
            'status'              => 'en_cours',
            'started_at'          => now(),
        ]);

        $imagerieRequest->update(['status' => 'in_progress']);

        return response()->json([
            'success' => true,
            'message' => 'Examen d\'imagerie démarré avec succès.',
            'data'    => $imagerie,
        ], 201);
    }

    public function terminer(Request $request, Imagerie $imagerie): JsonResponse
    {
        $this->authorize('terminer', $imagerie);

        $request->validate([
            'compte_rendu'      => ['required', 'string'],
            'conclusion'        => ['nullable', 'string'],
            'recommandations'   => ['nullable', 'string'],
            'chemin_images'     => ['nullable', 'string', 'max:500'],
            'format_images'     => ['nullable', 'in:dicom,jpeg,png,pdf,autre'],
            'incidents'         => ['boolean'],
            'details_incidents' => ['nullable', 'string'],
            'observations'      => ['nullable', 'string'],
        ]);

        ImagerieResultat::create([
            'imagerie_id'     => $imagerie->id,
            'compte_rendu'    => $request->compte_rendu,
            'conclusion'      => $request->conclusion,
            'recommandations' => $request->recommandations,
            'chemin_images'   => $request->chemin_images,
            'format_images'   => $request->format_images,
            'agent_id'        => $imagerie->agent_id,
            'status'          => 'valide',
            'valide_le'       => now(),
        ]);

        $imagerie->update([
            'incidents'         => $request->incidents ?? false,
            'details_incidents' => $request->details_incidents,
            'observations'      => $request->observations,
            'status'            => 'termine',
            'finished_at'       => now(),
            'validated_at'      => now(),
        ]);

        $imagerie->request->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Examen d\'imagerie terminé avec succès.',
            'data'    => $imagerie->load('resultat'),
        ]);
    }
}