<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Message;
use App\Services\SmsSegmentCalculator;
use Illuminate\Support\Facades\DB;

class CostReportController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $symbol = config('sms.currency_symbol', '$');

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
        ->paginate(30);

        $summary = [
            'total_messages'  => Message::whereIn('status', ['sent', 'delivered', 'failed', 'undelivered'])->count(),
            'total_segments'  => Message::sum('sms_segments'),
            'total_cost'      => (float) Message::sum('cost'),
            'cost_today'      => (float) Message::whereDate('created_at', today())->sum('cost'),
            'cost_this_month' => (float) Message::whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)->sum('cost'),
        ];

        return view('reports.costs', compact('campaigns', 'summary', 'symbol'));
    }
}
