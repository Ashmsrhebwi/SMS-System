<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuppressionList extends Model
{
    protected $table = 'suppression_list';

    protected $fillable = [
        'phone', 'name', 'reason', 'error_code',
        'failure_count', 'contact_id', 'added_by', 'last_failed_at',
    ];

    protected $casts = [
        'last_failed_at' => 'datetime',
        'failure_count'  => 'integer',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class)->withTrashed();
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
