<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginOtp extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['user_id', 'otp_hash', 'session_token', 'attempts', 'expires_at', 'used_at', 'ip_address', 'user_agent'];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function isExhausted(): bool
    {
        return $this->attempts >= 3;
    }

    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->isUsed() && !$this->isExhausted();
    }
}
