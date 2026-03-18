<?php

namespace Modules\Soins\App\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Soins\App\Models\Hospitalisation;
use Modules\Soins\App\Models\HospitalisationRequest;

class HospitalisationController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Hospitalisation::class);

        $q = Hospitalisation::query()->with(['request']);

        // uniquement terminées
        $q->where('status', 'termine');

        // filtre patient_id via request relation
        if ($request->filled('patient_id')) {
            $q->whereHas('request', function ($sub) use ($request) {
                $sub->where('patient_id', (int) $request->patient_id);
            });
        }

        // filtres dates sur sortie_at
        if ($request->filled('date_from')) {
            $q->whereDate('sortie_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $q->whereDate('sortie_at', '<=', $request->date_to);
        }

        // recherche texte
        if ($request->filled('q')) {
            $search = trim((string) $request->q);
            $q->where(function ($w) use ($search) {
                $w->where('service', 'like', "%{$search}%")
                ->orWhere('chambre', 'like', "%{$search}%")
                ->orWhere('lit', 'like', "%{$search}%")
                ->orWhere('diagnostic_admission', 'like', "%{$search}%")
                ->orWhere('diagnostic_sortie', 'like', "%{$search}%")
                ->orWhere('code_cim10', 'like', "%{$search}%");
            });
        }

        $items = $q->orderByDesc('sortie_at')->get();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function show(Hospitalisation $hospitalisation): JsonResponse
    {
        $this->authorize('view', $hospitalisation);

        return response()->json([
            'success' => true,
            'data' => $hospitalisation->load(['request']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Hospitalisation::class);

        $request->validate([
            'hospitalisation_request_id' => ['required', 'integer', 'exists:hospitalisation_requests,id'],
            'agent_id'                   => ['nullable', 'integer', 'exists:t_agents,id'],
            'service'                    => ['nullable', 'string', 'max:100'],
            'chambre'                    => ['nullable', 'string', 'max:50'],
            'lit'                        => ['nullable', 'string', 'max:50'],
            'diagnostic_admission'       => ['nullable', 'string'],
            'code_cim10'                 => ['nullable', 'string', 'max:20'],
        ]);

        $hospRequest = HospitalisationRequest::find($request->hospitalisation_request_id);

        if (!$hospRequest->estAutorise()) {
            return response()->json([
                'success' => false,
                'message' => 'La demande n\'est pas encore autorisée.',
            ], 422);
        }

        $hospitalisation = Hospitalisation::create([
            'hospitalisation_request_id' => $request->hospitalisation_request_id,
            'agent_id'                   => $request->agent_id,
            'service'                    => $request->service,
            'chambre'                    => $request->chambre,
            'lit'                        => $request->lit,
            'diagnostic_admission'       => $request->diagnostic_admission,
            'code_cim10'                 => $request->code_cim10,
            'status'                     => 'en_cours',
            'admission_at'               => now(),
        ]);

        $hospRequest->update(['status' => 'in_progress']);

        return response()->json([
            'success' => true,
            'message' => 'Hospitalisation démarrée avec succès.',
            'data'    => $hospitalisation,
        ], 201);
    }

    public function sortie(Request $request, Hospitalisation $hospitalisation): JsonResponse
    {
        $this->authorize('terminer', $hospitalisation);

        $request->validate([
            'diagnostic_sortie' => ['nullable', 'string'],
            'mode_sortie'       => ['required', 'in:guerison,amelioration,stationnaire,transfert,contre_avis,decede'],
            'observations'      => ['nullable', 'string'],
        ]);

        $hospitalisation->update([
            'diagnostic_sortie' => $request->diagnostic_sortie,
            'mode_sortie'       => $request->mode_sortie,
            'observations'      => $request->observations,
            'status'            => 'termine',
            'sortie_at'         => now(),
        ]);

        $hospitalisation->request->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sortie enregistrée avec succès.',
            'data'    => $hospitalisation,
        ]);
    }
}