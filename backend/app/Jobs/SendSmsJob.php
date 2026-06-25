<?php

namespace App\Jobs;

use App\Models\GlobalBlacklist;
use App\Models\Message;
use App\Models\OptOut;
use App\Models\SuppressionList;
use App\Services\ActivityLogger;
use App\Services\CampaignCompletionService;
use App\Services\SmsSegmentCalculator;
use App\Services\TwilioService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Only 1 try — we mark the message failed ourselves and don't want wasteful retries.
     * Transient Twilio errors are handled by the resend-failed feature.
     */
    public int $tries   = 1;
    public int $timeout = 30;

    public function __construct(private int $messageId) {}

    public function handle(TwilioService $twilio, CampaignCompletionService $completion): void
    {
        $message = Message::with(['contact', 'campaign'])->findOrFail($this->messageId);

        if ($message->status !== 'pending') {
            return;
        }

        $contact = $message->contact;

        // Re-check compliance at actual send time — contact may have opted out since queuing
        if (!$contact) {
            $this->markFailed($message, 'Contact record not found', $completion);
            return;
        }

        if (!$contact->opted_in) {
            $this->markFailed($message, 'Contact has opted out', $completion);
            return;
        }

        if (OptOut::where('phone', $contact->phone)->exists()) {
            $this->markFailed($message, 'Phone is in opt-out list', $completion);
            return;
        }

        if (GlobalBlacklist::where('phone', $contact->phone)->exists()) {
            $this->markFailed($message, 'Phone is globally blacklisted', $completion);
            return;
        }

        if (SuppressionList::where('phone', $contact->phone)->exists()) {
            $this->markFailed($message, 'Phone is in suppression list', $completion);
            return;
        }

        try {
            $statusCallbackUrl = route('webhooks.twilio.status');
            $sid = $twilio->sendMessage(
                $contact->phone,
                $message->message_body,
                $statusCallbackUrl
            );

            $segments = SmsSegmentCalculator::segments($message->message_body);
            $cost     = SmsSegmentCalculator::cost($message->message_body);

            $message->update([
                'twilio_sid'   => $sid,
                'status'       => 'queued',
                'sent_at'      => now(),
                'sms_segments' => $segments,
                'cost'         => $cost,
            ]);

            ActivityLogger::smsSent($message);

        } catch (\Twilio\Exceptions\RestException $e) {
            // Twilio-specific error — log the code, not the full message (may contain credentials)
            $reason = "Twilio error {$e->getStatusCode()}: {$e->getMessage()}";
            Log::warning('SMS send failed — Twilio error', [
                'message_id'   => $this->messageId,
                'twilio_code'  => $e->getStatusCode(),
                'error'        => $e->getMessage(),
            ]);
            $this->markFailed($message, $reason, $completion);

        } catch (\Exception $e) {
            // Non-Twilio exception (network, config, etc.)
            Log::error('SMS send failed — unexpected error', [
                'message_id' => $this->messageId,
                'error'      => $e->getMessage(),
            ]);
            $this->markFailed($message, 'Send error: ' . $e->getMessage(), $completion);

            // Do NOT re-throw — failure is recorded. No retry needed.
        }
    }

    private function markFailed(Message $message, string $reason, CampaignCompletionService $completion): void
    {
        $message->update([
            'status'        => 'failed',
            'error_message' => $reason,
        ]);

        // Since this message will never receive a Twilio webhook, check campaign completion now
        $completion->checkAndComplete($message->campaign_id);
    }
}
