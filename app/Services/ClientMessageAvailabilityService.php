<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ClientMessageAvailabilityService
{
    /**
     * Check metadata only: never select a message body, attachment, or reply.
     */
    public function hasMessagesFromOffice(User $client): bool
    {
        return $this->officeMessagesQuery($client)->exists();
    }

    public function countMessagesFromOffice(User $client): int
    {
        return (int) $this->officeMessagesQuery($client)->count('messages.id');
    }

    /**
     * message_reads is unique per message and user, so unread state is specific
     * to this authenticated client rather than inferred from a global flag.
     */
    public function countUnreadMessagesFromOffice(User $client): int
    {
        return (int) $this->officeMessagesQuery($client)
            ->whereNotExists(function (Builder $query) use ($client): void {
                $query->selectRaw('1')
                    ->from('message_reads')
                    ->whereColumn('message_reads.message_id', 'messages.id')
                    ->where('message_reads.user_id', $client->getKey());
            })
            ->count('messages.id');
    }

    private function officeMessagesQuery(User $client): Builder
    {
        $authorizedOfficeSenders = User::query()
            ->where('status', User::DEFAULT_STATUS)
            ->whereHas('roles', static fn ($roles) => $roles->whereIn('name', User::ADMIN_ROLES))
            ->select('users.id');

        return DB::table('messages')
            ->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
            ->join('documents', 'documents.document_id', '=', 'conversations.document_id')
            ->join('conversation_participants as client_participants', function ($join) use ($client): void {
                $join->on('client_participants.conversation_id', '=', 'conversations.id')
                    ->where('client_participants.user_id', '=', $client->getKey());
            })
            ->where('documents.user_id', $client->getKey())
            ->whereNotNull('documents.lao_number')
            ->where('documents.lao_number', '!=', '')
            ->whereNotIn('documents.status', ['pending', 'rejected'])
            ->where('messages.sender_id', '!=', $client->getKey())
            ->whereIn('messages.sender_id', $authorizedOfficeSenders);
    }
}
