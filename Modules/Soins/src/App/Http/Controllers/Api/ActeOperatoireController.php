<?php

namespace Modules\Soins\App\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Soins\App\Models\ActeOperatoire;
use Modules\Soins\App\Models\ActeOperatoireRequest;

class ActeOperatoireController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ActeOperatoire::class);

        $q = ActeOperatoire::query()->with(['request']);
        $q->where('status', 'termine');

        if ($request->filled('patient_id')) {
            $q->whereHas('request', function ($sub) use ($request) {
                $sub->where('patient_id', (int) $request->patient_id);
            });
        }

        if ($request->filled('date_from')) {
            $q->whereDate('fin_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $q->whereDate('fin_at', '<=', $request->date_to);
        }

        if ($request->filled('q')) {
            $search = trim((string) $request->q);
            $q->where(function ($w) use ($search) {
                $w->where('type_operation', 'like', "%{$search}%")
                ->orWhere('compte_rendu', 'like', "%{$search}%")
                ->orWhere('suites_operatoires', 'like', "%{$search}%");
            });
        }

        $items = $q->orderByDesc('fin_at')->get();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function show(ActeOperatoire $acte): JsonResponse
    {
        $this->authorize('view', $acte);

        return response()->json([
            'success' => true,
            'data' => $acte->load(['request']),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', ActeOperatoire::class);

        $request->validate([
            'acte_operatoire_request_id' => ['required', 'integer', 'exists:acte_operatoire_requests,id'],
            'agent_id'                   => ['nullable', 'integer', 'exists:t_agents,id'],
            'type_anesthesie'            => ['nullable', 'in:generale,locoregionale,locale,sedation'],
            'salle'                      => ['nullable', 'string', 'max:50'],
        ]);

        $acteRequest = ActeOperatoireRequest::find($request->acte_operatoire_request_id);

        if (!$acteRequest->estAutorise()) {
            return response()->json([
                'success' => false,
                'message' => 'La demande n\'est pas encore autorisée.',
            ], 422);
        }

        $acte = ActeOperatoire::create([
            'acte_operatoire_request_id' => $request->acte_operatoire_request_id,
            'agent_id'                   => $request->agent_id,
            'type_operation'             => $acteRequest->type_operation,
            'type_anesthesie'            => $request->type_anesthesie,
            'salle'                      => $request->salle,
            'status'                     => 'en_cours',
            'debut_at'                   => now(),
        ]);

        $acteRequest->update(['status' => 'in_progress']);

        return response()->json([
            'success' => true,
            'message' => 'Acte opératoire démarré avec succès.',
            'data'    => $acte,
        ], 201);
    }

    public function terminer(Request $request, ActeOperatoire $acte): JsonResponse
    {
        $this->authorize('terminer', $acte);

        $request->validate([
            'compte_rendu'          => ['required', 'string'],
            'incidents'             => ['nullable', 'string'],
            'suites_operatoires'    => ['nullable', 'string'],
            'complications'         => ['boolean'],
            'details_complications' => ['nullable', 'string'],
        ]);

        $acte->update([
            'compte_rendu'          => $request->compte_rendu,
            'incidents'             => $request->incidents,
            'suites_operatoires'    => $request->suites_operatoires,
            'complications'         => $request->complications ?? false,
            'details_complications' => $request->details_complications,
            'status'                => 'termine',
            'fin_at'                => now(),
        ]);

        $acte->request->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Acte opératoire terminé avec succès.',
            'data'    => $acte,
        ]);
    }
}