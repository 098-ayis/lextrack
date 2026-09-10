<?php

namespace App\Filament\Pages;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
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

    public $attachment = null;

    public string $attachmentKind = '';

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
     * Open a conversation.
     */
    public function selectConversation(int $conversationId): void
    {
        $conversation = Conversation::findOrFail($conversationId);

        Gate::authorize('view', $conversation);

        $this->selectedConversation = $conversation->id;
        $this->attachment = null;
        $this->attachmentKind = '';

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
            ->with('sender')
            ->oldest('created_at')
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

        if ($messageBody === '' && ! $this->attachment) {
            $this->addError(
                'newMessage',
                'Write a message or attach a file before sending.'
            );

            return;
        }

        $attachmentRule = match ($this->attachmentKind) {
            'image' => 'mimes:jpg,jpeg,png,gif,webp',
            'document' => 'mimes:pdf,docx',
            default => 'mimes:pdf,docx',
        };

        $this->validate([
            'newMessage' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'attachment' => [
                'nullable',
                'file',
                'max:5120',
                $attachmentRule,
            ],
        ]);

        $conversation = Conversation::findOrFail(
            $this->selectedConversation
        );

        Gate::authorize('sendMessage', $conversation);

        $attachmentPath = null;
        $attachmentName = null;
        $attachmentMimeType = null;

        if ($this->attachment) {
            $attachmentPath = $this->attachment->store(
                'message-attachments',
                'local'
            );
            $attachmentName = $this->attachment->getClientOriginalName();
            $attachmentMimeType = $this->attachment->getMimeType();
        }

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => auth()->id(),
            'body' => $messageBody !== '' ? $messageBody : 'Attachment sent.',
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'attachment_mime_type' => $attachmentMimeType,
        ]);

        $conversation->touch();

        $this->newMessage = '';
        $this->attachment = null;
        $this->attachmentKind = '';

        $this->loadMessages();

        $this->dispatch('message-sent');
    }

    /**
     * Remove a file selected for the next message.
     */
    public function clearAttachment(): void
    {
        $this->attachment = null;
        $this->attachmentKind = '';
        $this->resetValidation('attachment');
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
     * Staff makes themselves the primary handler.
     */
    public function assignToMe(int $conversationId): void
    {
        $conversation = Conversation::findOrFail($conversationId);

        Gate::authorize('assign', $conversation);

        $conversation->update([
            'assigned_to' => auth()->id(),
        ]);
    }

    /**
     * Assign another authorized staff member.
     */
    public function assignStaff(
        int $conversationId,
        int $staffId
    ): void {
        $conversation = Conversation::findOrFail($conversationId);

        Gate::authorize('assign', $conversation);

        $staff = User::permission('view_shared_messages')
            ->where('id', $staffId)
            ->firstOrFail();

        $conversation->update([
            'assigned_to' => $staff->id,
        ]);

        /*
         * Make sure assigned employee is also
         * a conversation participant.
         */
        $conversation->participants()->syncWithoutDetaching([
            $staff->id => [
                'joined_at' => now(),
            ],
        ]);
    }

    /**
     * Remove primary assignment.
     */
    public function unassign(int $conversationId): void
    {
        $conversation = Conversation::findOrFail($conversationId);

        Gate::authorize('assign', $conversation);

        $conversation->update([
            'assigned_to' => null,
        ]);
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
