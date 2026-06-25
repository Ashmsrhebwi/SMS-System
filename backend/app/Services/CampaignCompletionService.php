<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Message;
use App\Notifications\AdminAlertNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CampaignCompletionService
{
    public function checkAndComplete(int $campaignId): void
    {
        try {
            DB::transaction(function () use ($campaignId) {
                $campaign = Campaign::where('id', $campaignId)
                    ->where('status', 'sending')
                    ->lockForUpdate()
                    ->first();

                if (!$campaign) {
                    return;
                }

                $pending = $campaign->messages()
                    ->whereIn('status', ['pending', 'queued', 'sent'])
                    ->count();

                if ($pending === 0) {
                    $campaign->update(['status' => 'completed']);
                    Log::info("Campaign {$campaignId} marked as completed");
                    $this->notifyCampaignComplete($campaign);
                }
            });
        } catch (\Throwable $e) {
            Log::error('CampaignCompletionService error', [
                'campaign_id' => $campaignId,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    private function notifyCampaignComplete(Campaign $campaign): void
    {
        try {
            $notify = \App\Models\SystemSetting::get('notify_campaign_complete', true);
            if (!$notify) return;

            $delivered = Message::where('campaign_id', $campaign->id)->where('status', 'delivered')->count();
            $failed    = Message::where('campaign_id', $campaign->id)->whereIn('status', ['failed', 'undelivered'])->count();
            $total     = $campaign->total_recipients;

            AdminAlertNotification::sendToAdmins(
                type: 'campaign_complete',
                subject: "Campaign Complete: {$campaign->name}",
                headline: "Campaign \"{$campaign->name}\" has finished sending",
                details: [
                    'Campaign'   => $campaign->name,
                    'Total Sent' => $total,
                    'Delivered'  => $delivered,
                    'Failed'     => $failed,
                    'Rate'       => $total > 0 ? round(($delivered / $total) * 100, 1) . '%' : '0%',
                    'Completed'  => now()->toDateTimeString() . ' UTC',
                ],
                severity: $failed > $delivered ? 'warning' : 'info'
            );
        } catch (\Throwable $e) {
            Log::error('Campaign complete notification failed: ' . $e->getMessage());
        }
    }
}
