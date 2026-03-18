<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Users\App\Models\User;

class UserRoleController extends Controller
{
    /**
     * Remplace tous les rôles d'un user.
     * Body: { "role_labels": ["admin", "reception"] }
     */
    public function syncUserRoles(Request $request, int $userId): JsonResponse
    {
        $data = $request->validate([
            'role_labels' => ['required', 'array'],
            'role_labels.*' => ['string'],
        ]);

        /** @var User|null $user */
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['status' => false, 'msg' => 'Utilisateur introuvable'], 404);
        }

        $roleIds = DB::table('roles')
            ->whereIn('label', $data['role_labels'])
            ->pluck('id')
            ->all();

        DB::beginTransaction();
        try {
            // supprime les anciens rôles
            DB::table('users_roles')->where('users_id', $userId)->delete();

            // insère les nouveaux
            foreach ($roleIds as $rid) {
                DB::table('users_roles')->insert([
                    'users_id' => $userId,
                    'roles_id' => $rid,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            // purge cache permissions
            $user->flushPermissionsCache();

            return response()->json(['status' => true, 'msg' => 'Rôles synchronisés']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'msg' => $e->getMessage()], 500);
        }
    }
}
