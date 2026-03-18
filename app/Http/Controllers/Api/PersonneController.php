<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Personne;
use Illuminate\Http\Request;

class PersonneController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(1, min($perPage, 100));

        $query = Personne::query()->orderByDesc('id');

        if ($q !== '') {
            $like = '%' . preg_replace('/\s+/', '%', $q) . '%';
            $query->where(function ($sub) use ($like) {
                $sub->where('nom', 'like', $like)
                    ->orWhere('prenom', 'like', $like);
            });
        }

        return response()->json([
            'message' => 'Liste des personnes',
            'data' => $query->paginate($perPage),
        ]);
    }

    public function store(Request $request)
    {
        // ✅ "source" te permet de distinguer WEB vs MOBILE proprement
        // - web => exige les champs complets
        // - mobile => champs complets optionnels
        $source = $request->query('source', $request->input('source', 'web')); // web|mobile

        $rules = [
            'nom'            => ['required', 'string', 'max:100'],
            'prenom'         => ['nullable', 'string', 'max:100'],
            'sexe'           => ['nullable', 'in:M,F'],
            'date_naissance' => ['nullable', 'date'],

            'lieu_naissance' => ['nullable', 'string', 'max:255'],
            'nationalite'    => ['nullable', 'string', 'max:255'],
            'nom_pere'       => ['nullable', 'string', 'max:255'],
            'nom_mere'       => ['nullable', 'string', 'max:255'],
        ];

        // ✅ Si web => rendre obligatoires ces champs
        if ($source === 'web') {
            $rules['lieu_naissance'][0] = 'required';
            $rules['nationalite'][0]    = 'required';
            $rules['nom_pere'][0]       = 'required';
            $rules['nom_mere'][0]       = 'required';
        }

        $data = $request->validate($rules);

        $personne = Personne::query()->create($data);

        return response()->json([
            'message' => 'Personne créée',
            'data' => $personne,
        ], 201);
    }

    public function show(Personne $personne)
    {
        return response()->json([
            'message' => 'Détails personne',
            'data' => $personne,
        ]);
    }

    public function update(Request $request, Personne $personne)
    {
        $data = $request->validate([
            'nom'            => ['sometimes', 'string', 'max:100'],
            'prenom'         => ['sometimes', 'nullable', 'string', 'max:100'],
            'sexe'           => ['sometimes', 'nullable', 'in:M,F'],
            'date_naissance' => ['sometimes', 'nullable', 'date'],

            'lieu_naissance' => ['sometimes', 'nullable', 'string', 'max:255'],
            'nationalite'    => ['sometimes', 'nullable', 'string', 'max:255'],
            'nom_pere'       => ['sometimes', 'nullable', 'string', 'max:255'],
            'nom_mere'       => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $personne->update($data);

        return response()->json([
            'message' => 'Personne mise à jour',
            'data' => $personne->fresh(),
        ]);
    }
}
