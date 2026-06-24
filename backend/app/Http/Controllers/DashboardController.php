<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Click;
use App\Models\Contact;
use App\Models\GlobalBlacklist;
use App\Models\Message;
use App\Services\CountryDetectorService;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $symbol         = config('sms.currency_symbol', '$');
        $totalSent      = Message::whereIn('status', ['sent', 'delivered', 'failed', 'undelivered'])->count();
        $totalDelivered = Message::where('status', 'delivered')->count();
        $totalClicked   = Click::where('click_count', '>', 0)->count();

        $stats = [
            'total_contacts'      => Contact::count(),
            'opted_in'            => Contact::where('opted_in', true)->count(),
            'blacklisted'         => GlobalBlacklist::count(),
            'total_campaigns'     => Campaign::count(),
            'active_campaigns'    => Campaign::whereIn('status', ['sending', 'scheduled'])->count(),
            'total_messages_sent' => $totalSent,
            'total_delivered'     => $totalDelivered,
            'delivery_rate'       => $totalSent > 0 ? round(($totalDelivered / $totalSent) * 100, 1) : 0,
            'click_rate'          => $totalDelivered > 0 ? round(($totalClicked / $totalDelivered) * 100, 1) : 0,

            // Cost stats
            'cost_today'      => $symbol . number_format((float) Message::whereDate('created_at', today())->sum('cost'), 2),
            'cost_this_month' => $symbol . number_format((float) Message::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->sum('cost'), 2),
            'cost_total'      => $symbol . number_format((float) Message::sum('cost'), 2),
        ];

        $recentCampaigns = Campaign::latest()->take(5)->get();

        $recentActivity = Message::with(['contact' => fn($q) => $q->withTrashed(), 'campaign'])
            ->whereIn('status', ['delivered', 'failed', 'undelivered'])
            ->latest('updated_at')
            ->take(8)
            ->get();

        // Top countries (from contacts)
        $topCountries = $this->getTopCountries(5);

        return view('dashboard', compact('stats', 'recentCampaigns', 'recentActivity', 'topCountries', 'symbol'));
    }

    private function getTopCountries(int $limit): array
    {
        $contacts = Contact::select('phone')->limit(500)->get();
        $counts   = [];

        foreach ($contacts as $contact) {
            $country = CountryDetectorService::detect($contact->phone);
            $counts[$country] = ($counts[$country] ?? 0) + 1;
        }

        arsort($counts);
        $top = array_slice($counts, 0, $limit, true);

        return array_map(fn($country, $count) => ['country' => $country, 'count' => $count], array_keys($top), $top);
    }
}
