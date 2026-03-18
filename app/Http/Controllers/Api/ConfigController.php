<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ConfigController extends Controller
{
    public function roles(): JsonResponse
    {
        $roles = DB::table('roles')->select('id', 'label', 'description')->orderBy('label')->get();
        return response()->json(['status' => true, 'data' => $roles]);
    }

    public function modules(): JsonResponse
    {
        if (!Schema::hasTable('modules')) {
            return response()->json(['status' => true, 'data' => []]);
        }

        // On essaye d'être flexible (slug ou label)
        $cols = Schema::hasColumn('modules', 'slug')
            ? ['id', 'slug', 'label']
            : ['id', 'label'];

        $modules = DB::table('modules')->select($cols)->orderBy('id')->get();
        return response()->json(['status' => true, 'data' => $modules]);
    }

    public function fonctionnalites(): JsonResponse
    {
        $query = DB::table('fonctionnalites')
            ->select('id', 'modules_id', 'parent', 'label', 'tech_label')
            ->orderBy('modules_id')
            ->orderBy('parent')
            ->orderBy('id');

        $items = $query->get();

        return response()->json(['status' => true, 'data' => $items]);
    }

    public function fonctionnalitesTree(): JsonResponse
    {
        $items = DB::table('fonctionnalites')
            ->select('id', 'modules_id', 'parent', 'label', 'tech_label')
            ->orderBy('modules_id')
            ->orderBy('id')
            ->get()
            ->toArray();

        // index
        $byId = [];
        foreach ($items as $it) {
            $it->children = [];
            $byId[$it->id] = $it;
        }

        // build tree
        $roots = [];
        foreach ($items as $it) {
            if ($it->parent && isset($byId[$it->parent])) {
                $byId[$it->parent]->children[] = $it;
            } else {
                $roots[] = $it;
            }
        }

        return response()->json(['status' => true, 'data' => $roots]);
    }
}
