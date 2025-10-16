<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\ForgotPasswordNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Controller handling forgot password notifications.
 */
class ForgotPasswordNotificationController extends Controller
{
    /**
     * Handle the incoming request to send a forgot password notification.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(): JsonResponse
    {
        $validator = Validator::make(request()->all(), [
            'email' => 'required|string|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = User::where('email', $validator->validated()['email'])->first();

        if (! $user) {
            return response()->json(['message' => 'If that email address is in our system, we have emailed you a password reset link.'], 200);
        }

        $user->regenerateTwoFactorCode("forgot_password");
        $user->notify(new ForgotPasswordNotification());
        
        return response()->json(['message' => 'If that email address is in our system, we have code to reset your password.'], 200);
    }
}
