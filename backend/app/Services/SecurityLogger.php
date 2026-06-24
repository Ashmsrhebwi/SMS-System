<?php

namespace App\Services;

use App\Models\SecurityLog;
use App\Models\User;
use Illuminate\Http\Request;

class SecurityLogger
{
    public function __construct(private Request $request) {}

    public function log(
        string $event,
        ?User $user = null,
        ?string $email = null,
        array $metadata = []
    ): void {
        SecurityLog::create([
            'user_id'    => $user?->id,
            'email'      => $email ?? $user?->email,
            'event'      => $event,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'metadata'   => empty($metadata) ? null : $metadata,
        ]);
    }

    public function loginSuccess(User $user): void
    {
        $this->log(SecurityLog::EVENT_LOGIN_SUCCESS, $user);
    }

    public function loginFailed(string $email): void
    {
        $this->log(SecurityLog::EVENT_LOGIN_FAILED, email: $email);
    }

    public function otpSent(User $user): void
    {
        $this->log(SecurityLog::EVENT_OTP_SENT, $user);
    }

    public function otpSuccess(User $user): void
    {
        $this->log(SecurityLog::EVENT_OTP_SUCCESS, $user);
    }

    public function otpFailed(User $user, string $reason): void
    {
        $event = match ($reason) {
            'expired'   => SecurityLog::EVENT_OTP_EXPIRED,
            'exhausted' => SecurityLog::EVENT_OTP_EXHAUSTED,
            default     => SecurityLog::EVENT_OTP_FAILED,
        };
        $this->log($event, $user);
    }

    public function logout(User $user): void
    {
        $this->log(SecurityLog::EVENT_LOGOUT, $user);
    }

    public function passwordResetRequest(string $email): void
    {
        $this->log(SecurityLog::EVENT_PASSWORD_RESET_REQ, email: $email);
    }

    public function passwordResetSuccess(User $user): void
    {
        $this->log(SecurityLog::EVENT_PASSWORD_RESET_OK, $user);
    }
}
