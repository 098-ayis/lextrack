<?php

namespace App\Models;

use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'document_id',
        'document_request_id',
        'created_by',
        'status',
    ];

    protected static function booted(): void
    {
        static::created(function (self $conversation): void {
            app(AuditLogService::class)->record(
                'Conversation created',
                'A conversation was created.',
                $conversation,
            );
        });

        static::updated(function (self $conversation): void {
            if ($conversation->wasChanged('status')) {
                app(AuditLogService::class)->record(
                    $conversation->status === 'closed'
                        ? 'Conversation closed'
                        : 'Conversation status changed',
                    $conversation->status === 'closed'
                        ? 'A conversation was closed.'
                        : 'Conversation status changed.',
                    $conversation,
                );
            }
        });
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(
            Document::class,
            'document_id',
            'document_id'
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        )->withTrashed();
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'conversation_participants',
            'conversation_id',
            'user_id'
        )
            ->withTrashed()
            ->withPivot('joined_at')
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(
            Message::class,
            'conversation_id'
        );
    }

}
