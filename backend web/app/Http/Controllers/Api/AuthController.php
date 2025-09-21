<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService,
        private AuditService $auditService
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $key = 'login_attempts:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'message' => 'Too many login attempts. Please try again later.',
                'code' => 'RATE_LIMIT_EXCEEDED'
            ], 429);
        }

        try {
            $result = $this->authService->login($request->validated());

            RateLimiter::clear($key);

            $this->auditService->logLoginAttempt(
                $request->email,
                true,
                $request->ip(),
                $request->userAgent()
            );

            return response()->json($result);

        } catch (\Exception $e) {
            RateLimiter::hit($key, 60);

            $this->auditService->logLoginAttempt(
                $request->email,
                false,
                $request->ip(),
                $request->userAgent()
            );

            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'AUTHENTICATION_FAILED'
            ], 401);
        }
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request->validated());

            return response()->json($result, 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Registration failed',
                'errors' => ['email' => [$e->getMessage()]],
                'code' => 'REGISTRATION_FAILED'
            ], 422);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $this->authService->logout($request->user());

            return response()->json(null, 204);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Logout failed',
                'code' => 'LOGOUT_FAILED'
            ], 500);
        }
    }

    public function refresh(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json(['token' => $token]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Token refresh failed',
                'code' => 'TOKEN_REFRESH_FAILED'
            ], 401);
        }
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $key = 'forgot_password:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            return response()->json([
                'message' => 'Too many password reset attempts. Please try again later.',
                'code' => 'RATE_LIMIT_EXCEEDED'
            ], 429);
        }

        $status = Password::sendResetLink($request->only('email'));

        RateLimiter::hit($key, 300); // 5 minutes

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Password reset link sent'
            ]);
        }

        return response()->json([
            'message' => 'Unable to send password reset link',
            'code' => 'PASSWORD_RESET_FAILED'
        ], 422);
    }

    public function verifyTwoFactor(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        try {
            $user = \App\Models\User::findOrFail($request->user_id);

            if ($this->authService->verify2FA($user, $request->code)) {
                $token = $user->createToken('auth-token')->plainTextToken;

                return response()->json([
                    'token' => $token,
                    'user' => $user->load('organization'),
                ]);
            }

            return response()->json([
                'message' => 'Invalid two-factor authentication code',
                'code' => '2FA_INVALID'
            ], 401);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Two-factor authentication failed',
                'code' => '2FA_FAILED'
            ], 401);
        }
    }
}