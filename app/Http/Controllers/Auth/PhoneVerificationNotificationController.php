<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Controller handling phone verification notifications.
 */
class PhoneVerificationNotificationController extends Controller
{
    /**
     * Handle the incoming request to send a phone verification notification.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validator = Validator::make(request()->all(), [
            'id'        => 'required|string|max:50',
            'method'    => 'required|string|in:sms,whatsapp',
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

        $method_description = strtolower($validator->validated()['method']) === 'whatsapp' ? 'WhatsApp' : 'SMS';

        $user->regenerateTwoFactorCode(strtolower($method_description));

        return response()->json([
            'message' => 'Verification code sent to phone.',
            'code'    => $user->two_factor_code, // Apenas para testes; remova em produção
            'id'      => $user->id, // Apenas para testes; remova em produção
            'hash'    => sha1($user->email) // Apenas para testes; remova em produção
        ], 200);
    }
}
