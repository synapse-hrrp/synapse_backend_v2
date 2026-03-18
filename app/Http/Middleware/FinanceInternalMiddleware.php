<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class FinanceInternalMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $key = (string) $request->header('X-Finance-Key');
        $expected = (string) config('finance.internal_key');

        if ($expected === '' || $key !== $expected) {
            return response()->json([
                'success' => false,
                'message' => 'Accès interdit (Finance internal).',
            ], 403);
        }

        return $next($request);
    }
}