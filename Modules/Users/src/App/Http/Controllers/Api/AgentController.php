<?php

namespace Modules\Users\App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PersonneService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Users\App\Models\Agent;

class AgentController extends Controller
{
    public function index()
    {
        $agents = Agent::query()
            ->with('personne')
            ->orderByDesc('id')
            ->paginate(10);

        return response()->json([
            'message' => 'Liste des agents',
            'data' => $agents,
        ]);
    }

    public function store(Request $request, PersonneService $personneService)
    {
        $data = $request->validate([
            'matricule' => ['required', 'string', 'max:50', 'unique:t_agents,matricule'],
            'statut'    => ['required', 'string', 'max:20'],

            // WEB mode
            'personne_id' => ['nullable', 'integer', 'exists:t_personnes,id'],

            // MOBILE mode (inline)
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

        // ✅ force "personne_id OU personne"
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

            $agent = Agent::query()->create([
                'matricule'   => $data['matricule'],
                'statut'      => $data['statut'],
                'personne_id' => $personne->id,
            ]);

            return $agent->load('personne');
        });

        return response()->json([
            'message' => 'Agent créé',
            'data' => $result,
        ], 201);
    }

    public function show(Agent $agent)
    {
        return response()->json([
            'message' => 'Détails agent',
            'data' => $agent->load('personne'),
        ]);
    }

    public function update(Request $request, Agent $agent, PersonneService $personneService)
    {
        $data = $request->validate([
            'matricule' => ['sometimes', 'string', 'max:50', 'unique:t_agents,matricule,' . $agent->id],
            'statut'    => ['sometimes', 'string', 'max:20'],

            // Option : ré-attacher à une autre personne
            'personne_id' => ['sometimes', 'nullable', 'integer', 'exists:t_personnes,id'],

            // Option : mettre à jour/compléter les infos personne (mobile)
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

        $result = DB::transaction(function () use ($agent, $data, $personneService) {
            // Update agent fields
            $agent->fill(collect($data)->only(['matricule', 'statut', 'personne_id'])->toArray());
            $agent->save();

            // Update personne inline (si envoyé)
            if (!empty($data['personne'])) {
                $agent->personne()->update($data['personne']);
            }

            return $agent->refresh()->load('personne');
        });

        return response()->json([
            'message' => 'Agent modifié',
            'data' => $result,
        ]);
    }

    public function destroy(Agent $agent)
    {
        $agent->delete();

        return response()->json([
            'message' => 'Agent supprimé',
            'data' => null,
        ]);
    }
}
