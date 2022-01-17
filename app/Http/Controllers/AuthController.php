<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Nette\Schema\ValidationException;


class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                function($attribute, $value, $fail) {
                    if (User::count()>0) {
                        $fail('Sorry, A user has already been created.');
                    }
                }
            ],
            'email' => 'required|string|unique:users,email|email',
            'password' => [
                'required',
                'string',
                Password::defaults(),
                'confirmed'
            ],
        ]);
        $validator->stopOnFirstFailure();

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Failed validation',
                'success' => false
                ]);
        }

        $validated = $validator->validate();

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password'])
            ]);


            $token = $user->createtoken('MyAppToken')->plainTextToken;

            $response = [
                'user' => $user,
                'token' => $token,
                'success' => true
            ];

            return response($response, 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!auth()->attempt($validated)) {
            return response()->json([
                'message' => 'Invalid credentials.',
                'success' => false
            ]);
        }

        $user = auth()->user();

        $token = $user->createtoken('MyAppToken')->plainTextToken;

        $response = [
            'user' => $user,
            'token' => $token,
            'success' => true
        ];

        return response($response, 201);
    }

    public function logout()
    {
        auth()->user()->tokens()->delete();
        return response()->json([
            'message' => 'Logged out successfully.',
            'success' => true
        ]);
    }
}
