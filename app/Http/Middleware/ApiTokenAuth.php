<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\ApiToken;

class ApiTokenAuth
{
    public function handle($request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Missing token'], 401);
        }

        $apiToken = ApiToken::where('token', $token)->first();

        if (!$apiToken) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->merge(['company_id' => $apiToken->company_id]);

        return $next($request);
    }
}
