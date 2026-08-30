<x-filament-panels::page>

    <style>
        [x-cloak] {
            display: none !important;
        }

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
            color: #111827;
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
            background: #ffffff;
            color: #111827;
        }

        .msg-search input::placeholder {
            color: #9ca3af;
        }

        .msg-search input:focus {
            border-color: #6366f1;
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
            background: #f0f1ff;
        }

        .msg-item.active {
            background: #f0f1ff;
            border-left: 3px solid #6366f1;
        }

        .m-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e7ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            color: #4f46e5;
            flex-shrink: 0;
            position: relative;
        }

        .t-avatar img,
        .t-msg-avatar img {
            display: block;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
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
            color: #6366f1;
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
            background: #f0f1ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            color: #6366f1;
        }

        .t-name {
            font-size: 15px;
            font-weight: 700;
            color: #111827;
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
            background: #e0e7ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            color: #4f46e5;
            align-self: flex-end;
            flex-shrink: 0;
        }

        .t-msg-row.own .t-msg-avatar {
            background: #e0e7ff;
            color: #4f46e5;
        }

        .t-sender-name {
            margin-bottom: 4px;
            color: #374151;
            font-size: 11px;
            font-weight: 600;
        }

        .t-bubble {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 10px 16px;
            font-size: 13.5px;
            line-height: 1.5;
            color: #111827;
        }

        .t-msg-row.own .t-bubble {
            background: #6366f1;
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
            border-color: #6366f1;
        }

        .thread-footer button {
            background: #6366f1;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            font-size: 15px;
        }

        .thread-footer button:hover {
            background: #4f46e5;
        }

        .thread-header > button {
            border: 0;
            background: transparent;
            color: #6b7280;
            cursor: pointer;
            font-size: 18px;
        }

        .thread-header > button:hover {
            color: #111827;
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

        .unread-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: 999px;
            background: #dc2626;
            color: white;
            font-size: 10px;
            font-weight: 700;
        }

        .dark .msg-list {
            background: #111827;
            border-color: #374151;
            color: #f9fafb;
        }

        .dark .msg-list-header {
            border-bottom-color: #374151;
        }

        .dark .msg-list-header h3,
        .dark .m-name {
            color: #f9fafb;
        }

        .dark .msg-search input {
            border-color: #4b5563;
            background: #1f2937;
            color: #f9fafb;
        }

        .dark .msg-search input::placeholder {
            color: #9ca3af;
        }

        .dark .msg-search input:focus {
            border-color: #818cf8;
        }

        .dark .msg-search .s-icon,
        .dark .m-time,
        .dark .m-preview {
            color: #9ca3af;
        }

        .dark .msg-item {
            border-bottom-color: #374151;
        }

        .dark .msg-item:hover {
            background: #1e1b4b;
        }

        .dark .msg-item.active {
            background: #1e1b4b;
            border-left-color: #6366f1;
        }

        .dark .msg-item.unread .m-preview,
        .dark .msg-item.unread .m-name {
            color: #f9fafb;
        }

        .dark .m-sub {
            color: #a5b4fc;
        }

        .dark .msg-thread {
            background: #111827;
            border-color: #374151;
            color: #f9fafb;
        }

        .dark .thread-header,
        .dark .thread-footer {
            border-color: #374151;
            background: #111827;
        }

        .dark .t-avatar {
            background: #1e1b4b;
            color: #a5b4fc;
        }

        .dark .t-name {
            color: #f9fafb;
        }

        .dark .t-sub,
        .dark .t-time,
        .dark .empty-thread {
            color: #9ca3af;
        }

        .dark .thread-body {
            background: #0f172a;
        }

        .dark .t-msg-avatar {
            background: #312e81;
            color: #c7d2fe;
        }

        .dark .t-msg-row.own .t-msg-avatar {
            background: #1e1b4b;
            color: #c7d2fe;
        }

        .dark .t-sender-name {
            color: #d1d5db;
        }

        .dark .t-bubble {
            border-color: #374151;
            background: #1f2937;
            color: #f3f4f6;
        }

        .dark .t-msg-row.own .t-bubble {
            background: #6366f1;
            color: #ffffff;
        }

        .dark .thread-footer input {
            border-color: #4b5563;
            background: #1f2937;
            color: #f9fafb;
        }

        .dark .thread-footer input::placeholder {
            color: #9ca3af;
        }

        .dark .thread-footer input:focus {
            border-color: #818cf8;
        }

        .dark .thread-footer button {
            background: #6366f1;
        }

        .dark .thread-footer button:hover {
            background: #4f46e5;
        }

        .dark .thread-header > button {
            color: #9ca3af;
        }

        .dark .thread-header > button:hover {
            color: #f9fafb;
        }

        .dark .msg-list .p-6.text-center {
            color: #9ca3af;
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

                        $displayName = $conversation->document?->particulars
                            ?: 'General Conversation';

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
                            ($conversation->document?->lao_number ?? '')
                        );

                        $searchKey = preg_replace(
                            '/[^a-z0-9]/',
                            '',
                            $searchText
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
                        data-search-text="{{ $searchKey }}"
                        x-show="!search.trim() || $el.dataset.searchText.includes(search.trim().toLowerCase().replace(/[^a-z0-9]/g, ''))"
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
                                    {{ $conversation->document->lao_number ?? 'Not assigned' }}
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
                                            ->copy()
                                            ->timezone(config('app.timezone'))
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

                <div
                    x-cloak
                    x-show="search.trim() && !Array.from($root.querySelectorAll('.msg-item')).some(item => item.offsetParent !== null)"
                    class="p-6 text-center text-gray-500 dark:text-gray-400"
                >
                    No conversations match your search.
                </div>

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

                $threadName = $activeConversation?->document?->particulars
                    ?: 'General Conversation';

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
                            {{ $activeConversation->document->lao_number ?? 'Not assigned' }}
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

                        $senderProfilePhoto = $message->sender?->getProfilePhotoUrl();

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

                            @if ($senderProfilePhoto)
                                <img
                                    src="{{ $senderProfilePhoto }}"
                                    alt="{{ $senderName }}"
                                >
                            @elseif ($isOwn)
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
                                {{ $message->created_at
                                    ->copy()
                                    ->timezone(config('app.timezone'))
                                    ->format('M d, g:i A') }}
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
