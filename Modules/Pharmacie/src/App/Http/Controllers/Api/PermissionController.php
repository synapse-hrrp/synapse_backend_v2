<?php

namespace Modules\Pharmacie\App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class PermissionController extends Controller
{
    /**
     * Liste des rôles Pharmacie
     */
    public function roles(): JsonResponse
    {
        $roles = Role::whereIn('name', [
            'Pharmacien Chef',
            'Pharmacien',
            'Vendeur Pharmacie',
            'Gestionnaire Stock',
            'Consultation Pharmacie'
        ])->with('permissions')->get();

        return response()->json([
            'success' => true,
            'message' => 'Liste des rôles Pharmacie',
            'data' => $roles
        ], 200);
    }

    /**
     * Liste des permissions Pharmacie
     */
    public function permissions(): JsonResponse
    {
        $permissions = Permission::where('name', 'like', 'pharmacie.%')->get();

        return response()->json([
            'success' => true,
            'message' => 'Liste des permissions Pharmacie',
            'data' => $permissions
        ], 200);
    }

    /**
     * Assigner un rôle à un utilisateur
     */
    public function assignRole(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string|exists:roles,name'
        ]);

        $user = User::findOrFail($request->user_id);
        $user->assignRole($request->role);

        return response()->json([
            'success' => true,
            'message' => 'Rôle assigné avec succès',
            'data' => [
                'user' => $user->name,
                'role' => $request->role,
                'permissions' => $user->getAllPermissions()->pluck('name')
            ]
        ], 200);
    }

    /**
     * Retirer un rôle d'un utilisateur
     */
    public function removeRole(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|string|exists:roles,name'
        ]);

        $user = User::findOrFail($request->user_id);
        $user->removeRole($request->role);

        return response()->json([
            'success' => true,
            'message' => 'Rôle retiré avec succès',
            'data' => null
        ], 200);
    }

    /**
     * Permissions de l'utilisateur connecté
     */
    public function myPermissions(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'message' => 'Vos permissions Pharmacie',
            'data' => [
                'roles' => $user->getRoleNames(),
                'permissions' => $user->getAllPermissions()
                    ->filter(fn($p) => str_starts_with($p->name, 'pharmacie.'))
                    ->pluck('name')
                    ->values()
            ]
        ], 200);
    }
}