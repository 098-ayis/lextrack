<x-filament-panels::page>

    <style>
        .msg-wrap {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 16px;
            height: calc(100vh - 180px);
        }

        .msg-list {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .msg-list-header {
            padding: 14px 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        .msg-list-header h3 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .msg-search {
            position: relative;
        }

        .msg-search input {
            width: 100%;
            padding: 8px 12px 8px 32px;
            border: 1.5px solid #e5e7eb;
            border-radius: 18px;
            font-size: 12.5px;
            outline: none;
        }

        .msg-search input:focus {
            border-color: #2563eb;
        }

        .msg-search .s-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            font-size: 12px;
        }

        .msg-items {
            flex: 1;
            overflow-y: auto;
        }

        .msg-item {
            display: flex;
            gap: 10px;
            align-items: center;
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid #e5e7eb;
            transition: background .1s;
        }

        .msg-item:hover {
            background: #f3f8fc;
        }

        .msg-item.active {
            background: #f3f8fc;
            border-left: 3px solid #1b6ca8;
        }

        .m-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #ffe0b2;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            color: #a05200;
            flex-shrink: 0;
            position: relative;
        }

        .m-avatar .dot {
            position: absolute;
            bottom: -2px;
            right: -2px;
            width: 11px;
            height: 11px;
            border-radius: 50%;
            background: #3ab66b;
            border: 2px solid white;
        }

        .m-info {
            overflow: hidden;
            flex: 1;
        }

        .m-name {
            font-size: 13.5px;
            font-weight: 600;
            color: #111827;
            display: flex;
            justify-content: space-between;
        }

        .m-time {
            font-size: 10.5px;
            color: #6b7280;
            font-weight: 400;
        }

        .m-sub {
            font-size: 11px;
            color: #1b6ca8;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .m-preview {
            font-size: 12px;
            color: #6b7280;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .msg-item.unread .m-preview,
        .msg-item.unread .m-name {
            font-weight: 700;
            color: #111827;
        }

        .msg-thread {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .thread-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            border-bottom: 1px solid #e5e7eb;
        }

        .t-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f3f8fc;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            color: #1b6ca8;
        }

        .t-name {
            font-size: 15px;
            font-weight: 700;
        }

        .t-sub {
            font-size: 12px;
            color: #6b7280;
        }

        .thread-body {
            flex: 1;
            overflow-y: auto;
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            background: #f9fafb;
        }

        .t-msg-row {
            display: flex;
            gap: 8px;
            max-width: 65%;
        }

        .t-msg-row.own {
            align-self: flex-end;
            flex-direction: row-reverse;
        }

        .t-msg-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #ffe0b2;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            color: #a05200;
            align-self: flex-end;
            flex-shrink: 0;
        }

        .t-msg-row.own .t-msg-avatar {
            background: #dbeafe;
            color: #1b6ca8;
        }

        .t-bubble {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 10px 16px;
            font-size: 13.5px;
            line-height: 1.5;
        }

        .t-msg-row.own .t-bubble {
            background: #1b6ca8;
            color: white;
            border: none;
        }

        .t-time {
            font-size: 10.5px;
            color: #6b7280;
            margin-top: 4px;
        }

        .t-msg-row.own .t-time {
            text-align: right;
        }

        .thread-footer {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 18px;
            border-top: 1px solid #e5e7eb;
        }

        .thread-footer input {
            flex: 1;
            padding: 10px 16px;
            border: 1.5px solid #e5e7eb;
            border-radius: 22px;
            font-size: 13.5px;
            outline: none;
        }

        .thread-footer input:focus {
            border-color: #1b6ca8;
        }

        .thread-footer button {
            background: #1b6ca8;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            font-size: 15px;
        }

        .empty-thread {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #6b7280;
            font-size: 14px;
            flex-direction: column;
            gap: 10px;
        }

        @media (max-width: 900px) {
            .msg-wrap {
                grid-template-columns: 1fr;
                height: auto;
            }

            .msg-list {
                min-height: 300px;
            }

            .msg-thread {
                min-height: 500px;
            }
        }
    </style>

    <div
        class="msg-wrap"
        x-data="{ search: '' }"
    >

        {{-- =========================
            CONVERSATION LIST
        ========================== --}}
        <div class="msg-list">

            <div class="msg-list-header">

                <h3>Conversations</h3>

                <div class="msg-search">

                    <span class="s-icon">
                        <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                    </span>

                    <input
                        type="text"
                        placeholder="Search Messages"
                        x-model="search"
                    >

                </div>

            </div>


            <div class="msg-items">

                @forelse ($conversations as $conversation)

                    @php
                        $latestMessage = $conversation->messages->last();

                        $otherParticipant = $conversation
                            ->participants
                            ->firstWhere('id', '!=', auth()->id());

                        $displayName = $otherParticipant?->name
                            ?? 'Legal Office';

                        $initials = collect(
                            explode(' ', $displayName)
                        )
                            ->filter()
                            ->map(
                                fn ($part) =>
                                    strtoupper(
                                        substr($part, 0, 1)
                                    )
                            )
                            ->take(2)
                            ->join('');

                        $searchText = strtolower(
                            $displayName . ' ' .
                            ($conversation->document?->particulars ?? '') . ' ' .
                            ($latestMessage?->body ?? '')
                        );
                    @endphp


                    <div
                        class="
                            msg-item
                            {{ $conversation->unread_messages_count > 0 ? 'unread' : '' }}
                            {{ (int) $selectedConversation === (int) $conversation->id
                                ? 'active'
                                : '' }}
                        "
                        wire:click="selectConversation({{ $conversation->id }})"
                    >

                    @if ($conversation->unread_messages_count > 0)

                        <span class="unread-count">
                            {{ $conversation->unread_messages_count }}
                        </span>

                    @endif

                        <div class="m-avatar">

                            {{ $initials ?: 'CL' }}

                            @if ($conversation->unread_messages_count > 0)
                                <span class="dot"></span>
                            @endif

                        </div>

                        <div class="m-info">

                            <div class="m-sub">

                                @if ($conversation->document)
                                    Document #{{ $conversation->document_id }}
                                @else
                                    General Conversation
                                @endif

                            </div>


                            <div class="m-name">

                                <span>
                                    {{ $displayName }}
                                </span>

                                <span class="m-time">

                                    @if ($latestMessage)
                                        {{ $latestMessage
                                            ->created_at
                                            ->format('M d') }}
                                    @endif

                                </span>

                            </div>


                            <div class="m-preview">

                                @if ($latestMessage)

                                    @if (
                                        $latestMessage->sender_id
                                        === auth()->id()
                                    )
                                        You:
                                    @endif

                                    {{ \Illuminate\Support\Str::limit(
                                        $latestMessage->body,
                                        60
                                    ) }}

                                @else
                                    No messages yet
                                @endif

                            </div>

                        </div>

                    </div>

                @empty

                    <div class="p-6 text-center text-gray-500">
                        No conversations yet.
                    </div>

                @endforelse

            </div>

        </div>


       {{-- MESSAGE THREAD --}}
    <div
        class="msg-thread"
        wire:poll.4s="refreshConversation"
    >

        @if (! $selectedConversation)

            <div class="empty-thread">

                <x-heroicon-o-chat-bubble-left-right
                    class="w-10 h-10 opacity-50"
                />

                <span>
                    Select a conversation to start messaging
                </span>

            </div>

        @else

            @php
                $activeConversation = $conversations
                    ->firstWhere('id', $selectedConversation);

                $otherParticipant = $activeConversation
                    ?->participants
                    ->firstWhere('id', '!=', auth()->id());

                $threadName = $otherParticipant?->name
                    ?? 'Legal Office';

                $threadInitials = collect(explode(' ', $threadName))
                    ->filter()
                    ->map(
                        fn ($part) =>
                            strtoupper(substr($part, 0, 1))
                    )
                    ->take(2)
                    ->join('');
            @endphp


            {{-- THREAD HEADER --}}
            <div class="thread-header">

                <div class="t-avatar">
                    {{ $threadInitials ?: 'LO' }}
                </div>

                <div style="flex: 1;">

                    <div class="t-name">
                        {{ $threadName }}
                    </div>

                    <div class="t-sub">

                        @if ($activeConversation?->document)
                            Document #{{ $activeConversation->document_id }}
                        @endif

                        @if ($activeConversation?->assignedStaff)
                            • Primary handler:
                            {{ $activeConversation->assignedStaff->name }}
                        @endif

                    </div>

                </div>

                <button
                    type="button"
                    wire:click="$set('selectedConversation', null)"
                    title="Close conversation"
                >
                    ✕
                </button>

            </div>


            {{-- MESSAGES --}}
            <div
                class="thread-body"
                id="threadBody"
            >

                @forelse ($messages as $message)

                    @php
                        $isOwn =
                            (int) $message->sender_id
                            === (int) auth()->id();

                        $senderName =
                            $message->sender?->name
                            ?? 'Unknown User';

                        $senderInitials = collect(
                            explode(' ', $senderName)
                        )
                            ->filter()
                            ->map(
                                fn ($part) =>
                                    strtoupper(substr($part, 0, 1))
                            )
                            ->take(2)
                            ->join('');
                    @endphp

                    <div
                        class="t-msg-row {{ $isOwn ? 'own' : '' }}"
                        wire:key="message-{{ $message->id }}"
                    >

                        <div class="t-msg-avatar">

                            @if ($isOwn)
                                You
                            @else
                                {{ $senderInitials ?: '?' }}
                            @endif

                        </div>

                        <div>

                            @unless ($isOwn)

                                <div class="t-sender-name">
                                    {{ $senderName }}
                                </div>

                            @endunless

                            <div class="t-bubble">
                                {{ $message->body }}
                            </div>

                            <div class="t-time">
                                {{ $message->created_at->format('M d, g:i A') }}
                            </div>

                        </div>

                    </div>

                @empty

                    <div class="empty-thread">
                        No messages yet. Start the conversation.
                    </div>

                @endforelse

            </div>


            {{-- MESSAGE INPUT --}}
    @if ($activeConversation && $activeConversation->status === 'active')

        <div class="thread-footer">

            <input
                type="text"
                wire:model="newMessage"
                wire:keydown.enter="sendMessage"
                placeholder="Type a message..."
                maxlength="5000"
                autocomplete="off"
            >

            <button
                type="button"
                wire:click="sendMessage"
                wire:loading.attr="disabled"
                title="Send message"
            >
                <span wire:loading.remove wire:target="sendMessage">
                    ➤
                </span>

                <span wire:loading wire:target="sendMessage">
                    ...
                </span>
            </button>

        </div>

        @error('newMessage')
            <div
                style="
                    color: #dc2626;
                    font-size: 12px;
                    padding: 4px 16px 10px;
                "
            >
                {{ $message }}
            </div>
        @enderror

    @else

        <div
            class="thread-footer"
            style="
                justify-content: center;
                color: #6b7280;
            "
        >
            This conversation is closed.
        </div>

    @endif

    @endif

    </div>

</div>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('messages-read', () => {
            window.location.reload();
        });
    });
</script>

</x-filament-panels::page>