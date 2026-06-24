<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityLog extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'event',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    // Event constants
    const EVENT_LOGIN_SUCCESS      = 'login_success';
    const EVENT_LOGIN_FAILED       = 'login_failed';
    const EVENT_OTP_SENT           = 'otp_sent';
    const EVENT_OTP_SUCCESS        = 'otp_success';
    const EVENT_OTP_FAILED         = 'otp_failed';
    const EVENT_OTP_EXPIRED        = 'otp_expired';
    const EVENT_OTP_EXHAUSTED      = 'otp_exhausted';
    const EVENT_LOGOUT             = 'logout';
    const EVENT_PASSWORD_RESET_REQ = 'password_reset_request';
    const EVENT_PASSWORD_RESET_OK  = 'password_reset_success';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
