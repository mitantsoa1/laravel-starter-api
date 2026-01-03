<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/forgot-password",
     *     summary="Request a password reset token",
     *     tags={"Authentication"},
     *      @OA\Parameter(
     *          name="Accept",
     *          in="header",
     *          required=true,
     *          description="Must be 'application/json'",
     *          @OA\Schema(type="string", default="application/json")
     *      ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset token sent.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="A password reset token has been sent to your email address.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|exists:users',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // We are using the default password broker
        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'A password reset token has been sent to your email address. (Check logs)'])
            : response()->json(['message' => 'Unable to send password reset token.'], 500);
    }

    /**
     * @OA\Post(
     *     path="/api/reset-password",
     *     summary="Reset the password",
     *     tags={"Authentication"},
     *     @OA\Parameter(
     *          name="Accept",
     *          in="header",
     *          required=true,
     *          description="Must be 'application/json'",
     *          @OA\Schema(type="string", default="application/json")
     *      ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password", "password_confirmation", "token"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="new-password"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="new-password"),
     *             @OA\Property(property="token", type="string", example="your-reset-token")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password has been reset successfully.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Your password has been reset successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or invalid token."
     *     )
     * )
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // We are using the default password broker
        $status = Password::reset($request->all(), function ($user, $password) {
            $user->forceFill([
                'password' => \Illuminate\Support\Facades\Hash::make($password)
            ])->setRememberToken(Str::random(60));

            $user->save();
        });

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Your password has been reset successfully.'])
            : response()->json(['message' => 'Invalid token or email.'], 422);
    }
}
