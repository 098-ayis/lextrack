<?php

namespace App\Filament\Client\Pages;

use App\Models\Conversation;
use App\Models\Document;
use App\Models\Message;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class Messages extends Page
{
    protected string $view = 'filament.client.pages.messages';

    public function getHeading(): string
    {
        return '';
    }

    public ?int $selectedConversation = null;

    public string $newMessage = '';

    public $messages = [];

    public function mount(): void
    {
        $documentId = (int) request()->query('document', 0);

        if ($documentId > 0) {
            $this->openDocumentConversation($documentId);
        }
    }

    /**
     * Conversations visible to the logged-in client.
     */
    public function getViewData(): array
    {
        $userId = auth()->id();

        return [
            'conversations' => auth()
                ->user()
                ->conversations()
                ->with([
                    'document',
                    'assignedStaff',
                    'participants',
                    'messages.sender',
                ])
                ->withCount([
                    'messages as unread_messages_count' => function ($query) use ($userId) {
                        $query
                            ->where('sender_id', '!=', $userId)
                            ->whereDoesntHave('readers', function ($query) use ($userId) {
                                $query->where('users.id', $userId);
                            });
                    },
                ])
                ->latest('conversations.updated_at')
                ->get(),
        ];
    }
    /**
     * Open/create the conversation belonging to a document.
     */
    public function openDocumentConversation(int $documentId): void
    {
        $conversation = $this->getOrCreateConversation($documentId);

        $this->selectedConversation = $conversation->id;

        $this->loadMessages();

        $this->markMessagesAsRead();
    }

    /**
     * Create the document conversation if it does not exist.
     *
     * Client does NOT select staff recipient.
     */
    protected function getOrCreateConversation(int $documentId): Conversation
    {
        /*
         * SECURITY:
         * The client may message documents they uploaded or documents they
         * have an associated document request for.
         */
        $document = Document::query()
            ->where('document_id', $documentId)
            ->where(function ($query) {
                $query
                    ->where('user_id', auth()->id())
                    ->orWhereHas(
                        'documentRequests',
                        fn ($requestQuery) => $requestQuery->where(
                            'user_id',
                            auth()->id()
                        )
                    );
            })
            ->firstOrFail();

        return DB::transaction(function () use ($document) {

            $conversation = Conversation::firstOrCreate(
                [
                    'document_id' => $document->document_id,
                ],
                [
                    'created_by' => auth()->id(),
                    'assigned_to' => null,
                    'status' => 'active',
                ]
            );

            /*
             * Add the client as participant.
             */
            $conversation->participants()->syncWithoutDetaching([
                auth()->id() => [
                    'joined_at' => now(),
                ],
            ]);

            /*
             * Add every authorized staff member.
             *
             * This requires the permission:
             * view_shared_messages
             */
            $staffIds = User::permission('view_shared_messages')
                ->pluck('id');

            foreach ($staffIds as $staffId) {
                $conversation->participants()->syncWithoutDetaching([
                    $staffId => [
                        'joined_at' => now(),
                    ],
                ]);
            }

            return $conversation;
        });
    }

    /**
     * User selects an existing conversation.
     */
    public function selectConversation(int $conversationId): void
    {
        $conversation = Conversation::findOrFail($conversationId);

        Gate::authorize('view', $conversation);

        $this->selectedConversation = $conversation->id;

        $this->loadMessages();

        $this->markMessagesAsRead();
    }

    /**
     * Retrieve messages for currently selected conversation.
     */
    public function loadMessages(): void
    {
        if (! $this->selectedConversation) {
            $this->messages = [];

            return;
        }

        $conversation = Conversation::findOrFail(
            $this->selectedConversation
        );

        Gate::authorize('view', $conversation);

        $this->messages = $conversation
            ->messages()
            ->with('sender')
            ->oldest('created_at')
            ->get();
    }

    public function refreshConversation(): void
    {
        if (! $this->selectedConversation) {
            return;
        }

        $this->loadMessages();

        $this->markMessagesAsRead();
    }

    /**
     * Send message.
     */
    public function sendMessage(): void
    {
        $this->validate([
            'newMessage' => [
                'required',
                'string',
                'max:5000',
            ],
        ]);

        if (! $this->selectedConversation) {
            return;
        }

        $conversation = Conversation::findOrFail(
            $this->selectedConversation
        );

        Gate::authorize('sendMessage', $conversation);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => auth()->id(),
            'body' => trim($this->newMessage),
        ]);

        /*
         * Make conversation move to top of inbox.
         */
        $conversation->touch();

        $this->newMessage = '';

        $this->loadMessages();
    }

    /**
     * Mark messages as read by this specific user.
     */
    public function markMessagesAsRead(): void
    {
        if (! $this->selectedConversation) {
            return;
        }

        $conversation = Conversation::findOrFail(
            $this->selectedConversation
        );

        Gate::authorize('view', $conversation);

        $messageIds = $conversation
            ->messages()
            ->where('sender_id', '!=', auth()->id())
            ->pluck('id');

        foreach ($messageIds as $messageId) {
            DB::table('message_reads')->updateOrInsert(
                [
                    'message_id' => $messageId,
                    'user_id' => auth()->id(),
                ],
                [
                    'read_at' => now(),
                ]
            );
        }
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
