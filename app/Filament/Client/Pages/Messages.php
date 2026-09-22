<?php

namespace App\Filament\Client\Pages;

use App\Models\Conversation;
use App\Models\Document;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\MessageReaction;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\WithFileUploads;

class Messages extends Page
{
    use WithFileUploads;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected string $view = 'filament.client.pages.messages';

    public function getHeading(): string
    {
        return '';
    }

    public ?int $selectedConversation = null;

    public string $search = '';

    public string $newMessage = '';

    public array $attachments = [];

    public string $attachmentKind = '';

    public ?int $replyingToMessageId = null;

    public $messages = [];

    /**
     * Used to identify messages added by a polling refresh.
     */
    public ?int $lastLoadedConversationId = null;

    public int $lastLoadedMessageId = 0;

    /**
     * Conversations visible to the logged-in client.
     */
    public function getViewData(): array
    {
        $userId = auth()->id();

        $allConversations = auth()
            ->user()
            ->conversations()
            ->with([
                'document',
                'documentRequest.user',
                'participants',
                'messages.sender',
                'messages.attachments',
                'messages.reactions',
                'messages.replyTo.sender',
                'messages.replyTo.attachments',
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
            ->where(function ($query) use ($userId): void {
                $query
                    ->whereHas(
                        'document',
                        fn ($documentQuery) => $documentQuery->availableForMessaging()
                    )
                    ->orWhereHas(
                        'documentRequest',
                        fn ($requestQuery) => $requestQuery->where('user_id', $userId)
                    );
            })
            ->latest('conversations.updated_at')
            ->get();

        $search = $this->normalizeSearch($this->search);
        $conversations = $search === ''
            ? $allConversations
            : $allConversations
                ->filter(
                    fn (Conversation $conversation): bool => str_contains(
                        $this->getSearchableConversationText($conversation),
                        $search
                    )
                )
                ->values();

        return [
            'conversations' => $conversations,
            'activeConversationRecord' => $allConversations
                ->firstWhere('id', $this->selectedConversation),
        ];
    }

    private function getSearchableConversationText(Conversation $conversation): string
    {
        return $this->normalizeSearch(implode(' ', [
            $conversation->document?->lao_number,
            $conversation->document?->particulars,
            $conversation->document?->document_name,
            $conversation->documentRequest?->purpose,
            $conversation->documentRequest?->purpose_details,
            $conversation->messages->pluck('body')->filter()->implode(' '),
        ]));
    }

    private function normalizeSearch(?string $value): string
    {
        return preg_replace(
            '/[^\p{L}\p{N}]/u',
            '',
            mb_strtolower((string) $value)
        ) ?? '';
    }
    /**
     * Open/create the conversation belonging to a document.
     */
    public function openDocumentConversation(int $documentId): void
    {
        $conversation = $this->getOrCreateConversation($documentId);

        $this->selectedConversation = $conversation->id;

        $this->clearAttachment();
        $this->cancelReply();

        $this->loadMessages();

        $this->markMessagesAsRead();
    }

    public function openRequestConversation(int $requestId): void
    {
        $request = \App\Models\DocumentRequest::query()
            ->where('user_id', auth()->id())
            ->findOrFail($requestId);

        $conversation = DB::transaction(function () use ($request): Conversation {
            $conversation = Conversation::firstOrCreate(
                [
                    'document_request_id' => $request->request_id,
                ],
                [
                    'created_by' => auth()->id(),
                    'status' => 'active',
                ]
            );

            $conversation->participants()->syncWithoutDetaching([
                auth()->id() => [
                    'joined_at' => now(),
                ],
            ]);

            $staffIds = User::permission('view_shared_messages')->pluck('id');

            foreach ($staffIds as $staffId) {
                $conversation->participants()->syncWithoutDetaching([
                    $staffId => [
                        'joined_at' => now(),
                    ],
                ]);
            }

            return $conversation;
        });

        Gate::authorize('view', $conversation);

        $this->selectedConversation = $conversation->id;
        $this->clearAttachment();
        $this->cancelReply();
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
         * Make sure this document actually belongs to
         * the logged-in client.
         */
        $document = Document::query()
            ->where('document_id', $documentId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        abort_unless(
            $document->isAvailableForMessaging(),
            403,
            'Messaging is available after the document is accepted.'
        );

        return DB::transaction(function () use ($document) {

            $conversation = Conversation::firstOrCreate(
                [
                    'document_id' => $document->document_id,
                ],
                [
                    'created_by' => auth()->id(),
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

        $this->clearAttachment();
        $this->cancelReply();

        $this->loadMessages();

        $this->markMessagesAsRead();

        $this->dispatch('conversation-opened');
    }

    /**
     * Open a revision request without exposing its destination in the chat.
     */
    public function openRevisionRequest(int $messageId): mixed
    {
        $message = Message::query()
            ->with('conversation.document')
            ->findOrFail($messageId);

        $conversation = $message->conversation;

        Gate::authorize('view', $conversation);

        $document = $conversation?->document;

        abort_unless(
            $document && (int) $document->user_id === (int) auth()->id(),
            404
        );

        $isRevisionRequest =
            $message->body === 'revision_request'
            || str_contains(
                (string) $message->body,
                'Please upload a revised version of your document using this link:'
            );

        abort_unless($isRevisionRequest, 404);

        return redirect()->to(ReviseDocument::getUrl([
            'document' => $document->public_id,
        ], false, 'client'));
    }

    /**
     * Retrieve messages for currently selected conversation.
     */
    public function loadMessages(bool $announceNewMessages = false): void
    {
        if (! $this->selectedConversation) {
            $this->messages = [];
            $this->lastLoadedConversationId = null;
            $this->lastLoadedMessageId = 0;

            return;
        }

        $conversation = Conversation::findOrFail(
            $this->selectedConversation
        );

        Gate::authorize('view', $conversation);

        $sameConversationWasLoaded =
            $this->lastLoadedConversationId === $conversation->id;

        $previousLastMessageId = $sameConversationWasLoaded
            ? $this->lastLoadedMessageId
            : 0;

        $this->messages = $conversation
            ->messages()
            ->with([
                'sender',
                'attachments',
                'reactions',
                'replyTo.sender',
                'replyTo.attachments',
            ])
            ->oldest('created_at')
            ->oldest('id')
            ->get();

        $newMessages = $announceNewMessages && $sameConversationWasLoaded
            ? $this->messages->filter(
                fn (Message $message): bool => $message->id > $previousLastMessageId
            )
            : collect();

        $this->lastLoadedConversationId = $conversation->id;
        $this->lastLoadedMessageId = (int) ($this->messages->last()?->id ?? 0);

        if ($newMessages->isEmpty()) {
            return;
        }

        $incomingCount = $newMessages
            ->where('sender_id', '!=', auth()->id())
            ->count();

        $outgoingCount = $newMessages
            ->where('sender_id', auth()->id())
            ->count();

        $this->dispatch(
            'new-messages-available',
            incomingCount: $incomingCount,
            outgoingCount: $outgoingCount,
        );

        if ($incomingCount > 0) {
            $this->dispatch(
                'messages-unread',
                count: $this->getUnreadMessagesCount()
            );
        }
    }

    public function refreshConversation(): void
    {
        if (! $this->selectedConversation) {
            return;
        }

        $this->loadMessages(announceNewMessages: true);
    }

    /**
     * Send message.
     */
    public function sendMessage(): void
    {
        if (! $this->selectedConversation) {
            return;
        }

        $messageBody = trim($this->newMessage);

        if ($messageBody === '' && count($this->attachments) === 0) {
            $this->addError(
                'newMessage',
                'Write a message or attach a file before sending.'
            );

            return;
        }

        $attachmentRule = match ($this->attachmentKind) {
            'image' => 'mimes:jpg,jpeg,png',
            'document' => 'mimes:pdf,docx',
            default => 'mimes:pdf,docx',
        };

        $this->validate([
            'newMessage' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'attachments' => [
                'nullable',
                'array',
                'max:10',
            ],
            'attachments.*' => [
                'file',
                'max:25600',
                $attachmentRule,
            ],
        ]);

        $conversation = Conversation::findOrFail(
            $this->selectedConversation
        );

        Gate::authorize('sendMessage', $conversation);

        $replyToMessageId = null;

        if ($this->replyingToMessageId !== null) {
            $replyToMessageId = $conversation
                ->messages()
                ->whereKey($this->replyingToMessageId)
                ->value('id');

            abort_unless($replyToMessageId, 404);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => auth()->id(),
            'body' => $messageBody !== '' ? $messageBody : 'Attachment sent.',
            'reply_to_message_id' => $replyToMessageId,
        ]);

        foreach ($this->attachments as $sortOrder => $attachment) {
            $path = $attachment->store('message-attachments', 'local');

            MessageAttachment::create([
                'message_id' => $message->id,
                'disk' => 'local',
                'path' => $path,
                'original_name' => $attachment->getClientOriginalName(),
                'mime_type' => $attachment->getMimeType(),
                'size' => $attachment->getSize(),
                'sha256' => hash_file('sha256', $attachment->getRealPath()),
                'sort_order' => $sortOrder,
            ]);
        }

        /*
         * Make conversation move to top of inbox.
         */
        $conversation->touch();

        $this->newMessage = '';
        $this->clearAttachment();
        $this->cancelReply();

        $this->loadMessages();

        $this->dispatch('message-sent');
    }

    /**
     * Remove a file selected for the next message.
     */
    public function clearAttachment(): void
    {
        $this->attachments = [];
        $this->attachmentKind = '';
        $this->resetValidation('attachments');
        $this->resetValidation('attachments.*');
    }

    public function startReply(int $messageId): void
    {
        if (! $this->selectedConversation) {
            return;
        }

        $conversation = Conversation::findOrFail($this->selectedConversation);

        Gate::authorize('view', $conversation);

        $conversation->messages()->findOrFail($messageId);

        $this->replyingToMessageId = $messageId;
        $this->dispatch('reply-started');
    }

    public function cancelReply(): void
    {
        $this->replyingToMessageId = null;
    }

    public function reactToMessage(int $messageId, string $reaction): void
    {
        $allowedReactions = ['👍', '❤️', '😂', '😮', '😢', '🙏'];

        if (! in_array($reaction, $allowedReactions, true) || ! $this->selectedConversation) {
            return;
        }

        $conversation = Conversation::findOrFail($this->selectedConversation);

        Gate::authorize('view', $conversation);

        $message = $conversation->messages()->findOrFail($messageId);

        $userReactions = MessageReaction::query()
            ->where('message_id', $message->id)
            ->where('user_id', auth()->id())
            ->get();

        $isTogglingOff = $userReactions->contains('reaction', $reaction);

        MessageReaction::query()
            ->where('message_id', $message->id)
            ->where('user_id', auth()->id())
            ->delete();

        if (! $isTogglingOff) {
            MessageReaction::create([
                'message_id' => $message->id,
                'user_id' => auth()->id(),
                'reaction' => $reaction,
            ]);
        }

        $this->loadMessages();
    }

    public function removeAttachment(int $index): void
    {
        if (! array_key_exists($index, $this->attachments)) {
            return;
        }

        unset($this->attachments[$index]);
        $this->attachments = array_values($this->attachments);
        $this->resetValidation('attachments');
        $this->resetValidation('attachments.*');
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

        $this->dispatch(
            'messages-read',
            count: $this->getUnreadMessagesCount()
        );
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

    public function mount(): void
    {
        $documentPublicId = request()->query('document');
        $requestId = request()->query('request');

        if ($requestId) {
            $this->openRequestConversation((int) $requestId);

            return;
        }

        $documentId = request()->query('document');

        if (! $documentPublicId) {
            return;
        }

        $document = \App\Models\Document::query()
            ->where('public_id', $documentPublicId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $conversation = Conversation::query()
            ->where('document_id', $document->document_id)
            ->whereHas(
                'document',
                fn ($query) => $query->availableForMessaging()
            )
            ->first();

        if (! $conversation) {
            return;
        }

        Gate::authorize('view', $conversation);

        $this->selectedConversation = $conversation->id;

        $this->loadMessages();

        $this->markMessagesAsRead();
    }

    private function getUnreadMessagesCount(): int
    {
        $userId = auth()->id();

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
}
