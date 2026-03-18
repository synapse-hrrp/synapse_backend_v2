<?php

namespace Modules\Users\App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\Users\App\Models\User;
use Modules\Users\App\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        return User::query()
            ->with(['agent:id,matricule,statut', 'roles:id,label,description'])
            ->paginate(15);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],

            // ✅ liaison agent
            'agent_id' => ['nullable', 'integer', 'exists:t_agents,id'],

            // ✅ optionnel: assign roles à la création
            'roles' => ['nullable', 'array', 'min:1'],
            'roles.*' => ['required_with:roles', 'string', 'exists:roles,label'],
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'agent_id' => $data['agent_id'] ?? null,
            ]);

            if (!empty($data['roles'])) {
                $roleIds = Role::query()
                    ->whereIn('label', $data['roles'])
                    ->pluck('id')
                    ->all();

                $user->roles()->sync($roleIds);
                $user->flushPermissionsCache();
            }

            return $user->fresh(['agent:id,matricule,statut', 'roles:id,label,description']);
        });

        return response()->json([
            'message' => 'Utilisateur créé',
            'data' => $user,
        ], 201);
    }

    public function show(int $id)
    {
        $user = User::query()
            ->with(['agent:id,matricule,statut', 'roles:id,label,description'])
            ->findOrFail($id);

        return response()->json([
            'message' => 'Détails utilisateur',
            'data' => $user,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $user = User::query()->findOrFail($id);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['sometimes', 'string', 'min:6'],
            'agent_id' => ['sometimes', 'nullable', 'integer', 'exists:t_agents,id'],
        ]);

        $user->update($data);

        return response()->json([
            'message' => 'Utilisateur mis à jour',
            'data' => $user->fresh(['agent:id,matricule,statut', 'roles:id,label,description']),
        ]);
    }

    public function destroy(int $id)
    {
        $user = User::query()->findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'Utilisateur supprimé']);
    }

    /**
     * ✅ Assigner / remplacer les rôles d'un user
     * POST /v1/users/{id}/roles  { "roles": ["reception","medecin"] }
     */
    public function syncRoles(Request $request, int $id)
    {
        $data = $request->validate([
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'string', 'exists:roles,label'],
        ]);

        $user = User::query()->findOrFail($id);

        $roleIds = Role::query()
            ->whereIn('label', $data['roles'])
            ->pluck('id')
            ->all();

        $user->roles()->sync($roleIds);
        $user->flushPermissionsCache();

        return response()->json([
            'message' => 'Rôles mis à jour',
            'data' => [
                'user_id' => $user->id,
                'roles' => $user->roles()->pluck('label')->values(),
            ],
        ]);
    }

    /**
     * ✅ Ajouter un rôle sans enlever les autres
     * POST /v1/users/{id}/roles/attach { "role": "medecin" }
     */
    public function attachRole(Request $request, int $id)
    {
        $data = $request->validate([
            'role' => ['required', 'string', 'exists:roles,label'],
        ]);

        $user = User::query()->findOrFail($id);

        $roleId = Role::query()
            ->where('label', $data['role'])
            ->value('id');

        $user->roles()->syncWithoutDetaching([$roleId]);
        $user->flushPermissionsCache();

        return response()->json([
            'message' => 'Rôle ajouté',
            'data' => [
                'user_id' => $user->id,
                'roles' => $user->roles()->pluck('label')->values(),
            ],
        ]);
    }
}
