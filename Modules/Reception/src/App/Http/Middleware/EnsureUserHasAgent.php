<?php

namespace Modules\Reception\App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasAgent
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // pas connecté
        if (!$user) {
            return response()->json([
                'message' => 'Non authentifié.',
            ], 401);
        }

        // user connecté mais pas lié à un agent
        if (!$user->agent_id) {
            return response()->json([
                'message' => "Accès refusé : l'utilisateur n'est pas lié à un agent.",
                'error' => 'AGENT_REQUIRED',
            ], 403);
        }

        return $next($request);
    }
}
