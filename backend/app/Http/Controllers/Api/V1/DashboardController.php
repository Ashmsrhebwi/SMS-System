<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Click;
use App\Models\Contact;
use App\Models\GlobalBlacklist;
use App\Models\Message;
use App\Models\SuppressionList;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $symbol         = config('sms.currency_symbol', '$');
        $totalSent      = Message::whereIn('status', ['sent', 'delivered', 'failed', 'undelivered'])->count();
        $totalDelivered = Message::where('status', 'delivered')->count();
        $totalClicked   = Click::where('click_count', '>', 0)->count();

        $totalContacts = Contact::count();
        $optedIn       = Contact::where('opted_in', true)->count();

        $stats = [
            'total_contacts'      => $totalContacts,
            'opted_in'            => $optedIn,
            'opted_out'           => $totalContacts - $optedIn,
            'blacklisted'         => GlobalBlacklist::count(),
            'suppressed'          => SuppressionList::count(),
            'total_campaigns'     => Campaign::count(),
            'active_campaigns'    => Campaign::whereIn('status', ['sending', 'scheduled'])->count(),
            'total_messages_sent' => $totalSent,
            'total_delivered'     => $totalDelivered,
            'total_clicked'       => $totalClicked,
            'delivery_rate'       => $totalSent > 0 ? round(($totalDelivered / $totalSent) * 100, 1) : 0,
            'click_rate'          => $totalDelivered > 0 ? round(($totalClicked / $totalDelivered) * 100, 1) : 0,
            'cost_today'          => (float) Message::whereDate('created_at', today())->sum('cost'),
            'cost_this_month'     => (float) Message::whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)->sum('cost'),
            'cost_total'          => (float) Message::sum('cost'),
            'currency_symbol'     => $symbol,
        ];

        $recentCampaigns = Campaign::latest()->take(5)->get(['id', 'name', 'status', 'total_recipients', 'created_at']);

        $recentActivity = Message::with(['contact' => fn($q) => $q->withTrashed()->select('id', 'name', 'phone'), 'campaign' => fn($q) => $q->select('id', 'name')])
            ->whereIn('status', ['delivered', 'failed', 'undelivered'])
            ->latest('updated_at')
            ->take(8)
            ->get(['id', 'campaign_id', 'contact_id', 'status', 'updated_at']);

        $topCountries = Contact::selectRaw('country, COUNT(*) as count')
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->groupBy('country')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(fn($r) => ['country' => $r->country, 'count' => (int) $r->count])
            ->toArray();

        return response()->json([
            'stats'            => $stats,
            'recent_campaigns' => $recentCampaigns,
            'recent_activity'  => $recentActivity,
            'top_countries'    => $topCountries,
        ]);
    }
}
