<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Resources\UserResource;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/register",
     *     summary="Register a new user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "password_confirmation"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", minLength=8, example="Secret@123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="Secret@123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="user", ref="#/components/schemas/User"),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=3600)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation Error"
     *     )
     * )
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->password, // Managed by 'hashed' cast in User model
        ]);

        $token = auth('api')->login($user);

        return $this->respondWithTokenAndUser($token, $user);
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Login user and return JWT token",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="secret")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful login",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="user", ref="#/components/schemas/User"),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=3600)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        $user = User::where('email', $request->email)->first();

        // Check if user is blocked
        if ($user && $user->blocked_at) {
            return response()->json([
                'error' => 'Account blocked',
                'message' => 'Blocked',
                'is_blocked' => true,
            ], 403);
        }

        // Check if email is verified
        // if ($user && ! $user->email_verified_at) {
        //     return response()->json([
        //         'error' => 'Email not verified',
        //         'message' => 'Veuillez vérifier votre adresse e-mail avant de vous connecter.',
        //         'needs_verification' => true,
        //     ], 403);
        // }

        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        if (! $token = $guard->attempt($credentials)) {
            if ($user) {
                $user->increment('login_attempts');
                $attempts = $user->login_attempts;

                if ($attempts >= 5) {
                    $user->update(['blocked_at' => now()]);

                    return response()->json([
                        'error' => 'Account blocked',
                        'message' => 'Votre compte est désormais bloqué suite à 5 tentatives infructueuses. Veuillez réinitialiser votre mot de passe.',
                        'is_blocked' => true,
                    ], 403);
                }

                if ($attempts >= 3) {
                    $remaining = 5 - $attempts;

                    return response()->json([
                        'error' => 'Unauthorized',
                        'message' => "Identifiants incorrects. Il vous reste $remaining tentatives.",
                        'attempts_remaining' => $remaining,
                    ], 401);
                }
            }

            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Identifiants incorrects.',
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user = $guard->user();

        // Reset login attempts on successful login
        $user->update(['login_attempts' => 0]);


        return $this->respondWithTokenAndUser($token, $user);
    }


    /**
     * @OA\Post(
     *     path="/api/profile",
     *     summary="Get user profile",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function profile(): UserResource
    {
        return new UserResource(auth('api')->user());
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     summary="Logout user (invalidate token)",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successfully logged out"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function logout()
    {
        auth('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * @OA\Post(
     *     path="/api/refresh",
     *     summary="Refresh JWT token",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Token refreshed",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="user", ref="#/components/schemas/User"),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=3600)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
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

    protected function respondWithTokenAndUser($token, $user): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'user'         => new UserResource($user),
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60,
        ]);
    }
}
