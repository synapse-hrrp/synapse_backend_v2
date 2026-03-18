<?php

namespace Modules\Reactifs\App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Reactifs\App\Models\ReactifFournisseur;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReactifFournisseurController extends Controller
{
    public function index(): JsonResponse
    {
        $fournisseurs = ReactifFournisseur::where('actif', true)
            ->orderBy('nom')
            ->paginate(20);

        return response()->json([
            'data' => $fournisseurs,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nom'         => 'required|string|max:255',
            'code'        => 'nullable|string|unique:reactif_fournisseurs,code',
            'contact_nom' => 'nullable|string|max:255',
            'telephone'   => 'nullable|string|max:50',
            'email'       => 'nullable|email|max:255',
            'adresse'     => 'nullable|string',
            'pays'        => 'nullable|string|max:100',
            'notes'       => 'nullable|string',
        ]);

        $fournisseur = ReactifFournisseur::create($validated);

        return response()->json([
            'message' => "Fournisseur {$fournisseur->nom} créé avec succès.",
            'data'    => $fournisseur,
        ], 201);
    }

    public function show(ReactifFournisseur $fournisseur): JsonResponse
    {
        $fournisseur->load(['commandes' => fn($q) => $q->latest()->limit(10)]);

        return response()->json([
            'data' => $fournisseur,
        ]);
    }

    public function update(Request $request, ReactifFournisseur $fournisseur): JsonResponse
    {
        $validated = $request->validate([
            'nom'         => 'required|string|max:255',
            'code'        => 'nullable|string|unique:reactif_fournisseurs,code,' . $fournisseur->id,
            'contact_nom' => 'nullable|string|max:255',
            'telephone'   => 'nullable|string|max:50',
            'email'       => 'nullable|email|max:255',
            'adresse'     => 'nullable|string',
            'pays'        => 'nullable|string|max:100',
            'actif'       => 'boolean',
            'notes'       => 'nullable|string',
        ]);

        $fournisseur->update($validated);

        return response()->json([
            'message' => 'Fournisseur mis à jour.',
            'data'    => $fournisseur,
        ]);
    }

    public function destroy(ReactifFournisseur $fournisseur): JsonResponse
    {
        // Vérifier si le fournisseur a des commandes
        if ($fournisseur->commandes()->count() > 0) {
            // Désactiver au lieu de supprimer
            $fournisseur->update(['actif' => false]);

            return response()->json([
                'message' => 'Fournisseur désactivé car il possède des commandes.',
                'data'    => $fournisseur,
            ]);
        }

        $fournisseur->delete();

        return response()->json([
            'message' => 'Fournisseur supprimé.',
        ]);
    }
}