<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\VonageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Controller handling phone verification notifications.
 */
class PhoneVerificationNotificationController extends Controller
{
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
            return response()->json(['message' => 'Failed to send verification code. Please try again.'], 503);
        }

        return response()->json([
            'message' => 'Verification code sent via ' . strtoupper($method) . '.',
            'id'      => $user->id,       // Apenas para testes; remova em produção
            'hash'    => sha1($user->email), // Apenas para testes; remova em produção
        ], 200);
    }
}
