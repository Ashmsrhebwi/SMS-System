<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\ContactActivity;
use App\Models\Message;
use App\Models\Tag;

class ActivityLogger
{
    public static function log(
        int $contactId,
        string $eventType,
        string $description,
        ?int $userId = null,
        ?array $metadata = null
    ): void {
        try {
            ContactActivity::create([
                'contact_id'  => $contactId,
                'user_id'     => $userId ?? auth()->id(),
                'event_type'  => $eventType,
                'description' => $description,
                'metadata'    => $metadata,
            ]);
        } catch (\Throwable) {
            // Never break the main flow due to activity logging
        }
    }

    public static function contactCreated(Contact $contact): void
    {
        self::log($contact->id, ContactActivity::CREATED, 'Contact created manually.');
    }

    public static function contactImported(Contact $contact): void
    {
        self::log($contact->id, ContactActivity::IMPORTED, 'Contact imported from file.', null);
    }

    public static function contactUpdated(Contact $contact): void
    {
        self::log($contact->id, ContactActivity::UPDATED, 'Contact details updated.');
    }

    public static function smsSent(Message $message): void
    {
        self::log(
            $message->contact_id,
            ContactActivity::SMS_SENT,
            "SMS sent via campaign \"{$message->campaign?->name}\".",
            null,
            ['campaign_id' => $message->campaign_id, 'message_id' => $message->id]
        );
    }

    public static function smsDelivered(Message $message): void
    {
        self::log(
            $message->contact_id,
            ContactActivity::SMS_DELIVERED,
            "SMS delivered for campaign \"{$message->campaign?->name}\".",
            null,
            ['campaign_id' => $message->campaign_id, 'message_id' => $message->id]
        );
    }

    public static function smsFailed(Message $message): void
    {
        self::log(
            $message->contact_id,
            ContactActivity::SMS_FAILED,
            "SMS failed: {$message->error_message}",
            null,
            ['campaign_id' => $message->campaign_id, 'message_id' => $message->id, 'error' => $message->error_code]
        );
    }

    public static function linkClicked(Message $message): void
    {
        self::log(
            $message->contact_id,
            ContactActivity::LINK_CLICKED,
            "Tracking link clicked for campaign \"{$message->campaign?->name}\".",
            null,
            ['campaign_id' => $message->campaign_id, 'message_id' => $message->id]
        );
    }

    public static function addedToCampaign(int $contactId, Campaign $campaign): void
    {
        self::log(
            $contactId,
            ContactActivity::ADDED_TO_CAMPAIGN,
            "Added to campaign \"{$campaign->name}\".",
            null,
            ['campaign_id' => $campaign->id]
        );
    }

    public static function tagAdded(Contact $contact, Tag $tag): void
    {
        self::log(
            $contact->id,
            ContactActivity::TAG_ADDED,
            "Tag \"{$tag->name}\" added.",
            null,
            ['tag_id' => $tag->id, 'tag_name' => $tag->name]
        );
    }

    public static function tagRemoved(Contact $contact, Tag $tag): void
    {
        self::log(
            $contact->id,
            ContactActivity::TAG_REMOVED,
            "Tag \"{$tag->name}\" removed.",
            null,
            ['tag_id' => $tag->id, 'tag_name' => $tag->name]
        );
    }

    public static function optedOut(Contact $contact): void
    {
        self::log(
            $contact->id,
            ContactActivity::OPTED_OUT,
            'Contact opted out of SMS messages.',
            null
        );
    }

    public static function blacklistRemoved(Contact $contact): void
    {
        self::log(
            $contact->id,
            ContactActivity::BLACKLIST_REMOVED,
            'Removed from global blacklist.',
        );
    }

    public static function noteAdded(Contact $contact, string $note): void
    {
        self::log(
            $contact->id,
            ContactActivity::NOTE_ADDED,
            'Note added: ' . \Illuminate\Support\Str::limit($note, 60),
        );
    }

    public static function smsResent(Message $message): void
    {
        self::log(
            $message->contact_id,
            ContactActivity::RESENT,
            "SMS re-queued for delivery (campaign \"{$message->campaign?->name}\").",
            null,
            ['campaign_id' => $message->campaign_id, 'message_id' => $message->id]
        );
    }
}
