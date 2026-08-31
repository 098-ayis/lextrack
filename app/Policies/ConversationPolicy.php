<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function view(
        User $user, 
        Conversation $conversation
        ): bool {
    // Any user with this permission can view shared office conversations.
        if ($user->can('view_shared_messages')) {
            return true;
        }

        // Otherwise, only an actual participant can view.
        return $conversation
            ->participants()
            ->where('users.id', $user->id)
            ->exists();
    }

    public function sendMessage(
        User $user, 
        Conversation $conversation
        ): bool {
        if ($conversation->status !== 'active') {
            return false;
        }

        if ($user->can('reply_shared_messages')) {
            return true;
        }

        return $conversation
            ->participants()
            ->where('users.id', $user->id)
            ->exists();
    }


    public function assign(
        User $user,
        Conversation $conversation
    ): bool {
        return $user->can('assign_conversations');
    }

    public function close(
        User $user,
        Conversation $conversation
    ): bool {
        return $user->can('close_conversations');
    }
}