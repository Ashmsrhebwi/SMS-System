<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Message::with([
            'contact:id,name,phone,country,language',
            'campaign:id,name,created_by',
            'campaign.creator:id,name',
            'click',
        ])->latest('created_at');

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('twilio_sid', 'like', "%{$search}%")
                  ->orWhere('error_code', 'like', "%{$search}%")
                  ->orWhereHas('contact', fn($c) => $c->where('name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%"))
                  ->orWhereHas('campaign', fn($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        // Filters
        if ($status = $request->get('status')) {
            $statuses = is_array($status) ? $status : explode(',', $status);
            $query->whereIn('status', $statuses);
        }
        if ($campaignId = $request->get('campaign_id')) {
            $query->where('campaign_id', $campaignId);
        }
        if ($errorCode = $request->get('error_code')) {
            $query->where('error_code', $errorCode);
        }
        if ($dateFrom = $request->get('date_from')) {
            $query->where('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->where('created_at', '<=', $dateTo . ' 23:59:59');
        }
        if ($request->get('clicked') === '1') {
            $query->whereHas('click', fn($q) => $q->where('click_count', '>', 0));
        }
        if ($request->get('clicked') === '0') {
            $query->where(fn($q) => $q->doesntHave('click')
                ->orWhereHas('click', fn($c) => $c->where('click_count', 0)));
        }

        $messages = $query->paginate(min($request->integer('per_page', 50), 200));
        $isAdmin  = auth()->user()->isAdmin();

        $items = $messages->getCollection()->map(fn($msg) => $this->format($msg, $isAdmin));
        $messages->setCollection($items);

        return response()->json($messages);
    }

    public function show(Message $message): JsonResponse
    {
        $message->load([
            'contact:id,name,phone,email,country,language,opted_in',
            'campaign:id,name,status,total_recipients,created_by',
            'campaign.creator:id,name',
            'click',
        ]);

        $isAdmin = auth()->user()->isAdmin();

        return response()->json($this->formatDetail($message, $isAdmin));
    }

    private function format(Message $msg, bool $isAdmin): array
    {
        $contact = $msg->contact;
        $click   = $msg->click;

        return [
            'id'            => $msg->id,
            'twilio_sid'    => $msg->twilio_sid,
            'status'        => $msg->status,
            'error_code'    => $msg->error_code,
            'error_message' => $msg->error_message,
            'cost'          => $msg->cost,
            'sms_segments'  => $msg->sms_segments,
            'resend_count'  => $msg->resend_count,
            'sent_at'       => $msg->sent_at?->toISOString(),
            'delivered_at'  => $msg->delivered_at?->toISOString(),
            'created_at'    => $msg->created_at->toISOString(),
            'updated_at'    => $msg->updated_at->toISOString(),
            'campaign'      => $msg->campaign ? [
                'id'           => $msg->campaign->id,
                'name'         => $msg->campaign->name,
                'created_by'   => $msg->campaign->creator?->name,
            ] : null,
            'contact' => $contact ? [
                'id'      => $contact->id,
                'name'    => $contact->name,
                'phone'   => $isAdmin ? $contact->phone : $this->maskPhone($contact->phone),
                'country' => $contact->country,
                'language'=> $contact->language,
            ] : null,
            'click' => $click ? [
                'click_count'      => $click->click_count,
                'first_clicked_at' => $click->first_clicked_at?->toISOString(),
                'token'            => $isAdmin ? $click->token : null,
                'target_url'       => $isAdmin ? $click->target_url : null,
            ] : null,
        ];
    }

    private function formatDetail(Message $msg, bool $isAdmin): array
    {
        $base    = $this->format($msg, $isAdmin);
        $contact = $msg->contact;

        $base['message_body'] = $msg->message_body;

        if ($contact && $isAdmin) {
            $base['contact']['email']    = $contact->email;
            $base['contact']['opted_in'] = $contact->opted_in;
        }

        // Build delivery timeline from available timestamps
        $timeline = [];
        $timeline[] = ['event' => 'created',   'at' => $msg->created_at->toISOString(),     'note' => 'Message record created'];
        if ($msg->sent_at)      $timeline[] = ['event' => 'sent',       'at' => $msg->sent_at->toISOString(),      'note' => 'Submitted to Twilio'];
        if ($msg->delivered_at) $timeline[] = ['event' => 'delivered',  'at' => $msg->delivered_at->toISOString(), 'note' => 'Delivered to handset'];
        if ($msg->click?->first_clicked_at) {
            $timeline[] = ['event' => 'clicked', 'at' => $msg->click->first_clicked_at->toISOString(), 'note' => "Link clicked {$msg->click->click_count}×"];
        }
        if (in_array($msg->status, ['failed', 'undelivered'])) {
            $timeline[] = ['event' => 'failed', 'at' => $msg->updated_at->toISOString(), 'note' => $msg->error_message ?? 'Delivery failed'];
        }

        $base['timeline'] = $timeline;

        return $base;
    }

    private function maskPhone(?string $phone): ?string
    {
        if (!$phone) return null;
        $len = strlen($phone);
        if ($len <= 7) return str_repeat('*', $len);
        return substr($phone, 0, 5) . str_repeat('*', max(4, $len - 7)) . substr($phone, -2);
    }
}
