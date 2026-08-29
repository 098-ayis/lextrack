<?php

namespace App\Livewire;

use App\Models\Message;
use Livewire\Component;

class MessageUnreadBadge extends Component
{
    protected $listeners = [
        'messages-read' => '$refresh',
        'message-sent' => '$refresh',
        'message-received' => '$refresh',
    ];

    public function getUnreadCountProperty(): int
    {
        $userId = auth()->id();

        if (! $userId) {
            return 0;
        }

        return Message::query()
            ->where('sender_id', '!=', $userId)
            ->whereHas('conversation.participants', function ($query) use ($userId) {
                $query->where('users.id', $userId);
            })
            ->whereDoesntHave('readers', function ($query) use ($userId) {
                $query->where('users.id', $userId);
            })
            ->count();
    }

    public function render()
    {
        return view('livewire.message-unread-badge');
    }
}