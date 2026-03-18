<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

class DebugController
{
    public function can(Request $request, string $ability)
    {
        $user = $request->user();

        return response()->json([
            'user_id' => $user?->id,
            'ability' => $ability,
            'can' => $user ? $user->can($ability) : false,
            'roles' => $user?->roles()->pluck('label')->all(),
            'permissions' => method_exists($user, 'permissionsListStrings') ? $user->permissionsListStrings() : [],
        ]);
    }
}
