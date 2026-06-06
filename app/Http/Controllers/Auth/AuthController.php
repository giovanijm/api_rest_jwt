<?php

namespace App\Http\Controllers\Auth;

use \App\Notifications\ForgotPasswordNotification;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

/**
 * Controller handling user authentication.
 */
class AuthController extends Controller
{
    /**
     * Handle user login and return a JWT token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(): JsonResponse
    {
        $validator = Validator::make(request()->all(), [
            'email'    => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $credentials = request(['email', 'password']);

        // Find user by email to check verification status first
        $user = User::where('email', $credentials['email'] ?? null)->first();

        if (! $user || $user->is_active === false) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // // Determine verification status (support models without hasVerifiedEmail method)
        // $isVerified = method_exists($user, 'hasVerifiedEmail')
        //     ? $user->hasVerifiedEmail()
        //     : ! is_null($user->email_verified_at);

        // if (! $isVerified) {
        //     // Rate limit key specific to unverified email attempts + client IP
        //     $ip = request()->ip();
        //     $key = 'login-unverified:' . strtolower($user->email) . '|' . ($ip ?? 'unknown');
        //     $maxAttempts = 5;
        //     $decaySeconds = 15 * 60; // 15 minutes

        //     if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
        //         $seconds = RateLimiter::availableIn($key);
        //         return response()->json([
        //             'error' => 'Too many attempts. Please try again later.',
        //             'retry_after_seconds' => $seconds,
        //         ], 429);
        //     }

        //     // Increment attempts for this unverified email+IP
        //     RateLimiter::hit($key, $decaySeconds);

        //     return response()->json(['error' => 'Email not verified.'], 403);
        // }

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return UserResource
     */
    public function me(): UserResource
    {
        return UserResource::make(auth('api')->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Renova o token JWT com claims frescos do banco de dados.
     *
     * O auth()->refresh() padrão copia os custom claims do token anterior,
     * portanto email_verified / phone_verified não refletiriam mudanças recentes.
     * A solução é invalidar o token atual e emitir um novo via login($user),
     * o que força a chamada de getJWTCustomClaims() com dados atuais do BD.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(): JsonResponse
    {
        $user = auth('api')->user()->fresh();
        auth('api')->invalidate();
        $token = auth('api')->login($user);

        return $this->respondWithToken($token);
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60
        ]);
    }
}
