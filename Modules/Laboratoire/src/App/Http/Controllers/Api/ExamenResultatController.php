<?php

namespace Modules\Laboratoire\App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Laboratoire\App\Models\Examen;
use Modules\Laboratoire\App\Models\ExamenResultat;

class ExamenResultatController extends Controller
{
    /**
     * GET /api/v1/laboratoire/resultats
     */
    public function indexAll(): JsonResponse
    {
        $resultats = ExamenResultat::with([
            'examen.request.patient.personne',
            'examen.request.examenType',
        ])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $resultats,
        ]);
    }

    /**
     * GET /api/v1/laboratoire/examens/{examen}/resultats
     */
    public function index(Examen $examen): JsonResponse
    {
        $examen->load([
            'request.patient.personne',
            'request.examenType',
            'resultats',
        ]);

        return response()->json([
            'success' => true,
            'data'    => $examen->resultats,
        ]);
    }

    /**
     * POST /api/v1/laboratoire/examens/{examen}/resultats
     */
    public function store(Request $request, Examen $examen): JsonResponse
    {
        $request->validate([
            'parametre'          => ['required', 'string', 'max:150'],
            'valeur'             => ['required', 'string', 'max:100'],
            'unite'              => ['nullable', 'string', 'max:50'],
            'valeur_normale_min' => ['nullable', 'string', 'max:50'],
            'valeur_normale_max' => ['nullable', 'string', 'max:50'],
            'interpretation'     => ['nullable', 'in:normal,bas,eleve,positif,negatif'],
            'observations'       => ['nullable', 'string'],
        ]);

        $resultat = ExamenResultat::create([
            'examen_id'          => $examen->id,
            'parametre'          => $request->parametre,
            'valeur'             => $request->valeur,
            'unite'              => $request->unite,
            'valeur_normale_min' => $request->valeur_normale_min,
            'valeur_normale_max' => $request->valeur_normale_max,
            'interpretation'     => $request->interpretation,
            'observations'       => $request->observations,
        ]);

        $resultat->load([
            'examen.request.patient.personne',
            'examen.request.examenType',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Résultat ajouté avec succès.',
            'data'    => $resultat,
        ], 201);
    }

    /**
     * GET /api/v1/laboratoire/resultats/{resultat}
     */
    public function show(ExamenResultat $resultat): JsonResponse
    {
        $resultat->load([
            'examen.request.patient.personne',
            'examen.request.examenType',
        ]);

        return response()->json([
            'success' => true,
            'data'    => $resultat,
        ]);
    }

    /**
     * PUT /api/v1/laboratoire/resultats/{resultat}
     */
    public function update(Request $request, ExamenResultat $resultat): JsonResponse
    {
        $request->validate([
            'parametre'          => ['sometimes', 'string', 'max:150'],
            'valeur'             => ['sometimes', 'string', 'max:100'],
            'unite'              => ['nullable', 'string', 'max:50'],
            'valeur_normale_min' => ['nullable', 'string', 'max:50'],
            'valeur_normale_max' => ['nullable', 'string', 'max:50'],
            'interpretation'     => ['nullable', 'in:normal,bas,eleve,positif,negatif'],
            'observations'       => ['nullable', 'string'],
        ]);

        $resultat->update($request->only([
            'parametre',
            'valeur',
            'unite',
            'valeur_normale_min',
            'valeur_normale_max',
            'interpretation',
            'observations',
        ]));

        $resultat->load([
            'examen.request.patient.personne',
            'examen.request.examenType',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Résultat modifié avec succès.',
            'data'    => $resultat,
        ]);
    }

    /**
     * DELETE /api/v1/laboratoire/resultats/{resultat}
     */
    public function destroy(ExamenResultat $resultat): JsonResponse
    {
        $resultat->delete();

        return response()->json([
            'success' => true,
            'message' => 'Résultat supprimé avec succès.',
        ]);
    }
}