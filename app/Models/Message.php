<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'body',
    ];

    protected function casts(): array
    {
        return [
            'body' => 'encrypted',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(
            Conversation::class,
            'conversation_id'
        );
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'sender_id'
        );
    }

    public function readers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'message_reads',
            'message_id',
            'user_id'
        )->withPivot('read_at');
    }

    public static function getNavigationBadge(): ?string
    {
        $userId = auth()->id();

        if (! $userId) {
            return null;
        }

        $count = Message::query()
            ->where('sender_id', '!=', $userId)
            ->whereHas('conversation.participants', function ($query) use ($userId) {
                $query->where('users.id', $userId);
            })
            ->whereDoesntHave('readers', function ($query) use ($userId) {
                $query->where('users.id', $userId);
            })
            ->count();

        return $count > 0
            ? (string) $count
            : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
    
}