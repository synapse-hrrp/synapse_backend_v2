<?php

namespace Modules\Soins\App\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Soins\App\Models\DeclarationNaissance;

class DeclarationNaissanceController extends Controller
{
    use AuthorizesRequests;

    /**
     * POST /api/v1/soins/declarations-naissance
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', DeclarationNaissance::class);

        $validated = $request->validate([
            'accouchement_id'       => ['required', 'integer', 'exists:accouchements,id'],

            // migration: nom nullable(100), prenom required(100)
            'nom'                   => ['nullable', 'string', 'max:100'],
            'prenom'                => ['required', 'string', 'max:100'],

            // migration: sexe enum NOT NULL
            'sexe'                  => ['required', 'in:masculin,feminin,indetermine'],

            // migration: timestamp NOT NULL
            'date_heure_naissance'  => ['required', 'date'],

            // migration: lieu_naissance nullable(200)
            'lieu_naissance'        => ['nullable', 'string', 'max:200'],

            // migration: unsignedSmallInteger + decimal(4,1)
            'poids_naissance'       => ['nullable', 'integer', 'min:0', 'max:65535'],
            'taille_naissance'      => ['nullable', 'numeric', 'min:0'],

            // migration: FK NOT NULL
            'mere_patient_id'       => ['required', 'integer', 'exists:t_patients,id'],

            // migration: pere_* nullable(100)
            'pere_nom'              => ['nullable', 'string', 'max:100'],
            'pere_prenom'           => ['nullable', 'string', 'max:100'],
            'pere_profession'       => ['nullable', 'string', 'max:100'],

            // migration: enum + default('brouillon')
            'status'                => ['nullable', 'in:brouillon,validee,transmise,enregistree'],

            'numero_acte'           => ['nullable', 'string', 'max:100'],
            'date_enregistrement'   => ['nullable', 'date'],

            'agent_id'              => ['nullable', 'integer', 'exists:t_agents,id'],
            'observations'          => ['nullable', 'string'],
        ]);

        // ✅ default cohérent DB
        $validated['status'] = $validated['status'] ?? 'brouillon';

        // ✅ 1 accouchement => 1 déclaration
        $exists = DeclarationNaissance::query()
            ->where('accouchement_id', (int) $validated['accouchement_id'])
            ->first();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Une déclaration existe déjà pour cet accouchement.',
                'data'    => $exists,
            ], 409);
        }

        $declaration = DeclarationNaissance::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Déclaration de naissance créée avec succès.',
            'data'    => $declaration,
        ], 201);
    }

    /**
     * GET /api/v1/soins/declarations-naissance/{id}
     */
    public function show(DeclarationNaissance $declarationNaissance): JsonResponse
    {
        $this->authorize('view', $declarationNaissance);

        return response()->json([
            'success' => true,
            'data'    => $declarationNaissance,
        ]);
    }

    /**
     * PUT /api/v1/soins/declarations-naissance/{id}
     */
    public function update(Request $request, DeclarationNaissance $declarationNaissance): JsonResponse
    {
        $this->authorize('update', $declarationNaissance);

        $validated = $request->validate([
            'nom'                   => ['sometimes', 'nullable', 'string', 'max:100'],
            'prenom'                => ['sometimes', 'string', 'max:100'],

            'sexe'                  => ['sometimes', 'in:masculin,feminin,indetermine'],
            'date_heure_naissance'  => ['sometimes', 'date'],
            'lieu_naissance'        => ['sometimes', 'nullable', 'string', 'max:200'],

            'poids_naissance'       => ['sometimes', 'nullable', 'integer', 'min:0', 'max:65535'],
            'taille_naissance'      => ['sometimes', 'nullable', 'numeric', 'min:0'],

            'mere_patient_id'       => ['sometimes', 'integer', 'exists:t_patients,id'],

            'pere_nom'              => ['sometimes', 'nullable', 'string', 'max:100'],
            'pere_prenom'           => ['sometimes', 'nullable', 'string', 'max:100'],
            'pere_profession'       => ['sometimes', 'nullable', 'string', 'max:100'],

            'status'                => ['sometimes', 'in:brouillon,validee,transmise,enregistree'],

            'numero_acte'           => ['sometimes', 'nullable', 'string', 'max:100'],
            'date_enregistrement'   => ['sometimes', 'nullable', 'date'],

            'agent_id'              => ['sometimes', 'nullable', 'integer', 'exists:t_agents,id'],
            'observations'          => ['sometimes', 'nullable', 'string'],
        ]);

        $declarationNaissance->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Déclaration de naissance mise à jour avec succès.',
            'data'    => $declarationNaissance,
        ]);
    }
}