<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Enterprise password policy: 12+ chars, mixed case, number, symbol
        Password::defaults(function () {
            return Password::min(12)
                ->mixedCase()
                ->numbers()
                ->symbols();
        });

        RateLimiter::for('auth', fn (Request $r) => app()->environment('local')
            ? Limit::none()
            : Limit::perMinute(5)->by($r->input('email', '') . '|' . $r->ip())
        );
        RateLimiter::for('auth-otp', fn (Request $r) => app()->environment('local')
            ? Limit::none()
            : Limit::perMinute(10)->by($r->ip())
        );

        // Point password reset emails at the React SPA route, not the old Blade route.
        // The SPA ResetPasswordPage reads ?token= and ?email= from the query string.
        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            $isLocal  = app()->environment('local');
            $base     = $isLocal
                ? rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/')
                : rtrim(config('app.url'), '/') . '/spa';
            return $base . '/reset-password'
                . '?token=' . $token
                . '&email=' . urlencode($notifiable->getEmailForPasswordReset());
        });
    }
}
