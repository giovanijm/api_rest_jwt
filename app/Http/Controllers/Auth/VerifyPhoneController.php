<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Controller handling phone verification.
 */
class VerifyPhoneController extends Controller
{
    /**
     * Handle the incoming request to verify the user's phone.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @param  string  $hash
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, User $user, string $hash): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Validate that the hash in the URL matches the user's email
        $expected = sha1($user->email);

        if (! hash_equals((string) $hash, $expected)) {
            return response()->json(['message' => 'Invalid verification link.'], 403);
        }

        if ($user->hasVerifiedPhone()) {
            return response()->json(['message' => 'Phone already verified.'], 200);
        }

        $code           = $validator->validated()['code'];
        $method_correct = $user->two_factor_method === 'sms' || $user->two_factor_method === 'whatsapp';

        // Se você armazenou o code em texto:
        if ($user->two_factor_code !== $code || !$method_correct || $user->two_factor_expires_at < now()) {
            return response()->json(['message' => 'Invalid or expired code'], 400);
        }

        // Ou, se armazenou o hash do code:
        // if (! Hash::check($code, $user->two_factor_code_hash)) { ... }

        $user->phone_verified_at = now();
        $user->clearTwoFactorCode();
        $user->save();

        return response()->json(['message' => 'Phone verified'], 200);
    }
}
