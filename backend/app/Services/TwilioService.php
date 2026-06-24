<?php

namespace App\Services;

use App\Models\Message;
use Twilio\Rest\Client;
use Twilio\Security\RequestValidator;
use Exception;

class TwilioService
{
    private Client $client;
    private string $messagingServiceSid;

    public function __construct()
    {
        $this->client = new Client(
            config('services.twilio.account_sid'),
            config('services.twilio.auth_token')
        );
        $this->messagingServiceSid = config('services.twilio.messaging_service_sid');
    }

    public function sendMessage(string $to, string $body, string $statusCallbackUrl): string
    {
        $message = $this->client->messages->create($to, [
            'messagingServiceSid' => $this->messagingServiceSid,
            'body' => $body,
            'statusCallback' => $statusCallbackUrl,
        ]);

        return $message->sid;
    }

    public function validateRequest(string $signature, string $url, array $params): bool
    {
        $validator = new RequestValidator(config('services.twilio.auth_token'));
        return $validator->validate($signature, $url, $params);
    }
}
