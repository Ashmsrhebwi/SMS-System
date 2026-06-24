<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use App\Services\CampaignCompletionService;
use App\Services\TwilioService;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function twilioStatus(
        Request $request,
        TwilioService $twilio,
        CampaignCompletionService $completion,
    ) {
        $signature = $request->header('X-Twilio-Signature', '');
        $url       = route('webhooks.twilio.status');
        $params    = $request->post();

        if (!$twilio->validateRequest($signature, $url, $params)) {
            Log::warning('Invalid Twilio webhook signature', ['ip' => $request->ip()]);
            return response('Forbidden', 403);
        }

        $messageSid    = $request->input('MessageSid');
        $messageStatus = $request->input('MessageStatus');
        $errorCode     = $request->input('ErrorCode');

        Log::info('Twilio webhook received', ['sid' => $messageSid, 'status' => $messageStatus]);

        if (!$messageSid || !$messageStatus) {
            return response('Bad Request', 400);
        }

        $message = Message::where('twilio_sid', $messageSid)->first();

        if (!$message) {
            Log::warning('Twilio webhook: message not found', ['sid' => $messageSid]);
            return response('OK', 200);
        }

        // Never downgrade out of a terminal state
        $terminal = ['delivered', 'undelivered', 'failed'];
        if (in_array($message->status, $terminal, true)) {
            return response('OK', 200);
        }

        // Only accept statuses that exist in the messages enum.
        // Twilio can fire 'sending', 'accepted', 'receiving' etc. which are not stored.
        $allowed  = ['queued', 'sent', 'delivered', 'undelivered', 'failed'];
        $priority = ['queued' => 1, 'sent' => 2, 'delivered' => 3, 'undelivered' => 3, 'failed' => 3];

        if (!in_array($messageStatus, $allowed, true)) {
            Log::info('Twilio webhook: ignoring non-stored status', [
                'sid'    => $messageSid,
                'status' => $messageStatus,
            ]);
            return response('OK', 200);
        }

        // Reject out-of-order callbacks (e.g. late 'queued' arriving after 'sent')
        $currentPriority = $priority[$message->status] ?? 0;
        $newPriority     = $priority[$messageStatus]   ?? 0;

        if ($newPriority < $currentPriority) {
            Log::info('Twilio webhook: ignoring out-of-order status', [
                'sid'      => $messageSid,
                'current'  => $message->status,
                'incoming' => $messageStatus,
            ]);
            return response('OK', 200);
        }

        $updateData = ['status' => $messageStatus];

        if ($messageStatus === 'delivered') {
            $updateData['delivered_at'] = now();
        }

        // Capture Twilio price when available (sent/delivered callbacks include it)
        $price = $request->input('Price');
        if ($price !== null && $price !== '') {
            $updateData['cost'] = abs((float) $price);
        }

        if ($errorCode) {
            $updateData['error_code']    = $errorCode;
            $updateData['error_message'] = $request->input('ErrorMessage', '');
        }

        $message->update($updateData);

        $fresh = $message->fresh(['campaign']);
        if ($messageStatus === 'delivered') {
            ActivityLogger::smsDelivered($fresh);
        } elseif (in_array($messageStatus, ['failed', 'undelivered'], true)) {
            ActivityLogger::smsFailed($fresh);
        }

        $completion->checkAndComplete($message->campaign_id);

        return response('OK', 200);
    }
}
