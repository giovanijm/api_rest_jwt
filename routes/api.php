<?php

use \App\Http\Controllers\User\RegisterController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ForgotPasswordNotificationController;
use App\Http\Controllers\Auth\PhoneVerificationNotificationController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\Auth\VerifyPhoneController;
use Illuminate\Support\Facades\Route;





// Grouped routes for better organization
Route::group(['middleware' => 'api'], function(): void {
    
    //===> Public routes <===    
    Route::post('user/register', RegisterController::class)->name('register'); // User registration route
    Route::post('auth/login', [AuthController::class, 'login'])->name('login'); // User login route
    Route::post('auth/forgot-password-notification', ForgotPasswordNotificationController::class)->name('password.forgot.notification'); // Forgot password notification route
    Route::post('auth/forgot-password', ForgotPasswordController::class)->name('password.forgot'); // Password reset route


    //===> Protected routes (require authentication) <===
    Route::group(['middleware' => 'auth:api'], function(): void {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('logout'); // User logout route
        Route::post('auth/refresh', [AuthController::class, 'refresh'])->name('refresh'); // Token refresh route
        Route::post('auth/me', [AuthController::class, 'me'])->name('me'); // Get authenticated user details
        Route::post('auth/email/verification-notification', EmailVerificationNotificationController::class)->name('email.verification.notification'); // Resend email verification notification
        Route::post('auth/phone/verification-notification', PhoneVerificationNotificationController::class)->name('phone.verification.notification'); // Resend phone verification notification

        // Dev-only: token debug endpoint (returns current JWT claims). Only register in local/dev or when debug is enabled.
        if (app()->environment(['local', 'development']) || config('app.debug')) {
            Route::get('auth/debug/token', \App\Http\Controllers\Debug\TokenDebugController::class)->name('debug.token');
        }
    });
});

// Route for verifying email addresses, url assigned route name
// Middleware:
// - 'api': Applies API middleware group
// - 'signed': Ensures the URL is signed and valid
// - 'throttle:6,1': Limits to 6 requests per minute to prevent abuse
Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)->middleware(['api', 'signed', 'throttle:6,1'])->name('verification.verify');
Route::post('verify-phone/{user}/{hash}', VerifyPhoneController::class)->middleware(['api'])->name('phone.verification.verify');

