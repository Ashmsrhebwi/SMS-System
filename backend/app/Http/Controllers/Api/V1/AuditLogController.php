<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $query = AuditLog::with('user')->latest('created_at');

        // Full-text search across action, entity_type, ip_address, and user name
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('entity_type', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        // Action category filter (prefix match — create, update, delete, login…)
        if ($action = $request->get('action')) {
            $query->where('action', 'like', "{$action}%");
        }

        // Entity type filter
        if ($entityType = $request->get('entity_type')) {
            $query->where('entity_type', $entityType);
        }

        // User filter
        if ($userId = $request->get('user_id')) {
            $query->where('user_id', $userId);
        }

        // Date range
        if ($dateFrom = $request->get('date_from')) {
            $query->where('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->where('created_at', '<=', $dateTo . ' 23:59:59');
        }

        $logs = $query->paginate(min($request->integer('per_page', 50), 200));

        return AuditLogResource::collection($logs)->additional([
            'meta_extra' => [
                'action_categories' => ['create', 'update', 'delete', 'login', 'logout', 'send', 'import', 'export', 'security', 'bulk'],
            ],
        ])->response();
    }
}
