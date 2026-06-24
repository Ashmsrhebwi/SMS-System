<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\SuppressionList;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuppressionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $query = SuppressionList::with('addedBy:id,name')
            ->latest();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }
        if ($reason = $request->get('reason')) {
            $query->where('reason', 'like', "%{$reason}%");
        }

        $items = $query->paginate($request->integer('per_page', 50));

        $summary = [
            'total'             => SuppressionList::count(),
            'added_today'       => SuppressionList::whereDate('created_at', today())->count(),
            'added_this_week'   => SuppressionList::where('created_at', '>=', now()->startOfWeek())->count(),
            'top_error_code'    => SuppressionList::select('error_code')
                ->whereNotNull('error_code')
                ->groupBy('error_code')
                ->orderByRaw('COUNT(*) DESC')
                ->value('error_code'),
        ];

        return response()->json(['data' => $items, 'summary' => $summary]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $data = $request->validate([
            'phone'  => 'required|string|max:20',
            'name'   => 'nullable|string|max:255',
            'reason' => 'nullable|string|max:255',
        ]);

        $entry = SuppressionList::updateOrCreate(
            ['phone' => $data['phone']],
            [
                'name'           => $data['name'] ?? null,
                'reason'         => $data['reason'] ?? 'Manual suppression',
                'added_by'       => auth()->id(),
                'failure_count'  => 1,
                'last_failed_at' => now(),
            ]
        );

        // Also opt out the contact if they exist
        Contact::where('phone', $data['phone'])->update(['opted_in' => false]);

        AuditLogger::log('add_to_suppression', $entry, null, $data);

        return response()->json($entry, 201);
    }

    public function destroy(SuppressionList $suppressionList): JsonResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        AuditLogger::log('remove_from_suppression', $suppressionList, ['phone' => $suppressionList->phone]);
        $suppressionList->delete();

        return response()->json(['message' => 'Removed from suppression list.']);
    }

    public function stats(): JsonResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $byReason = SuppressionList::selectRaw('reason, COUNT(*) as count')
            ->whereNotNull('reason')
            ->groupBy('reason')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $trend = SuppressionList::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'total'     => SuppressionList::count(),
            'by_reason' => $byReason,
            'trend'     => $trend,
        ]);
    }
}
