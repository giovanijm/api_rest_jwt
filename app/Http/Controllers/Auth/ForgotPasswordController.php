<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Controller handling forgot password functionality.
 */
class ForgotPasswordController extends Controller
{
    /**
     * Handle the incoming request to reset the user's password.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(): JsonResponse
    {
        $validator = Validator::make(request()->all(), [
            'email'     => 'required|string|email|max:255',
            'code'      => 'required|string|min:6|max:6',
            'password'  => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = User::where('email', $validator->validated()['email'])->first();

        if (! $user) {
            return response()->json(['message' => 'If that email address is in our system, we have code to reset your password.'], 200);
        }

        if ($user->two_factor_code !== $validator->validated()['code'] || $user->two_factor_expires_at < now()) {
            return response()->json(['message' => 'The provided code is invalid or has expired.'], 400);
        }

        $user->clearTwoFactorCode();

            $user->password = bcrypt($validator->validated()['password']);
            $user->save();

        return response()->json(['message' => 'Your password has been reset successfully.'], 200);
    }
}
