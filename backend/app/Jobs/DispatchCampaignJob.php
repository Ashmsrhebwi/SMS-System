<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Click;
use App\Models\Message;
use App\Models\Segment;
use App\Services\ActivityLogger;
use App\Services\SegmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DispatchCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 3600; // Allow up to 1 hour for large campaigns

    public function __construct(private int $campaignId) {}

    public function handle(SegmentService $segmentService): void
    {
        $campaign = Campaign::findOrFail($this->campaignId);

        // Accept 'sending' (set atomically by controller) or draft/scheduled
        if (!in_array($campaign->status, ['draft', 'scheduled', 'sending'])) {
            return;
        }

        // Only update to 'sending' if not already in that state (atomic set by controller)
        if (in_array($campaign->status, ['draft', 'scheduled'])) {
            $campaign->update(['status' => 'sending']);
        }

        $segment = $campaign->segment_id ? Segment::find($campaign->segment_id) : null;

        // Count contacts first without loading them all into memory
        $totalCount = $segmentService->countEligible($segment);
        $campaign->update(['total_recipients' => $totalCount]);

        if ($totalCount === 0) {
            $campaign->update(['status' => 'completed']);
            Log::info("Campaign {$this->campaignId} completed — no eligible contacts");
            return;
        }

        $delaySeconds = (int) config('sms.rate_limit_delay', 1);
        $optOutText   = config('sms.opt_out_text', '');
        $hasTracking  = str_contains($campaign->message_body, '{tracking_url}');
        $index        = 0;

        // Process contacts in chunks of 500 to avoid memory exhaustion on large campaigns
        $segmentService->eachEligibleContact($segment, 500, function ($chunk) use (
            $campaign, $optOutText, $hasTracking, $delaySeconds, &$index
        ) {
            foreach ($chunk as $contact) {
                $personalizedBody = $this->personalizeMessage(
                    $campaign->message_body,
                    $contact,
                    $optOutText,
                    $campaign->id,
                    $contact->id
                );

                $message = Message::create([
                    'campaign_id'  => $campaign->id,
                    'contact_id'   => $contact->id,
                    'status'       => 'pending',
                    'message_body' => $personalizedBody,
                ]);

                // Only create Click record when the message body actually has a tracking URL
                if ($hasTracking) {
                    $trackingToken = $this->generateUniqueToken();
                    $targetUrl     = config('sms.whatsapp_url', 'https://wa.me/');

                    Click::create([
                        'message_id'  => $message->id,
                        'token'       => $trackingToken,
                        'target_url'  => $targetUrl,
                        'click_count' => 0,
                    ]);

                    $trackingUrl = route('track.click', ['token' => $trackingToken]);
                    $message->update([
                        'message_body' => str_replace('{tracking_url}', $trackingUrl, $personalizedBody),
                    ]);
                }

                ActivityLogger::addedToCampaign($contact->id, $campaign);

                SendSmsJob::dispatch($message->id)
                    ->delay(now()->addSeconds($index * $delaySeconds));

                $index++;
            }
        });
    }

    /**
     * Called by Laravel when the job itself fails (not individual SMS failures).
     * Resets campaign to draft so it can be retried.
     */
    public function failed(\Throwable $exception): void
    {
        Campaign::where('id', $this->campaignId)
            ->where('status', 'sending')
            ->update(['status' => 'draft']);

        Log::error('DispatchCampaignJob failed — campaign reset to draft', [
            'campaign_id' => $this->campaignId,
            'error'       => $exception->getMessage(),
        ]);
    }

    private function personalizeMessage(
        string $body,
        $contact,
        string $optOutText,
        int $campaignId,
        int $contactId
    ): string {
        $name      = $contact->name ?? '';
        $nameParts = explode(' ', trim($name), 2);
        $firstName = $nameParts[0] ?? '';
        $lastName  = $nameParts[1] ?? '';

        $personalized = str_replace(
            ['{name}', '{first_name}', '{last_name}', '{clinic_name}', '{date}'],
            [$name, $firstName, $lastName, config('app.name'), now()->format('F j, Y')],
            $body
        );

        if ($optOutText) {
            $optOutUrl    = route('optout.form', ['campaign' => $campaignId, 'contact' => $contactId]);
            $optOutLine   = str_replace('{opt_out_url}', $optOutUrl, $optOutText);
            $personalized .= "\n" . $optOutLine;
        }

        return $personalized;
    }

    /**
     * Generate a unique tracking token with collision check.
     */
    private function generateUniqueToken(): string
    {
        do {
            $token = Str::random(32);
        } while (Click::where('token', $token)->exists());

        return $token;
    }
}
