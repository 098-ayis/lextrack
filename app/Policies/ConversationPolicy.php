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

        return $conversation
            ->participants()
            ->where('users.id', $user->id)
            ->exists();
    }

    public function assign(
        User $user,
        Conversation $conversation
    ): bool {
        $isParticipant = $conversation
            ->participants()
            ->where('users.id', $user->id)
            ->exists();

        if (! $isParticipant) {
            return false;
        }

        // Do not allow the client who created the conversation
        // to assign themselves as staff handler.
        if ((int) $conversation->created_by === (int) $user->id) {
            return false;
        }

        return true;
    }

    /*
    public function assign(
        User $user,
        Conversation $conversation
    ): bool {
        return $user->can('assign_conversations');
    }
    */
    public function close(
        User $user,
        Conversation $conversation
    ): bool {
        return $user->can('close_conversations');
    }
}