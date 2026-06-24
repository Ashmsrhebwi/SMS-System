<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    protected $fillable = ['name', 'message_body', 'scheduled_at', 'status', 'total_recipients', 'segment_id', 'created_by'];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function segment(): BelongsTo
    {
        return $this->belongsTo(Segment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getDeliveredCountAttribute(): int
    {
        return $this->messages()->where('status', 'delivered')->count();
    }

    public function getFailedCountAttribute(): int
    {
        return $this->messages()->whereIn('status', ['failed', 'undelivered'])->count();
    }

    public function getSentCountAttribute(): int
    {
        return $this->messages()->whereIn('status', ['sent', 'delivered', 'failed', 'undelivered', 'queued'])->count();
    }

    public function getClickCountAttribute(): int
    {
        return Click::whereHas('message', fn($q) => $q->where('campaign_id', $this->id))
            ->where('click_count', '>', 0)
            ->count();
    }

    public function getDeliveryRateAttribute(): float
    {
        $sent = $this->sent_count;
        if ($sent === 0) return 0;
        return round(($this->delivered_count / $sent) * 100, 1);
    }

    public function getClickRateAttribute(): float
    {
        $delivered = $this->delivered_count;
        if ($delivered === 0) return 0;
        return round(($this->click_count / $delivered) * 100, 1);
    }

    public function getTotalCostAttribute(): float
    {
        return (float) $this->messages()->sum('cost');
    }

    public function getTotalSegmentsAttribute(): int
    {
        return (int) $this->messages()->sum('sms_segments');
    }
}
