<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginOtp;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OtpVerificationController extends Controller
{
    // ─── Show OTP form ────────────────────────────────────────────────────────

    public function create(Request $request): View|RedirectResponse
    {
        $pending = $request->session()->get('auth_pending');

        if (!$pending || !isset($pending['user_id'])) {
            return redirect()->route('login');
        }

        if (time() > ($pending['expires_at'] ?? 0)) {
            $request->session()->forget('auth_pending');
            return redirect()->route('login')
                ->with('status', 'Session expired. Please sign in again.');
        }

        $user = User::find($pending['user_id']);
        if (!$user) {
            $request->session()->forget('auth_pending');
            return redirect()->route('login');
        }

        // Mask email: j***@example.com
        $maskedEmail = $this->maskEmail($user->email);

        return view('auth.otp', compact('maskedEmail'));
    }

    // ─── Verify OTP ───────────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ]);

        $pending = $request->session()->get('auth_pending');

        if (!$pending || !isset($pending['user_id'])) {
            return redirect()->route('login')
                ->with('status', 'Session expired. Please sign in again.');
        }

        if (time() > ($pending['expires_at'] ?? 0)) {
            $request->session()->forget('auth_pending');
            return redirect()->route('login')
                ->with('status', 'Session expired. Please sign in again.');
        }

        $user = User::find($pending['user_id']);

        if (!$user || !$user->is_active) {
            $request->session()->forget('auth_pending');
            return redirect()->route('login');
        }

        // Rate-limit OTP submissions: 10 per minute per user+IP
        $rateLimiterKey = 'otp-verify:' . $user->id . ':' . $request->ip();
        if (RateLimiter::tooManyAttempts($rateLimiterKey, 10)) {
            throw ValidationException::withMessages([
                'otp' => 'Too many verification attempts. Please wait a minute.',
            ]);
        }
        RateLimiter::hit($rateLimiterKey, 60);

        // Find the active OTP record tied to this session token
        $otpRecord = LoginOtp::where('user_id', $user->id)
            ->where('session_token', $pending['session_token'])
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$otpRecord) {
            AuditLogger::logSecurity('otp_invalid_or_expired', $user->id, [
                'reason' => 'no_valid_record',
            ]);
            $request->session()->forget('auth_pending');
            return redirect()->route('login')
                ->with('status', 'Your verification code has expired. Please sign in again.');
        }

        // Check attempt limit (3 per OTP record)
        if ($otpRecord->isExhausted()) {
            AuditLogger::logSecurity('otp_exhausted', $user->id);
            $request->session()->forget('auth_pending');
            return redirect()->route('login')
                ->withErrors(['otp' => 'Maximum attempts reached. Please request a new code.']);
        }

        // Verify the code (timing-safe comparison)
        if (!$otpRecord->verify($request->otp, $user->getAuthPassword())) {
            $otpRecord->increment('attempts');

            $remaining = 3 - $otpRecord->fresh()->attempts;

            AuditLogger::logSecurity('failed_otp', $user->id, [
                'attempts' => $otpRecord->attempts,
                'remaining' => $remaining,
            ]);

            if ($remaining <= 0) {
                $request->session()->forget('auth_pending');
                return redirect()->route('login')
                    ->withErrors(['otp' => 'Maximum attempts reached. Please request a new code.']);
            }

            throw ValidationException::withMessages([
                'otp' => "Incorrect code. {$remaining} attempt(s) remaining.",
            ]);
        }

        // ── OTP is valid ──────────────────────────────────────────────────────

        $otpRecord->update(['used_at' => now()]);
        $request->session()->forget('auth_pending');

        Auth::login($user, $pending['remember'] ?? false);
        $request->session()->regenerate();

        RateLimiter::clear($rateLimiterKey);

        AuditLogger::logAuth('login', $user);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    // ─── Resend OTP ───────────────────────────────────────────────────────────

    public function resend(Request $request): RedirectResponse
    {
        $pending = $request->session()->get('auth_pending');

        if (!$pending || !isset($pending['user_id'])) {
            return redirect()->route('login');
        }

        $user = User::find($pending['user_id']);

        if (!$user) {
            return redirect()->route('login');
        }

        // Rate-limit OTP requests: 5 per hour per email
        $rateLimiterKey = 'otp-request:' . strtolower($user->email);

        if (RateLimiter::tooManyAttempts($rateLimiterKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimiterKey);
            return back()->withErrors([
                'otp' => 'Too many code requests. Please wait ' . ceil($seconds / 60) . ' minute(s).',
            ]);
        }

        // Invalidate all previous unused OTPs for this user
        LoginOtp::where('user_id', $user->id)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->update(['used_at' => now()]);

        // Generate and send new OTP
        [$newSessionToken] = $this->generateAndSendOtp($user, $request);

        // Update session with new token and refreshed expiry
        $request->session()->put('auth_pending', [
            'user_id'       => $user->id,
            'session_token' => $newSessionToken,
            'remember'      => $pending['remember'] ?? false,
            'expires_at'    => now()->addMinutes(10)->timestamp,
        ]);

        RateLimiter::hit($rateLimiterKey, 3600);

        AuditLogger::logSecurity('otp_resent', $user->id);

        return back()->with('status', 'A new verification code has been sent to your email.');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public static function generateAndSendOtp(User $user, Request $request): array
    {
        $otp          = LoginOtp::generateCode();
        $sessionToken = \Illuminate\Support\Str::random(64);
        $expiresAt    = now()->addMinutes(5);

        LoginOtp::create([
            'user_id'       => $user->id,
            'otp_hash'      => LoginOtp::hashCode($otp, $user->getAuthPassword()),
            'session_token' => $sessionToken,
            'expires_at'    => $expiresAt,
            'ip_address'    => $request->ip(),
            'user_agent'    => substr($request->userAgent() ?? '', 0, 500),
        ]);

        \Illuminate\Support\Facades\Mail::to($user->email)
            ->send(new \App\Mail\LoginOtpMail($user, $otp, 5));

        return [$sessionToken, $otp];
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email);
        $masked = substr($local, 0, 1) . str_repeat('*', max(strlen($local) - 2, 2)) . substr($local, -1);
        return $masked . '@' . $domain;
    }
}
