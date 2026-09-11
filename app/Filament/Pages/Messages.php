<?php

namespace App\Filament\Pages;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\MessageReaction;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\WithFileUploads;

class Messages extends Page
{
    use WithFileUploads;

    protected static ?int $navigationSort = 5;

    protected static string|\BackedEnum|null $navigationIcon =
        'heroicon-o-chat-bubble-left-right';

    protected string $view = 'filament.pages.messages';

    public ?int $selectedConversation = null;

    public string $newMessage = '';

    public array $attachments = [];

    public string $attachmentKind = '';

    public ?int $replyingToMessageId = null;

    public $messages = [];

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view_shared_messages') ?? false;
    }

    /**
     * Shared inbox.
     *
     * Staff only see conversations where they
     * are authorized participants.
     */
    public function getViewData(): array
    {
        $userId = auth()->id();

        return [
            'conversations' => Conversation::query()
                ->with([
                    'document.user',
                    'creator',
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
                ->whereHas(
                    'document',
                    fn ($query) => $query->availableForMessaging()
                )
                ->latest('conversations.updated_at')
                ->get(),
            ];
    }

    /**
     * Open a conversation.
     */
    public function selectConversation(int $conversationId): void
    {
        $conversation = Conversation::findOrFail($conversationId);

        Gate::authorize('view', $conversation);

        $this->selectedConversation = $conversation->id;
        $this->attachments = [];
        $this->attachmentKind = '';
        $this->cancelReply();

        $this->loadMessages();

        $this->markMessagesAsRead();

        $this->dispatch(
            'messages-read',
            count: $this->getUnreadMessagesCount()
        );

        $this->dispatch('conversation-opened');
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
     * Load conversation messages.
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
    }

    /**
     * Staff reply.
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

        $conversation->touch();

        $this->newMessage = '';
        $this->attachments = [];
        $this->attachmentKind = '';
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

        $existingReaction = MessageReaction::query()
            ->where('message_id', $message->id)
            ->where('user_id', auth()->id())
            ->where('reaction', $reaction)
            ->first();

        if ($existingReaction) {
            $existingReaction->delete();
        } else {
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
     * Send the client a revision request card.
     *
     * The destination is intentionally not stored in or displayed as
     * message text. The client opens it through a server-authorized action.
     */
    public function requestRevision(): void
    {
        if (! $this->selectedConversation) {
            return;
        }

        $conversation = Conversation::query()
            ->with('document')
            ->findOrFail($this->selectedConversation);

        Gate::authorize('sendMessage', $conversation);

        $document = $conversation->document;

        if (! $document) {
            Notification::make()
                ->warning()
                ->title('Revision request could not be sent')
                ->body('This conversation is not linked to a document.')
                ->send();

            return;
        }

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => auth()->id(),
            'body' => 'revision_request',
        ]);

        $conversation->touch();

        $this->loadMessages();

        Notification::make()
            ->success()
            ->title('Revision request sent')
            ->body('The client can open the revision request card to upload a revised document.')
            ->send();

        $this->dispatch('message-sent');
    }

    /**
     * Close conversation.
     */
    public function closeConversation(int $conversationId): void
    {
        $conversation = Conversation::findOrFail($conversationId);

        Gate::authorize('close', $conversation);

        $conversation->update([
            'status' => 'closed',
        ]);
    }

    /**
     * Track reads separately for each staff member.
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
            ->whereHas(
                'conversation.document',
                fn ($query) => $query->availableForMessaging()
            )
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
        $documentId = request()->query('document');

        if (! $documentId) {
            return;
        }

        $conversation = Conversation::query()
            ->where('document_id', $documentId)
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
