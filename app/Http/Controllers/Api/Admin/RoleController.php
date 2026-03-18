<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RoleController extends Controller
{
    /**
     * Donne les fonctionnalités d'un rôle.
     */
    public function permissions(int $roleId): JsonResponse
    {
        $role = DB::table('roles')->where('id', $roleId)->first();
        if (!$role) {
            return response()->json(['status' => false, 'msg' => 'Rôle introuvable'], 404);
        }

        $ids = DB::table('roles_fonctionnalites')
            ->where('roles_id', $roleId)
            ->where(function ($q) {
                if (Schema::hasColumn('roles_fonctionnalites', 'deleted_at')) {
                    $q->whereNull('deleted_at');
                }
            })
            ->pluck('fonc_id')
            ->all();

        $perms = DB::table('fonctionnalites')
            ->whereIn('id', $ids)
            ->select('id', 'label', 'tech_label', 'modules_id', 'parent')
            ->orderBy('modules_id')->orderBy('id')
            ->get();

        return response()->json(['status' => true, 'data' => $perms]);
    }

    /**
     * Remplace toutes les fonctionnalités d'un rôle.
     * Body: { "tech_labels": ["patients.view", "patients.create"] }
     */
    public function syncPermissions(Request $request, int $roleId): JsonResponse
    {
        $data = $request->validate([
            'tech_labels' => ['required', 'array'],
            'tech_labels.*' => ['string'],
        ]);

        $role = DB::table('roles')->where('id', $roleId)->first();
        if (!$role) {
            return response()->json(['status' => false, 'msg' => 'Rôle introuvable'], 404);
        }

        $foncIds = DB::table('fonctionnalites')
            ->whereIn('tech_label', $data['tech_labels'])
            ->pluck('id')
            ->all();

        DB::beginTransaction();
        try {
            // soft delete si possible, sinon delete
            if (Schema::hasColumn('roles_fonctionnalites', 'deleted_at')) {
                DB::table('roles_fonctionnalites')
                    ->where('roles_id', $roleId)
                    ->update(['deleted_at' => now()]);
            } else {
                DB::table('roles_fonctionnalites')
                    ->where('roles_id', $roleId)
                    ->delete();
            }

            foreach ($foncIds as $fid) {
                $payload = [
                    'roles_id' => $roleId,
                    'fonc_id'  => $fid,
                ];

                if (Schema::hasColumn('roles_fonctionnalites', 'deleted_at')) $payload['deleted_at'] = null;
                if (Schema::hasColumn('roles_fonctionnalites', 'created_at')) $payload['created_at'] = now();
                if (Schema::hasColumn('roles_fonctionnalites', 'updated_at')) $payload['updated_at'] = now();

                DB::table('roles_fonctionnalites')->updateOrInsert(
                    ['roles_id' => $roleId, 'fonc_id' => $fid],
                    $payload
                );
            }

            DB::commit();
            return response()->json(['status' => true, 'msg' => 'Permissions synchronisées']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'msg' => $e->getMessage()], 500);
        }
    }
}
