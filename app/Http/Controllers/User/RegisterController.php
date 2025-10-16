<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Controller handling user registration.
 */
class RegisterController extends Controller
{
    /**
     * Handle user registration.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(): JsonResponse
    {
        $validator = Validator::make(request()->all(), [
            'name'          => 'required|string|max:255',
            'email'         => 'required|string|email|max:255|unique:users,email',
            'password'      => 'required|string|min:8|confirmed',
            'role'          => 'required|string|in:admin,merchant,user',
            'phone_number'  => 'required|digits:11',
            'latitude'      => 'required|numeric|between:-90,90',
            'longitude'     => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }
        // normalize phone number to digits only and cast to string
        $phone = (string) preg_replace('/\D+/', '', request()->input('phone_number', ''));

        if (User::where('phone_number', $phone)->exists()) {
            return response()->json(['error' => 'Phone number already in use'], 400);
        }

        $user               = new User;
        $user->name         = request()->name;
        $user->email        = request()->email;
        $user->password     = bcrypt(request()->password);
        $user->role         = request()->role;
        $user->phone_number = $phone;
        $user->save();

        // Set location using raw SQL to utilize PostGIS functions
        $lon = request()->longitude;
        $lat = request()->latitude;
        $user->location = ['lat' => $lat, 'lon' => $lon];

        return response()->json(UserResource::make($user), 201);
    }
}
