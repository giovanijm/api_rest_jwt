<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\VonageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Throwable;

class PhoneVerificationNotificationController extends Controller
{
    private const COOLDOWN_SECONDS = 15;

    public function __construct(private VonageService $vonage) {}

    /**
     * Handle the incoming request to send a phone verification notification.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id'     => 'required|string|max:50',
            'method' => 'required|string|in:sms,whatsapp',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = User::find($validator->validated()['id']);

        if (! $user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if ($user->hasVerifiedPhone()) {
            return response()->json(['message' => 'Phone already verified.'], 200);
        }

        $key = 'phone-verification:' . $user->id;

        if (RateLimiter::tooManyAttempts($key, 1)) {
            $retryAfter = RateLimiter::availableIn($key);

            return response()->json([
                'message'             => "Aguarde {$retryAfter} segundo(s) antes de reenviar o código.",
                'retry_after_seconds' => $retryAfter,
            ], 429);
        }

        RateLimiter::hit($key, self::COOLDOWN_SECONDS);

        $method = strtolower($validator->validated()['method']);

        $user->regenerateTwoFactorCode($method);

        $message = __('notification-sms.phone-verify.body', ['code' => $user->two_factor_code]);

        try {
            if ($method === 'whatsapp') {
                $this->vonage->sendWhatsApp($user->phone_number, $message);
            } else {
                $this->vonage->sendSms($user->phone_number, $message);
            }
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'message' => $e->getMessage() ?: 'Falha ao enviar o código. Tente novamente.',
            ], 503);
        }

        return response()->json([
            'message' => 'Verification code sent via ' . strtoupper($method) . '.',
            'id'      => $user->id,       // Apenas para testes; remova em produção
            'hash'    => sha1($user->email), // Apenas para testes; remova em produção
        ], 200);
    }
}
