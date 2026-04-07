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

        event(new \Illuminate\Auth\Events\Registered($user));

        return response()->json([
            'message' => 'Utilisateur enregistré avec succès. Veuillez vérifier votre e-mail pour confirmer votre compte.',
            'user'    => new UserResource($user),
        ], 201);
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

        // Check if email is verified
        if (! $user->hasVerifiedEmail()) {
            $guard->logout(); // Logout to invalidate the token we just got

            return response()->json([
                'error' => 'Email not verified',
                'message' => 'Votre adresse e-mail n\'est pas encore vérifiée. Veuillez consulter votre boîte de réception pour le lien de confirmation.',
                'needs_verification' => true,
            ], 403);
        }

        // Reset login attempts on successful login
        $user->update(['login_attempts' => 0]);


        return $this->respondWithTokenAndUser($token, $user);
    }

    /**
     * @OA\Post(
     *     path="/api/verification/resend",
     *     summary="Resend verification email",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Verification link sent"),
     *     @OA\Response(response=404, description="User not found"),
     *     @OA\Response(response=400, description="Email already verified")
     * )
     */
    public function resendVerificationNotification(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json(['message' => 'Utilisateur non trouvé.'], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Votre adresse e-mail est déjà vérifiée.'], 400);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Le lien de confirmation a été renvoyé.']);
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
    public function profile(Request $request): UserResource
    {
        $user = auth('api')->user();

        if ($request->isMethod('post') && $request->has('name')) {
            $request->validate([
                'name' => 'required|string|max:255',
            ]);
            $user->update(['name' => $request->name]);
        }

        return new UserResource($user);
    }

    /**
     * @OA\Post(
     *     path="/api/profile/password",
     *     summary="Change authenticated user's password",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"current_password","password","password_confirmation"},
     *             @OA\Property(property="current_password", type="string"),
     *             @OA\Property(property="password", type="string", minLength=8),
     *             @OA\Property(property="password_confirmation", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Password changed successfully"),
     *     @OA\Response(response=422, description="Validation error or wrong current password"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password'      => 'required|string',
            'password'              => 'required|string|min:8|confirmed',
        ]);

        /** @var \App\Models\User $user */
        $user = auth('api')->user();

        if (!\Illuminate\Support\Facades\Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'The current password is incorrect.',
                'errors'  => ['current_password' => ['The current password is incorrect.']],
            ], 422);
        }

        $user->update(['password' => bcrypt($request->password)]);

        return response()->json(['message' => 'Password changed successfully']);
    }

    /**
     * @OA\Post(
     *     path="/api/profile/avatar",
     *     summary="Upload a new avatar for the authenticated user",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="avatar", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Avatar updated successfully"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
        ]);

        /** @var \App\Models\User $user */
        $user = auth('api')->user();

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $url = asset('storage/' . $path);
            
            $user->update(['avatar' => $url]);

            return response()->json([
                'message' => 'Avatar updated successfully',
                'avatar'  => $url
            ]);
        }

        return response()->json(['message' => 'Failed to upload image'], 400);
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
