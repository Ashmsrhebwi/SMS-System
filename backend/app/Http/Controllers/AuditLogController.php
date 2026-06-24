<?php

namespace App\Http\Controllers;

use App\Exports\AuditLogExport;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $query = AuditLog::with('user')->orderByDesc('created_at');

        if ($userId = $request->get('user_id')) {
            $query->where('user_id', $userId);
        }
        if ($action = $request->get('action')) {
            $query->where('action', 'like', '%' . $action . '%');
        }
        if ($entityType = $request->get('entity_type')) {
            $query->where('entity_type', $entityType);
        }
        if ($from = $request->get('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->get('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $logs  = $query->paginate(50)->withQueryString();
        $users = User::orderBy('name')->get(['id', 'name']);
        $entityTypes = AuditLog::distinct()->orderBy('entity_type')->pluck('entity_type')->filter()->values();

        return view('audit.index', compact('logs', 'users', 'entityTypes'));
    }

    public function export(Request $request)
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        return Excel::download(new AuditLogExport($request->all()), 'audit-log-' . now()->format('Y-m-d') . '.xlsx');
    }
}
