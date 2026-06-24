<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactActivity extends Model
{
    protected $fillable = ['contact_id', 'user_id', 'event_type', 'description', 'metadata'];

    protected $casts = [
        'metadata' => 'array',
    ];

    // event_type constants
    const CREATED         = 'contact_created';
    const IMPORTED        = 'contact_imported';
    const UPDATED         = 'contact_updated';
    const SMS_SENT        = 'sms_sent';
    const SMS_DELIVERED   = 'sms_delivered';
    const SMS_FAILED      = 'sms_failed';
    const LINK_CLICKED    = 'link_clicked';
    const ADDED_TO_CAMPAIGN = 'added_to_campaign';
    const TAG_ADDED       = 'tag_added';
    const TAG_REMOVED     = 'tag_removed';
    const OPTED_OUT       = 'opted_out';
    const BLACKLIST_REMOVED = 'removed_from_blacklist';
    const NOTE_ADDED      = 'note_added';
    const RESENT          = 'sms_resent';

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function icon(): string
    {
        return match ($this->event_type) {
            self::CREATED, self::IMPORTED   => 'user-plus',
            self::UPDATED                   => 'pencil',
            self::SMS_SENT                  => 'send',
            self::SMS_DELIVERED             => 'check-circle',
            self::SMS_FAILED, self::RESENT  => 'x-circle',
            self::LINK_CLICKED              => 'cursor-click',
            self::ADDED_TO_CAMPAIGN         => 'megaphone',
            self::TAG_ADDED, self::TAG_REMOVED => 'tag',
            self::OPTED_OUT                 => 'ban',
            self::BLACKLIST_REMOVED         => 'shield-check',
            self::NOTE_ADDED                => 'annotation',
            default                         => 'information-circle',
        };
    }

    public function color(): string
    {
        return match ($this->event_type) {
            self::SMS_DELIVERED, self::BLACKLIST_REMOVED => 'emerald',
            self::SMS_FAILED                             => 'red',
            self::OPTED_OUT                              => 'amber',
            self::LINK_CLICKED                           => 'violet',
            self::ADDED_TO_CAMPAIGN, self::SMS_SENT, self::RESENT => 'indigo',
            self::TAG_ADDED, self::TAG_REMOVED           => 'sky',
            default                                      => 'slate',
        };
    }
}
