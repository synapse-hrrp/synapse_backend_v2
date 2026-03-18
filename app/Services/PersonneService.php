<?php

namespace App\Services;

use App\Models\Personne;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class PersonneService
{
    /**
     * Crée ou récupère une Personne selon le payload :
     * - si personne_id est fourni => retourne la personne existante
     * - sinon si personne{} est fourni => crée une personne
     */
    public function resolve(array $payload): Personne
    {
        $personneId = Arr::get($payload, 'personne_id');

        if (!empty($personneId)) {
            return Personne::query()->findOrFail($personneId);
        }

        $personneData = Arr::get($payload, 'personne');

        if (!is_array($personneData)) {
            throw ValidationException::withMessages([
                'personne' => ['Tu dois fournir soit personne_id soit personne{...}.'],
            ]);
        }

        // Validation minimale centralisée
        $validated = validator($personneData, [
            'nom'            => ['required', 'string', 'max:100'],
            'prenom'         => ['nullable', 'string', 'max:100'],
            'sexe' => ['nullable', 'in:M,F'],
            'date_naissance' => ['nullable', 'date'],
            'lieu_naissance' => ['nullable', 'string', 'max:255'],
            'nationalite'    => ['nullable', 'string', 'max:255'],
            'nom_pere'       => ['nullable', 'string', 'max:255'],
            'nom_mere'       => ['nullable', 'string', 'max:255'],
        ])->validate();

        return Personne::query()->create($validated);
    }
}
