<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Services\PersonneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatientController extends Controller
{
    public function index()
    {
        $patients = Patient::query()
            ->with('personne')
            ->orderByDesc('id')
            ->paginate(10);

        return response()->json([
            'message' => 'Liste des patients',
            'data' => $patients,
        ]);
    }

    public function store(Request $request, PersonneService $personneService)
    {
        $data = $request->validate([
            'nip' => ['required', 'string', 'max:50', 'unique:t_patients,nip'],
            'telephone' => ['nullable', 'string', 'max:50'],
            'adresse' => ['nullable', 'string', 'max:255'],

            // WEB mode
            'personne_id' => ['nullable', 'integer', 'exists:t_personnes,id'],

            // MOBILE mode
            'personne' => ['nullable', 'array'],
            'personne.nom' => ['required_without:personne_id', 'string', 'max:100'],
            'personne.prenom' => ['nullable', 'string', 'max:100'],
            'personne.sexe' => ['nullable', 'in:M,F'],
            'personne.date_naissance' => ['nullable', 'date'],
            'personne.lieu_naissance' => ['nullable', 'string', 'max:255'],
            'personne.nationalite' => ['nullable', 'string', 'max:255'],
            'personne.nom_pere' => ['nullable', 'string', 'max:255'],
            'personne.nom_mere' => ['nullable', 'string', 'max:255'],
        ]);

        if (empty($data['personne_id']) && empty($data['personne'])) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => [
                    'personne' => ['Tu dois fournir soit personne_id soit personne{...}.']
                ],
            ], 422);
        }

        $result = DB::transaction(function () use ($data, $personneService) {
            $personne = $personneService->resolve($data);

            $patient = Patient::query()->create([
                'nip' => $data['nip'],
                'telephone' => $data['telephone'] ?? null,
                'adresse' => $data['adresse'] ?? null,
                'personne_id' => $personne->id,
            ]);

            return $patient->load('personne');
        });

        return response()->json([
            'message' => 'Patient créé',
            'data' => $result,
        ], 201);
    }

    public function show(Patient $patient)
    {
        return response()->json([
            'message' => 'Détails patient',
            'data' => $patient->load('personne'),
        ]);
    }

    public function update(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'nip' => ['sometimes', 'string', 'max:50', 'unique:t_patients,nip,' . $patient->id],
            'telephone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'adresse' => ['sometimes', 'nullable', 'string', 'max:255'],

            // Option : compléter la personne aussi
            'personne' => ['sometimes', 'array'],
            'personne.nom' => ['sometimes', 'string', 'max:100'],
            'personne.prenom' => ['sometimes', 'nullable', 'string', 'max:100'],
            'personne.sexe' => ['sometimes', 'nullable', 'in:M,F'],
            'personne.date_naissance' => ['sometimes', 'nullable', 'date'],
            'personne.lieu_naissance' => ['sometimes', 'nullable', 'string', 'max:255'],
            'personne.nationalite' => ['sometimes', 'nullable', 'string', 'max:255'],
            'personne.nom_pere' => ['sometimes', 'nullable', 'string', 'max:255'],
            'personne.nom_mere' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $result = DB::transaction(function () use ($patient, $data) {
            $patient->fill(collect($data)->only(['nip', 'telephone', 'adresse'])->toArray());
            $patient->save();

            if (!empty($data['personne'])) {
                $patient->personne()->update($data['personne']);
            }

            return $patient->refresh()->load('personne');
        });

        return response()->json([
            'message' => 'Patient modifié',
            'data' => $result,
        ]);
    }

    public function destroy(Patient $patient)
    {
        $patient->delete();

        return response()->json([
            'message' => 'Patient supprimé',
            'data' => null,
        ]);
    }
}
