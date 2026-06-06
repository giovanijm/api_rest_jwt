<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class EmailVerificationNotificationController extends Controller
{
    private const COOLDOWN_SECONDS = 15;

    public function __invoke(): JsonResponse
    {
        $validator = Validator::make(request()->all(), [
            'id' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = User::find($validator->validated()['id']);

        if (! $user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.'], 200);
        }

        $key = 'email-verification:' . $user->id;

        if (RateLimiter::tooManyAttempts($key, 1)) {
            $retryAfter = RateLimiter::availableIn($key);

            return response()->json([
                'message'             => "Aguarde {$retryAfter} segundo(s) antes de reenviar o e-mail.",
                'retry_after_seconds' => $retryAfter,
            ], 429);
        }

        RateLimiter::hit($key, self::COOLDOWN_SECONDS);

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification email sent.'], 200);
    }
}
