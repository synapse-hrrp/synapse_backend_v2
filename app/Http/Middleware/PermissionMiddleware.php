<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'msg' => 'Non authentifié',
            ], 401);
        }

        if (!$user->can($permission)) {
            return response()->json([
                'status' => false,
                'msg' => "Accès refusé (permission: {$permission})",
            ], 403);
        }

        return $next($request);
    }
}
