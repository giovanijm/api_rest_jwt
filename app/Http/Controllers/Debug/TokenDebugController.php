<?php

namespace App\Http\Controllers\Debug;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

class TokenDebugController extends Controller
{
    /**
     * Return the decoded JWT claims for the current token.
     * This endpoint is registered only in non-production environments.
     */
    public function __invoke(Request $request)
    {
        // Protect this endpoint by environment check as an extra safety
        if (! app()->environment(['local', 'development']) && ! config('app.debug')) {
            return response()->json(['message' => 'Endpoint available in local/dev only'], 403);
        }

        try {
            $token = JWTAuth::getToken();

            if (! $token) {
                return response()->json(['message' => 'No token present'], 400);
            }

            $payload = JWTAuth::setToken($token)->getPayload();

            // toArray will give us the claims as an associative array
            return response()->json([
                'claims' => $payload->toArray(),
                'token' => (string) $token,
            ]);

        } catch (JWTException $e) {
            return response()->json(['message' => 'Invalid token', 'error' => $e->getMessage()], 400);
        }
    }
}
