<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class VerifyEmailController extends Controller
{
    /**
     * Handle the incoming request for email verification.
     *
     * This implementation does not require a session-authenticated user. It
     * finds the user by the {id} route parameter (ULID), validates the email
     * hash, and marks the email as verified.
     */
    public function __invoke(Request $request, string $id, string $hash): JsonResponse
    {
        // Find user by ULID (id)
        $user = User::find($id);

        if (! $user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Validate that the hash in the URL matches the user's email
        $expected = sha1($user->getEmailForVerification());

        if (! hash_equals((string) $hash, $expected)) {
            return response()->json(['message' => 'Invalid verification link.'], 403);
        }

        // If already verified, return success
        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.'], 200);
        }

        // Mark as verified and fire the Verified event
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));

            // Clear rate-limiter entries related to unverified-login attempts.
            // We clear two keys:
            // - per-email+ip: login-unverified:{lowercase_email}|{ip}
            // - per-email fallback: login-unverified:{lowercase_email}|unknown
            $emailKeyBase = 'login-unverified:' . strtolower($user->email);

            // Clear key for this request IP (if available)
            $ip = $request->ip() ?? 'unknown';
            $keyWithIp = $emailKeyBase . '|' . $ip;
            RateLimiter::clear($keyWithIp);

            // Also clear the generic/unknown key
            $keyUnknown = $emailKeyBase . '|unknown';
            RateLimiter::clear($keyUnknown);
        }

        return response()->json(['message' => 'Email verified successfully.'], 200);
    }
}
