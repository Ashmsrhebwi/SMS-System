<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
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

        $stats = Cache::remember('report_countries_stats', 3600, function () {
            $contacts = \App\Models\Contact::select('phone')->get();
            $byCountry = [];

            foreach ($contacts as $contact) {
                $country = CountryDetectorService::detect($contact->phone);
                if (!isset($byCountry[$country])) {
                    $byCountry[$country] = [
                        'country'       => $country,
                        'contacts'      => 0,
                        'messages_sent' => 0,
                        'delivered'     => 0,
                        'failed'        => 0,
                    ];
                }
                $byCountry[$country]['contacts']++;
            }

            $messageCounts = Message::join('contacts', 'messages.contact_id', '=', 'contacts.id')
                ->selectRaw('contacts.phone, messages.status')
                ->whereNotNull('contacts.phone')
                ->get();

            foreach ($messageCounts as $msg) {
                $country = CountryDetectorService::detect($msg->phone);
                if (!isset($byCountry[$country])) {
                    $byCountry[$country] = ['country' => $country, 'contacts' => 0, 'messages_sent' => 0, 'delivered' => 0, 'failed' => 0];
                }
                if (in_array($msg->status, ['sent', 'delivered', 'failed', 'undelivered', 'queued'])) {
                    $byCountry[$country]['messages_sent']++;
                }
                if ($msg->status === 'delivered') {
                    $byCountry[$country]['delivered']++;
                }
                if (in_array($msg->status, ['failed', 'undelivered'])) {
                    $byCountry[$country]['failed']++;
                }
            }

            return collect(array_values($byCountry))
                ->map(function ($row) {
                    $row['delivery_rate'] = $row['messages_sent'] > 0
                        ? round(($row['delivered'] / $row['messages_sent']) * 100, 1)
                        : 0;
                    return $row;
                })
                ->sortByDesc('contacts')
                ->values();
        });

        return response()->json([
            'data'          => $stats,
            'top_countries' => $stats->take(10),
        ]);
    }

    public function delivery(Request $request): JsonResponse
    {

        $days = $request->integer('days', 30);

        $daily = Message::selectRaw('DATE(created_at) as date, COUNT(*) as total, SUM(CASE WHEN status="delivered" THEN 1 ELSE 0 END) as delivered, SUM(CASE WHEN status IN ("failed","undelivered") THEN 1 ELSE 0 END) as failed')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json(['data' => $daily]);
    }
}
