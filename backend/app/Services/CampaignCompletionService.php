<?php

namespace App\Services;

use App\Models\Campaign;
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
                }
            });
        } catch (\Throwable $e) {
            Log::error('CampaignCompletionService error', [
                'campaign_id' => $campaignId,
                'error'       => $e->getMessage(),
            ]);
        }
    }
}
