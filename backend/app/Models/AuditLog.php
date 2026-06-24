<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'action', 'entity_type', 'entity_id',
        'old_values', 'new_values', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'old_values'  => 'array',
        'new_values'  => 'array',
        'created_at'  => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function badgeColor(): string
    {
        return match (true) {
            str_contains($this->action, 'delete') || str_contains($this->action, 'logout') => 'red',
            str_contains($this->action, 'create') || str_contains($this->action, 'login')  => 'emerald',
            str_contains($this->action, 'update') || str_contains($this->action, 'edit')   => 'amber',
            str_contains($this->action, 'send')                                             => 'indigo',
            default                                                                         => 'slate',
        };
    }
}
