<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Message extends Model
{
    protected $fillable = [
        'campaign_id', 'contact_id', 'twilio_sid', 'status',
        'error_code', 'error_message', 'message_body',
        'sms_segments', 'cost', 'resend_count',
        'sent_at', 'delivered_at',
    ];

    protected $casts = [
        'sent_at'      => 'datetime',
        'delivered_at' => 'datetime',
        'cost'         => 'float',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function click(): HasOne
    {
        return $this->hasOne(Click::class);
    }
}
