<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ApiToken;

class ApiTokenAuth
{
    public function handle(Request $request, Closure $next)
    {
        $header = $request->header('Authorization');

        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return response()->json([
                'message' => 'Invalid API token'
            ], 401);
        }

        $tokenValue = trim(str_replace('Bearer', '', $header));

        $token = ApiToken::where('token', $tokenValue)->first();

        if (!$token || !$token->company || !$token->company->is_active) {
            return response()->json([
                'message' => 'Invalid API token'
            ], 401);
        }

        // 🔑 Attach token to request
        $request->attributes->set('api_token', $token);

        return $next($request);
    }
}
