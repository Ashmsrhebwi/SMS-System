<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use App\Services\SecurityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private OtpService     $otpService,
        private SecurityLogger $securityLogger,
    ) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = 'login:' . Str::lower($request->input('email')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => __('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        $user = User::where('email', $request->input('email'))->first();

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            $this->securityLogger->loginFailed($request->input('email'));

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($throttleKey);

        $generated = $this->otpService->generate($user);

        if ($generated === false) {
            $seconds = $this->otpService->remainingRequestSeconds($user);
            throw ValidationException::withMessages([
                'email' => 'Too many OTP requests. Please try again in ' . ceil($seconds / 60) . ' minute(s).',
            ]);
        }

        $this->securityLogger->otpSent($user);

        $request->session()->put('auth.otp_user_id', $user->id);
        $request->session()->put('auth.remember', $request->boolean('remember'));

        return redirect()->route('otp.verify');
    }

    public function destroy(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            $this->securityLogger->logout(Auth::user());
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
