<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Calendar extends Model
{
    protected $primaryKey = 'sched_id';

    protected $fillable = [
        'user_id',
        'date',
        'time',
        'event',
        'details',
    ];

    protected $casts = [
        'date' => 'date',
        'time' => 'datetime:H:i',

        'reminder_3_days_sent_at' => 'datetime',
        'reminder_1_day_sent_at' => 'datetime',
        'reminder_10_minutes_sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'id'
        );
    }
}