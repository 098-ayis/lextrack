<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Calendar extends Model
{
    protected $primaryKey = 'sched_id';

    protected $fillable = [
        'user_id',
        'document_request_id',
        'date',
        'time',
        'event',
        'category',
        'details',
    ];

    protected $casts = [
        'date' => 'date',
        'time' => 'datetime:H:i',

        'reminder_3_days_sent_at' => 'datetime',
        'reminder_1_day_sent_at' => 'datetime',
        'reminder_1_hour_sent_at' => 'datetime',
    ];

    public function getIsCompletedAttribute(): bool
    {
        if (! $this->date) {
            return false;
        }

        $scheduledAt = $this->date->copy();

        if ($this->time) {
            $scheduledAt->setTimeFrom($this->time);
        } else {
            $scheduledAt->endOfDay();
        }

        return $scheduledAt->lt(now());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'id'
        );
    }

    public function documentRequest(): BelongsTo
    {
        return $this->belongsTo(
            DocumentRequest::class,
            'document_request_id',
            'request_id'
        );
    }
}
