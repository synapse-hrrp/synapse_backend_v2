<?php

namespace Modules\Imagerie\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Imagerie\App\Models\ImagerieResultat;

class ImagerieResultatController extends Controller
{
    // GET /api/v1/imagerie/resultats
    public function index(Request $request): JsonResponse
    {
        $query = ImagerieResultat::query()
            ->with([
                'imagerie.request.imagerieType',
            ])
            ->orderByDesc('valide_le');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('valide_le', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('valide_le', '<=', $request->date_to);
        }

        if ($request->filled('patient_id')) {
            $query->whereHas('imagerie.request', fn($q) => $q->where('patient_id', $request->patient_id));
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('compte_rendu', 'like', "%{$q}%")
                    ->orWhere('conclusion', 'like', "%{$q}%")
                    ->orWhere('recommandations', 'like', "%{$q}%");
            });
        }

        return response()->json([
            'success' => true,
            'data'    => $query->paginate(20),
        ]);
    }

    // GET /api/v1/imagerie/resultats/{resultat}
    public function show(ImagerieResultat $resultat): JsonResponse
    {
        $resultat->load(['imagerie.request.imagerieType']);

        return response()->json([
            'success' => true,
            'data'    => $resultat,
        ]);
    }
}