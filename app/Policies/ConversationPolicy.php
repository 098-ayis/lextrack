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
        $isDocumentConversation = $conversation->document?->isAvailableForMessaging();
        $isRequestConversation = $conversation->documentRequest !== null;

        if (! $isDocumentConversation && ! $isRequestConversation) {
            return false;
        }

        // Any user with this permission can view shared office conversations.
        if ($user->can('view_shared_messages')) {
            return true;
        }

        if (
            $isDocumentConversation
            && (int) $conversation->document?->user_id !== (int) $user->id
        ) {
            return false;
        }

        if (
            $isRequestConversation
            && (int) $conversation->documentRequest?->user_id !== (int) $user->id
        ) {
            return false;
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
        $hasMessageableSubject =
            $conversation->document?->isAvailableForMessaging()
            || $conversation->documentRequest !== null;

        if (
            $conversation->status !== 'active'
            || ! $hasMessageableSubject
        ) {
            return false;
        }

        if ($user->can('reply_shared_messages')) {
            return true;
        }

        if (
            $conversation->document?->isAvailableForMessaging()
            && (int) $conversation->document?->user_id !== (int) $user->id
        ) {
            return false;
        }

        if (
            $conversation->documentRequest !== null
            && (int) $conversation->documentRequest?->user_id !== (int) $user->id
        ) {
            return false;
        }

        return $conversation
            ->participants()
            ->where('users.id', $user->id)
            ->exists();
    }


    public function close(
        User $user,
        Conversation $conversation
    ): bool {
        return $user->can('close_conversations');
    }
}
