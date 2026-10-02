<?php

namespace App\Models;

use App\Services\AuditLogService;
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

    protected static function booted(): void
    {
        static::created(function (self $event): void {
            app(AuditLogService::class)->record(
                'Calendar event created',
                'A calendar event was created.',
                $event,
            );
        });

        static::updated(function (self $event): void {
            app(AuditLogService::class)->record(
                'Calendar event updated',
                'A calendar event was updated.',
                $event,
            );
        });

        static::deleted(function (self $event): void {
            app(AuditLogService::class)->record(
                'Calendar event deleted',
                'A calendar event was deleted.',
                $event,
            );
        });
    }

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
        )->withTrashed();
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
