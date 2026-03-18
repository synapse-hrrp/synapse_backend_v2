<?php

namespace Modules\Pharmacie\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPharmaciePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié',
                'errors' => null
            ], 401);
        }

        if (!$request->user()->can($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé. Permission requise: ' . $permission,
                'errors' => null
            ], 403);
        }

        return $next($request);
    }
}