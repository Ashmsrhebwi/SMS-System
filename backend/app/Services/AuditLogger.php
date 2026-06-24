<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class AuditLogger
{
    public static function log(
        string $action,
        mixed $entity = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $userId = null
    ): void {
        try {
            AuditLog::create([
                'user_id'     => $userId ?? auth()->id(),
                'action'      => $action,
                'entity_type' => $entity instanceof Model ? class_basename($entity) : $entity,
                'entity_id'   => $entity instanceof Model ? $entity->id : null,
                'old_values'  => $oldValues,
                'new_values'  => $newValues,
                'ip_address'  => request()->ip(),
                'user_agent'  => self::truncateUserAgent(request()->userAgent()),
            ]);
        } catch (\Throwable $e) {
            Log::error('Audit logging failed: ' . $e->getMessage(), [
                'action' => $action,
            ]);
        }
    }

    public static function logAuth(string $action, $user): void
    {
        try {
            AuditLog::create([
                'user_id'     => $user?->id,
                'action'      => $action,
                'entity_type' => 'User',
                'entity_id'   => $user?->id,
                'ip_address'  => request()->ip(),
                'user_agent'  => self::truncateUserAgent(request()->userAgent()),
            ]);
        } catch (\Throwable $e) {
            Log::error('Auth audit logging failed: ' . $e->getMessage());
        }
    }

    public static function logSecurity(
        string $action,
        ?int $userId,
        array $context = []
    ): void {
        try {
            AuditLog::create([
                'user_id'     => $userId,
                'action'      => $action,
                'entity_type' => 'Security',
                'entity_id'   => $userId,
                'new_values'  => $context ?: null,
                'ip_address'  => request()->ip(),
                'user_agent'  => self::truncateUserAgent(request()->userAgent()),
            ]);
        } catch (\Throwable $e) {
            Log::error('Security audit logging failed: ' . $e->getMessage());
        }
    }

    private static function truncateUserAgent(?string $ua): ?string
    {
        return $ua ? substr($ua, 0, 500) : null;
    }
}
