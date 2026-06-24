<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use App\Services\SecurityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OtpController extends Controller
{
    public function __construct(
        private OtpService     $otpService,
        private SecurityLogger $securityLogger,
    ) {}

    public function show(Request $request): View|RedirectResponse
    {
        if (!$request->session()->has('auth.otp_user_id')) {
            return redirect()->route('login');
        }

        $user = User::find($request->session()->get('auth.otp_user_id'));
        if (!$user) {
            $request->session()->forget(['auth.otp_user_id', 'auth.remember']);
            return redirect()->route('login');
        }

        return view('auth.otp-verify', [
            'maskedEmail' => $this->maskEmail($user->email),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        if (!$request->session()->has('auth.otp_user_id')) {
            return redirect()->route('login');
        }

        $request->validate([
            'otp' => ['required', 'string', 'digits:6'],
        ]);

        $user = User::find($request->session()->get('auth.otp_user_id'));
        if (!$user) {
            return redirect()->route('login');
        }

        $result = $this->otpService->verify($user, $request->input('otp'));

        if ($result === 'valid') {
            $remember = $request->session()->pull('auth.remember', false);
            $request->session()->forget('auth.otp_user_id');

            Auth::loginUsingId($user->id, $remember);
            $request->session()->regenerate();

            $this->securityLogger->loginSuccess($user);
            $this->securityLogger->otpSuccess($user);

            return redirect()->intended(route('dashboard', absolute: false));
        }

        $this->securityLogger->otpFailed($user, $result);

        $message = match ($result) {
            'expired'   => 'This OTP has expired. Please request a new one.',
            'exhausted' => 'Too many incorrect attempts. Please request a new OTP.',
            'not_found' => 'No active OTP found. Please request a new one.',
            default     => 'Invalid OTP. Please try again.',
        };

        throw ValidationException::withMessages(['otp' => $message]);
    }

    public function resend(Request $request): RedirectResponse
    {
        if (!$request->session()->has('auth.otp_user_id')) {
            return redirect()->route('login');
        }

        $user = User::find($request->session()->get('auth.otp_user_id'));
        if (!$user) {
            return redirect()->route('login');
        }

        if (!$this->otpService->canRequest($user)) {
            $seconds = $this->otpService->remainingRequestSeconds($user);
            return back()->withErrors([
                'otp' => 'Too many OTP requests. Try again in ' . ceil($seconds / 60) . ' minute(s).',
            ]);
        }

        $this->otpService->generate($user);
        $this->securityLogger->otpSent($user);

        return back()->with('status', 'A new OTP has been sent to your email.');
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email);
        $masked = substr($local, 0, 2) . str_repeat('*', max(0, strlen($local) - 2));
        return $masked . '@' . $domain;
    }
}
