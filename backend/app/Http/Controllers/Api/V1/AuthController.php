<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use App\Services\SecurityLogger;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class AuthController extends Controller
{
    public function __construct(
        private OtpService     $otpService,
        private SecurityLogger $securityLogger,
    ) {}

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = 'login:' . Str::lower($request->input('email')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'message' => __('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)]),
            ], 429);
        }

        $user = User::where('email', $request->input('email'))->first();

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            $this->securityLogger->loginFailed($request->input('email'));
            return response()->json(['message' => __('auth.failed')], 401);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Your account has been deactivated.'], 403);
        }

        RateLimiter::clear($throttleKey);

        $generated = $this->otpService->generate($user);

        if ($generated === false) {
            $seconds = $this->otpService->remainingRequestSeconds($user);
            return response()->json([
                'message' => 'Too many OTP requests. Please try again in ' . ceil($seconds / 60) . ' minute(s).',
            ], 429);
        }

        $this->securityLogger->otpSent($user);

        return response()->json([
            'message'      => 'OTP sent to your email address.',
            'requires_otp' => true,
            'otp_token'    => $generated,
            'masked_email' => $this->maskEmail($user->email),
        ]);
    }

    public function otpVerify(Request $request): JsonResponse
    {
        $request->validate([
            'otp'       => ['required', 'string', 'digits:6'],
            'otp_token' => ['required', 'string'],
        ]);

        $otpRecord = \App\Models\LoginOtp::where('session_token', $request->input('otp_token'))
            ->whereNull('used_at')
            ->first();

        if (!$otpRecord) {
            return response()->json(['message' => 'No pending authentication. Please log in again.'], 401);
        }

        $user = $otpRecord->user;
        if (!$user) {
            return response()->json(['message' => 'Session expired. Please log in again.'], 401);
        }

        $result = $this->otpService->verify($user, $request->input('otp'));

        if ($result === 'valid') {
            $this->securityLogger->loginSuccess($user);
            $token = $user->createToken('spa-token')->plainTextToken;

            return response()->json([
                'message' => 'Authentication successful.',
                'token'   => $token,
                'user'    => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $user->role,
                ],
            ]);
        }

        $messages = [
            'invalid'   => 'Invalid OTP code.',
            'expired'   => 'OTP has expired. Please request a new one.',
            'exhausted' => 'Too many failed attempts. Please request a new OTP.',
            'not_found' => 'No active OTP found. Please request a new one.',
        ];

        $this->securityLogger->otpFailed($user, $result);

        return response()->json(['message' => $messages[$result] ?? 'OTP verification failed.'], 422);
    }

    public function otpResend(Request $request): JsonResponse
    {
        $request->validate([
            'otp_token' => ['required', 'string'],
        ]);

        $otpRecord = \App\Models\LoginOtp::where('session_token', $request->input('otp_token'))
            ->whereNull('used_at')
            ->first();

        if (!$otpRecord) {
            return response()->json(['message' => 'No pending authentication. Please log in again.'], 401);
        }

        $user = $otpRecord->user;
        if (!$user) {
            return response()->json(['message' => 'Session expired. Please log in again.'], 401);
        }

        $newToken = $this->otpService->generate($user);

        if ($newToken === false) {
            $seconds = $this->otpService->remainingRequestSeconds($user);
            return response()->json([
                'message' => 'Too many OTP requests. Please try again in ' . ceil($seconds / 60) . ' minute(s).',
            ], 429);
        }

        $this->securityLogger->otpSent($user);

        return response()->json([
            'message'   => 'A new OTP has been sent to your email address.',
            'otp_token' => $newToken,
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        try {
            $user = User::where('email', $request->input('email'))->first();
            if ($user) {
                Password::sendResetLink(['email' => $request->input('email')]);
                $this->securityLogger->passwordResetRequest($request->input('email'));
            }
        } catch (\Throwable) {
            // Never expose SMTP or DB failures — always return success
        }

        return response()->json(['message' => __('passwords.sent')]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
                $this->securityLogger->passwordResetSuccess($user);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => __($status)]);
        }

        return response()->json(['message' => __($status)], 422);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->securityLogger->logout(Auth::user());
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => [
                'id'                => $request->user()->id,
                'name'              => $request->user()->name,
                'email'             => $request->user()->email,
                'role'              => $request->user()->role,
                'email_verified_at' => $request->user()->email_verified_at,
                'is_active'         => $request->user()->is_active,
            ],
        ]);
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);
        $visible = substr($local, 0, min(2, strlen($local)));
        return $visible . str_repeat('*', max(0, strlen($local) - 2)) . '@' . $domain;
    }
}
