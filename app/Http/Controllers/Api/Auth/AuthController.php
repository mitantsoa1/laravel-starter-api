<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // Register new user
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'       => 'required|string|max:255',
            'email'      => 'required|string|email|max:255|unique:users',
            'password'   => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => bcrypt($request->password),
        ]);
        $credentials = [
            'email' => $request->email,
            'password' => $request->password,
        ];

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithTokenAndUser($token, auth('api')->user());
        return response()->json(['message' => 'User registered successfully', 'user' => $user], 201);
    }

    // Login user and return JWT token
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithTokenAndUser($token, auth('api')->user());
    }

    // Get user profile
    public function profile()
    {
        return response()->json(auth('api')->user());
    }

    // Logout user (invalidate token)
    public function logout()
    {
        auth('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    // Refresh JWT token
    public function refresh()
    {
        return $this->respondWithTokenAndUser(auth('api')->refresh(), auth('api')->user());
    }

    // Return token response structure
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60,
        ]);
    }

    protected function respondWithTokenAndUser($token, $user)
    {
        return response()->json([
            'access_token' => $token,
            'user' => $user,
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60,
        ]);
    }
}
