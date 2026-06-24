<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Message;
use App\Services\CountryDetectorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function costs(Request $request): JsonResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $campaigns = Campaign::select([
            'campaigns.id',
            'campaigns.name',
            'campaigns.status',
            'campaigns.created_at',
            DB::raw('COUNT(messages.id) AS message_count'),
            DB::raw('SUM(messages.sms_segments) AS total_segments'),
            DB::raw('SUM(messages.cost) AS total_cost'),
        ])
        ->leftJoin('messages', 'messages.campaign_id', '=', 'campaigns.id')
        ->groupBy('campaigns.id', 'campaigns.name', 'campaigns.status', 'campaigns.created_at')
        ->orderByDesc('campaigns.created_at')
        ->paginate($request->integer('per_page', 30));

        $summary = [
            'total_messages'  => Message::whereIn('status', ['sent', 'delivered', 'failed', 'undelivered'])->count(),
            'total_segments'  => (int) Message::sum('sms_segments'),
            'total_cost'      => (float) Message::sum('cost'),
            'cost_today'      => (float) Message::whereDate('created_at', today())->sum('cost'),
            'cost_this_month' => (float) Message::whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)->sum('cost'),
            'currency_symbol' => config('sms.currency_symbol', '$'),
        ];

        return response()->json(['data' => $campaigns, 'summary' => $summary]);
    }

    public function countries(): JsonResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        // Use stored country column — O(1) per row, no in-memory detection
        $stats = Cache::remember('report_countries_stats_v2', 1800, function () {
            $byCountry = Contact::selectRaw('
                    country,
                    COUNT(*) as contacts,
                    SUM(opted_in) as opted_in_count
                ')
                ->whereNotNull('country')
                ->where('country', '!=', '')
                ->groupBy('country')
                ->orderByDesc('contacts')
                ->get();

            // Delivery stats per country via join
            $deliveryStats = DB::table('messages')
                ->join('contacts', 'messages.contact_id', '=', 'contacts.id')
                ->selectRaw('
                    contacts.country,
                    COUNT(*) as messages_sent,
                    SUM(CASE WHEN messages.status = "delivered" THEN 1 ELSE 0 END) as delivered,
                    SUM(CASE WHEN messages.status IN ("failed","undelivered") THEN 1 ELSE 0 END) as failed
                ')
                ->whereNotNull('contacts.country')
                ->groupBy('contacts.country')
                ->get()
                ->keyBy('country');

            $grandTotal = $byCountry->sum('contacts');

            return $byCountry->map(function ($row) use ($deliveryStats, $grandTotal) {
                $ds = $deliveryStats->get($row->country);
                $msgSent = (int) ($ds?->messages_sent ?? 0);
                return [
                    'country'       => $row->country,
                    'contacts'      => (int) $row->contacts,
                    'opted_in'      => (int) $row->opted_in_count,
                    'percentage'    => $grandTotal > 0 ? round(($row->contacts / $grandTotal) * 100, 1) : 0,
                    'messages_sent' => $msgSent,
                    'delivered'     => (int) ($ds?->delivered ?? 0),
                    'failed'        => (int) ($ds?->failed ?? 0),
                    'delivery_rate' => $msgSent > 0 ? round(($ds->delivered / $msgSent) * 100, 1) : 0,
                ];
            })->values();
        });

        return response()->json([
            'data'          => $stats,
            'top_countries' => $stats->take(10),
            'total_contacts' => $stats->sum('contacts'),
        ]);
    }

    public function languages(): JsonResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $stats = Cache::remember('report_languages_stats', 1800, function () {
            $rows = Contact::selectRaw('language, COUNT(*) as total, SUM(opted_in) as opted_in_count')
                ->whereNotNull('language')
                ->where('language', '!=', '')
                ->groupBy('language')
                ->orderByDesc('total')
                ->get();

            $grandTotal = $rows->sum('total');

            return $rows->map(fn($r) => [
                'language'   => $r->language,
                'total'      => (int) $r->total,
                'opted_in'   => (int) $r->opted_in_count,
                'percentage' => $grandTotal > 0 ? round(($r->total / $grandTotal) * 100, 1) : 0,
            ])->values();
        });

        return response()->json(['data' => $stats, 'total' => $stats->sum('total')]);
    }

    public function delivery(Request $request): JsonResponse
    {
        $days   = min($request->integer('days', 30), 365);
        $period = $request->get('period', 'daily');

        if ($period === 'weekly') {
            $daily = Message::selectRaw('
                    YEARWEEK(created_at, 1) as week_key,
                    MIN(DATE(created_at)) as date,
                    COUNT(*) as total,
                    SUM(CASE WHEN status="delivered" THEN 1 ELSE 0 END) as delivered,
                    SUM(CASE WHEN status IN ("failed","undelivered") THEN 1 ELSE 0 END) as failed,
                    SUM(COALESCE(cost, 0)) as cost
                ')
                ->where('created_at', '>=', now()->subWeeks(12))
                ->groupBy('week_key')
                ->orderBy('week_key')
                ->get();
        } elseif ($period === 'monthly') {
            $daily = Message::selectRaw('
                    DATE_FORMAT(created_at, "%Y-%m") as date,
                    COUNT(*) as total,
                    SUM(CASE WHEN status="delivered" THEN 1 ELSE 0 END) as delivered,
                    SUM(CASE WHEN status IN ("failed","undelivered") THEN 1 ELSE 0 END) as failed,
                    SUM(COALESCE(cost, 0)) as cost
                ')
                ->where('created_at', '>=', now()->subMonths(12))
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        } else {
            $daily = Message::selectRaw('
                    DATE(created_at) as date,
                    COUNT(*) as total,
                    SUM(CASE WHEN status="delivered" THEN 1 ELSE 0 END) as delivered,
                    SUM(CASE WHEN status IN ("failed","undelivered") THEN 1 ELSE 0 END) as failed,
                    SUM(COALESCE(cost, 0)) as cost
                ')
                ->where('created_at', '>=', now()->subDays($days))
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        }

        return response()->json(['data' => $daily]);
    }

    public function summary(): JsonResponse
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $sym = config('sms.currency_symbol', '$');

        $today    = now()->toDateString();
        $weekStart = now()->startOfWeek()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        $dailyStats = $this->periodStats($today, $today);
        $weeklyStats = $this->periodStats($weekStart, $today);
        $monthlyStats = $this->periodStats($monthStart, $today);

        // Contacts trend
        $contactsToday = Contact::whereDate('created_at', today())->count();
        $contactsWeek  = Contact::where('created_at', '>=', now()->startOfWeek())->count();
        $contactsMonth = Contact::where('created_at', '>=', now()->startOfMonth())->count();

        // Campaign trend
        $campaignsToday = Campaign::whereDate('created_at', today())->count();
        $campaignsWeek  = Campaign::where('created_at', '>=', now()->startOfWeek())->count();
        $campaignsMonth = Campaign::where('created_at', '>=', now()->startOfMonth())->count();

        return response()->json([
            'currency_symbol' => $sym,
            'daily'   => array_merge($dailyStats,   ['contacts_added' => $contactsToday,  'campaigns_sent' => $campaignsToday]),
            'weekly'  => array_merge($weeklyStats,  ['contacts_added' => $contactsWeek,   'campaigns_sent' => $campaignsWeek]),
            'monthly' => array_merge($monthlyStats, ['contacts_added' => $contactsMonth,  'campaigns_sent' => $campaignsMonth]),
        ]);
    }

    private function periodStats(string $from, string $to): array
    {
        $row = Message::selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status="delivered" THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status IN ("failed","undelivered") THEN 1 ELSE 0 END) as failed,
                SUM(COALESCE(cost, 0)) as cost
            ')
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->first();

        $total     = (int) ($row->total ?? 0);
        $delivered = (int) ($row->delivered ?? 0);
        $failed    = (int) ($row->failed ?? 0);
        $cost      = (float) ($row->cost ?? 0);

        // Click count
        $clicks = \App\Models\Click::whereHas('message', function ($q) use ($from, $to) {
            $q->whereDate('created_at', '>=', $from)
              ->whereDate('created_at', '<=', $to);
        })->where('click_count', '>', 0)->count();

        return [
            'sent'          => $total,
            'delivered'     => $delivered,
            'failed'        => $failed,
            'clicks'        => $clicks,
            'delivery_rate' => $total > 0 ? round(($delivered / $total) * 100, 1) : 0,
            'click_rate'    => $delivered > 0 ? round(($clicks / $delivered) * 100, 1) : 0,
            'cost'          => $cost,
        ];
    }
}
