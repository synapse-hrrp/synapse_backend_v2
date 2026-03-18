<?php

namespace Modules\Soins\App\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Soins\App\Models\Consultation;
use Modules\Soins\App\Models\ConsultationRequest;
use Modules\Soins\App\Models\Constante;

class ConsultationController extends Controller
{
    use AuthorizesRequests;

        public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Consultation::class);

        $q = Consultation::query()->with(['request', 'constantes']);

        // ✅ uniquement terminées (historique)
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
                $w->where('diagnostic', 'like', "%{$search}%")
                ->orWhere('code_cim10', 'like', "%{$search}%");
            });
        }

        $items = $q->orderByDesc('finished_at')->get();

        return response()->json(['success' => true, 'data' => $items]);
    }

    public function show(Consultation $consultation): JsonResponse
    {
        $this->authorize('view', $consultation);

        return response()->json([
            'success' => true,
            'data' => $consultation->load(['request', 'constantes', 'prescriptions.lignes']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Consultation::class);

        $request->validate([
            'consultation_request_id' => ['required', 'integer', 'exists:consultation_requests,id'],
            'agent_id'                => ['nullable', 'integer', 'exists:t_agents,id'],
        ]);

        $consultRequest = ConsultationRequest::find($request->consultation_request_id);

        if (!$consultRequest->estAutorise()) {
            return response()->json([
                'success' => false,
                'message' => 'La demande n\'est pas encore autorisée.',
            ], 422);
        }

        $consultation = Consultation::create([
            'consultation_request_id' => $request->consultation_request_id,
            'agent_id'                => $request->agent_id,
            'status'                  => 'en_cours',
            'started_at'              => now(),
        ]);

        $consultRequest->update(['status' => 'in_progress']);

        return response()->json([
            'success' => true,
            'message' => 'Consultation démarrée avec succès.',
            'data'    => $consultation,
        ], 201);
    }

    public function terminer(Request $request, Consultation $consultation): JsonResponse
    {
        $this->authorize('terminer', $consultation);

        $request->validate([
            'anamnese'        => ['nullable', 'string'],
            'examen_clinique' => ['nullable', 'string'],
            'diagnostic'      => ['nullable', 'string', 'max:255'],
            'code_cim10'      => ['nullable', 'string', 'max:20'],
            'conclusion'      => ['nullable', 'string'],
            'constantes'                         => ['nullable', 'array'],
            'constantes.tension_systolique'      => ['nullable', 'string'],
            'constantes.tension_diastolique'     => ['nullable', 'string'],
            'constantes.frequence_cardiaque'     => ['nullable', 'integer'],
            'constantes.temperature'             => ['nullable', 'numeric'],
            'constantes.poids'                   => ['nullable', 'numeric'],
            'constantes.taille'                  => ['nullable', 'numeric'],
            'constantes.saturation_o2'           => ['nullable', 'integer'],
        ]);

        if ($request->has('constantes')) {
            Constante::create(array_merge(
                $request->constantes,
                [
                    'consultation_id' => $consultation->id,
                    'agent_id'        => $consultation->agent_id,
                    'pris_le'         => now(),
                ]
            ));
        }

        $consultation->update([
            'anamnese'        => $request->anamnese,
            'examen_clinique' => $request->examen_clinique,
            'diagnostic'      => $request->diagnostic,
            'code_cim10'      => $request->code_cim10,
            'conclusion'      => $request->conclusion,
            'status'          => 'termine',
            'finished_at'     => now(),
        ]);

        $consultation->request->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Consultation terminée avec succès.',
            'data'    => $consultation->load(['constantes', 'prescriptions']),
        ]);
    }
}