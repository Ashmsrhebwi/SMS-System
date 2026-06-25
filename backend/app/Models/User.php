<?php

namespace App\Models;

use App\Notifications\PasswordResetNotification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

// Relationship model imports
use App\Models\Campaign;
use App\Models\SmsTemplate;
use App\Models\ContactNote;
use App\Models\ContactActivity;
use App\Models\SuppressionList;
use App\Models\AuditLog;
use App\Models\SecurityLog;
use App\Models\LoginOtp;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'created_by');
    }

    public function smsTemplates(): HasMany
    {
        return $this->hasMany(SmsTemplate::class, 'created_by');
    }

    public function contactNotes(): HasMany
    {
        return $this->hasMany(ContactNote::class);
    }

    public function contactActivities(): HasMany
    {
        return $this->hasMany(ContactActivity::class);
    }

    public function suppressionEntries(): HasMany
    {
        return $this->hasMany(SuppressionList::class, 'added_by');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function securityLogs(): HasMany
    {
        return $this->hasMany(SecurityLog::class);
    }

    public function loginOtps(): HasMany
    {
        return $this->hasMany(LoginOtp::class);
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new PasswordResetNotification($token));
    }
}
