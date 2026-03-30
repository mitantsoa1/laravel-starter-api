<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/auth/google/url",
     *     summary="Get Google Auth URL",
     *     description="Returns the URL to redirect the user to for Google Authentication.",
     *     tags={"Auth"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="url", type="string", example="https://accounts.google.com/o/oauth2/auth?client_id=...")
     *         )
     *     )
     * )
     */
    public function redirectToGoogle()
    {
        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver('google');

        // Force the redirect URL from config to ensure it's passed correctly
        $redirectUrl = config('services.google.redirect');

        \Illuminate\Support\Facades\Log::info('Redirect URL configured in Laravel: ' . $redirectUrl);

        return response()->json([
            'url' => $driver->redirectUrl($redirectUrl)->stateless()->redirect()->getTargetUrl(),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/auth/google/callback",
     *     summary="Handle Google Auth Callback",
     *     description="Exchanges the authorization code for a JWT token.",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code"},
     *             @OA\Property(property="code", type="string", description="The authorization code returned by Google")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=3600),
     *             @OA\Property(property="user", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid request"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
            $driver = Socialite::driver('google');

            // Explicitly set the redirect URL
            $redirectUrl = config('services.google.redirect');
            $socialUser = $driver->redirectUrl($redirectUrl)->stateless()->user();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid credentials provided.', 'message' => $e->getMessage()], 422);
        }

        $user = User::where('google_id', $socialUser->getId())
            ->orWhere('email', $socialUser->getEmail())
            ->first();

        if ($user) {
            $changed = false;
            if (!$user->google_id) {
                $user->google_id = $socialUser->getId();
                $changed = true;
            }
            if (!$user->avatar && $socialUser->getAvatar()) {
                $user->avatar = $socialUser->getAvatar();
                $changed = true;
            }
            if ($changed) {
                $user->save();
            }
        } else {
            $user = User::create([
                'name' => $socialUser->getName(),
                'email' => $socialUser->getEmail(),
                'google_id' => $socialUser->getId(),
                'avatar' => $socialUser->getAvatar(),
                'password' => Hash::make(Str::random(16)),
            ]);

            // Send Welcome Email to the user
            // $user->notify(new \App\Notifications\WelcomeNotification($user));
        }

        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = auth('api');

        if (!$token = $guard->login($user)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Generate a temporary one-time code
        $authCode = Str::random(64);

        // Store the token in cache for 2 minutes
        \Illuminate\Support\Facades\Cache::put('social_auth_' . $authCode, $token, 120);

        // Redirect to Frontend with Code
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');

        return redirect($frontendUrl . '/en/callback?code=' . $authCode);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/social/exchange",
     *     summary="Exchange temporary code for JWT token",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code"},
     *             @OA\Property(property="code", type="string", description="Temporary code returned from callback")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token exchanged successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="bearer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Missing or invalid code"
     *     )
     * )
     */
    public function exchangeCodeForToken(Request $request)
    {
        $code = $request->input('code');

        if (!$code) {
            return response()->json(['error' => 'Missing code'], 400);
        }

        $token = \Illuminate\Support\Facades\Cache::get('social_auth_' . $code);

        if (!$token) {
            return response()->json(['error' => 'Invalid or expired code'], 400);
        }

        // Remove from cache (Consume once)
        \Illuminate\Support\Facades\Cache::forget('social_auth_' . $code);

        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
        ]);
    }
}
