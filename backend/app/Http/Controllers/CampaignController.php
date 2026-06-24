<?php

namespace App\Http\Controllers;

use App\Exports\CampaignReportExport;
use App\Jobs\DispatchCampaignJob;
use App\Jobs\SendSmsJob;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Message;
use App\Models\Segment;
use App\Models\SmsTemplate;
use App\Services\AuditLogger;
use App\Services\SegmentService;
use App\Services\SmsSegmentCalculator;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class CampaignController extends Controller
{
    public function __construct(private SegmentService $segmentService) {}

    public function index()
    {
        $this->authorize('viewAny', Campaign::class);
        $campaigns = Campaign::with('segment')->latest()->paginate(20);
        return view('campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        $this->authorize('create', Campaign::class);

        $eligibleCount = $this->segmentService->countEligible(null);
        $segments  = Segment::orderBy('name')->get();
        $templates = SmsTemplate::with('category')->orderBy('name')->get();

        return view('campaigns.create', compact('eligibleCount', 'segments', 'templates'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Campaign::class);

        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'message_body' => 'required|string|max:1600',
            'scheduled_at' => 'nullable|date|after:now',
            'send_now'     => 'boolean',
            'segment_id'   => 'nullable|exists:segments,id',
        ]);

        $campaign = Campaign::create([
            'name'         => $data['name'],
            'message_body' => $data['message_body'],
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'status'       => $data['scheduled_at'] ? 'scheduled' : 'draft',
            'segment_id'   => $data['segment_id'] ?? null,
            'created_by'   => auth()->id(),
        ]);

        AuditLogger::log('create_campaign', $campaign, null, $campaign->only(['name', 'status']));

        if ($request->boolean('send_now')) {
            DispatchCampaignJob::dispatch($campaign->id);
            AuditLogger::log('send_campaign', $campaign, null, ['trigger' => 'send_now']);
            return redirect()->route('campaigns.show', $campaign)->with('success', 'Campaign is being dispatched!');
        }

        if ($data['scheduled_at']) {
            DispatchCampaignJob::dispatch($campaign->id)->delay(\Carbon\Carbon::parse($data['scheduled_at']));
            return redirect()->route('campaigns.show', $campaign)->with('success', 'Campaign scheduled successfully.');
        }

        return redirect()->route('campaigns.show', $campaign)->with('success', 'Campaign saved as draft.');
    }

    public function show(Campaign $campaign)
    {
        $this->authorize('view', $campaign);

        $messages = $campaign->messages()
            ->with(['contact' => fn($q) => $q->withTrashed(), 'click'])
            ->latest()
            ->paginate(50);

        $stats = [
            'total'     => $campaign->messages()->count(),
            'pending'   => $campaign->messages()->where('status', 'pending')->count(),
            'queued'    => $campaign->messages()->where('status', 'queued')->count(),
            'sent'      => $campaign->messages()->where('status', 'sent')->count(),
            'delivered' => $campaign->messages()->where('status', 'delivered')->count(),
            'failed'    => $campaign->messages()->whereIn('status', ['failed', 'undelivered'])->count(),
            'clicked'   => $campaign->click_count,
        ];

        $total = max($stats['total'], 1);
        $rates = [
            'delivery' => $stats['total'] > 0 ? round(($stats['delivered'] / $total) * 100, 1) : 0,
            'failure'  => $stats['total'] > 0 ? round(($stats['failed'] / $total) * 100, 1) : 0,
            'click'    => $stats['delivered'] > 0 ? round(($stats['clicked'] / $stats['delivered']) * 100, 1) : 0,
            'opt_out'  => 0,
        ];

        $costStats = [
            'total_cost'     => (float) $campaign->messages()->sum('cost'),
            'total_segments' => (int) $campaign->messages()->sum('sms_segments'),
            'symbol'         => config('sms.currency_symbol', '$'),
        ];

        return view('campaigns.show', compact('campaign', 'messages', 'stats', 'rates', 'costStats'));
    }

    public function sendNow(Campaign $campaign)
    {
        $this->authorize('send', $campaign);

        if (!in_array($campaign->status, ['draft', 'scheduled'])) {
            return back()->with('error', 'Campaign cannot be sent in its current state.');
        }

        DispatchCampaignJob::dispatch($campaign->id);
        AuditLogger::log('send_campaign', $campaign);
        return redirect()->route('campaigns.show', $campaign)->with('success', 'Campaign is being dispatched!');
    }

    public function resendFailed(Campaign $campaign)
    {
        $this->authorize('send', $campaign);

        $failed = $campaign->messages()
            ->whereIn('status', ['failed', 'undelivered'])
            ->with(['contact' => fn($q) => $q->withTrashed()])
            ->get();

        if ($failed->isEmpty()) {
            return back()->with('error', 'No failed messages to resend.');
        }

        $resent = 0;
        foreach ($failed as $message) {
            // Skip if contact is soft-deleted or opted out
            if (!$message->contact || !$message->contact->opted_in) continue;

            $message->update([
                'status'        => 'pending',
                'error_code'    => null,
                'error_message' => null,
                'resend_count'  => $message->resend_count + 1,
            ]);

            SendSmsJob::dispatch($message->id)->delay(now()->addSeconds($resent));
            $resent++;
        }

        AuditLogger::log('resend_failed_messages', $campaign, null, ['resent' => $resent]);

        return back()->with('success', "Re-queued {$resent} failed message(s) for delivery.");
    }

    public function duplicate(Campaign $campaign)
    {
        $this->authorize('duplicate', $campaign);

        $clone                    = $campaign->replicate();
        $clone->name              = $campaign->name . ' (Copy)';
        $clone->status            = 'draft';
        $clone->scheduled_at      = null;
        $clone->total_recipients  = 0;
        $clone->created_by        = auth()->id();
        $clone->save();

        AuditLogger::log('duplicate_campaign', $clone, null, ['source_id' => $campaign->id]);

        return redirect()->route('campaigns.show', $clone)
            ->with('success', 'Campaign duplicated. Review and send when ready.');
    }

    public function exportReport(Campaign $campaign)
    {
        $this->authorize('export', $campaign);
        $filename = 'campaign-' . $campaign->id . '-report-' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new CampaignReportExport($campaign), $filename);
    }

    public function destroy(Campaign $campaign)
    {
        $this->authorize('delete', $campaign);

        if ($campaign->status === 'sending') {
            return back()->with('error', 'Cannot delete a campaign that is currently sending.');
        }

        AuditLogger::log('delete_campaign', $campaign, $campaign->only(['name', 'status']));
        $campaign->delete();
        return redirect()->route('campaigns.index')->with('success', 'Campaign deleted.');
    }
}
