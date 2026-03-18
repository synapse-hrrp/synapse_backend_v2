<?php

namespace Modules\Laboratoire\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Laboratoire\App\Models\ExamenRequest;

class ExamenRequestController extends Controller
{
    /**
     * GET /api/v1/laboratoire/examen-requests
     */
    public function index(Request $request): JsonResponse
    {
        $query = ExamenRequest::with([
            'patient.personne',
            'examenType',
        ]);

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('examen_type_id')) {
            $query->where('examen_type_id', $request->examen_type_id);
        }

        $requests = $query->orderByDesc('created_at')->get();

        return response()->json([
            'success' => true,
            'data'    => $requests,
        ]);
    }

    /**
     * POST /api/v1/laboratoire/examen-requests
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id'         => ['required', 'integer', 'exists:t_patients,id'],
            'registre_id'        => ['required', 'integer'],
            'examen_type_id'     => ['required', 'integer', 'exists:examen_types,id'],
            'tariff_item_id'     => ['required', 'integer', 'exists:tariff_items,id'],
            'unit_price_applied' => ['required', 'numeric', 'min:0'],
            'billing_request_id' => ['nullable', 'integer', 'exists:t_billing_requests,id'],
            'notes'              => ['nullable', 'string'],
            'is_urgent'          => ['boolean'],
            'status'             => ['nullable', 'string'],
        ]);

        $validated['status'] = $validated['status'] ?? 'pending_payment';

        $examenRequest = ExamenRequest::create($validated)->load([
            'patient.personne',
            'examenType',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande d\'examen créée avec succès.',
            'data'    => $examenRequest,
        ], 201);
    }

    /**
     * GET /api/v1/laboratoire/examen-requests/{examenRequest}
     */
    public function show(ExamenRequest $examenRequest): JsonResponse
    {
        $examenRequest->load([
            'patient.personne',
            'examenType',
        ]);

        return response()->json([
            'success' => true,
            'data'    => $examenRequest,
        ]);
    }

    /**
     * PUT /api/v1/laboratoire/examen-requests/{examenRequest}
     */
    public function update(Request $request, ExamenRequest $examenRequest): JsonResponse
    {
        $validated = $request->validate([
            'examen_type_id'     => ['sometimes', 'integer', 'exists:examen_types,id'],
            'tariff_item_id'     => ['sometimes', 'integer', 'exists:tariff_items,id'],
            'unit_price_applied' => ['sometimes', 'numeric', 'min:0'],
            'billing_request_id' => ['nullable', 'integer', 'exists:t_billing_requests,id'],
            'notes'              => ['nullable', 'string'],
            'is_urgent'          => ['boolean'],
            'status'             => ['nullable', 'string'],
        ]);

        $examenRequest->update($validated);
        $examenRequest->load([
            'patient.personne',
            'examenType',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Demande d\'examen mise à jour avec succès.',
            'data'    => $examenRequest,
        ]);
    }

    /**
     * DELETE /api/v1/laboratoire/examen-requests/{examenRequest}
     */
    public function destroy(ExamenRequest $examenRequest): JsonResponse
    {
        $examenRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Demande d\'examen supprimée avec succès.',
        ]);
    }

    /**
     * GET /api/v1/laboratoire/examen-requests/worklist
     */
    public function worklist(): JsonResponse
    {
        $requests = ExamenRequest::with([
                'patient.personne',
                'examenType',
            ])
            ->where('status', 'authorized')
            ->orderByDesc('is_urgent')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $requests,
        ]);
    }

    /**
     * GET /api/v1/laboratoire/examen-requests/pending
     */
    public function pending(): JsonResponse
    {
        $requests = ExamenRequest::with([
                'patient.personne',
                'examenType',
            ])
            ->where('status', 'pending_payment')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $requests,
        ]);
    }
}