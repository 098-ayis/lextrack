<x-filament-panels::page>
<style>
    [x-cloak] {
        display: none !important;
    }

    /* =========================================================
       MAIN LAYOUT
    ========================================================= */

    .msg-wrap {
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 16px;
        height: calc(100vh - 180px);
    }


    /* =========================================================
       CONVERSATION LIST
    ========================================================= */

    .msg-list {
        display: flex;
        flex-direction: column;
        overflow: hidden;

        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
    }

    .msg-list-header {
        padding: 14px 16px;
        border-bottom: 1px solid #e5e7eb;
    }

    .msg-list-header h3 {
        margin-bottom: 10px;

        color: #111827;

        font-size: 16px;
        font-weight: 700;
    }

    .msg-items {
        flex: 1;
        overflow-y: auto;
    }


    /* =========================================================
       SEARCH
    ========================================================= */

    .msg-search {
        position: relative;
    }

    .msg-search input {
        width: 100%;

        padding: 8px 12px 8px 32px;

        background: #ffffff;
        border: 1.5px solid #e5e7eb;
        border-radius: 18px;

        color: #111827;

        font-size: 12.5px;

        outline: none;
    }

    .msg-search input::placeholder {
        color: #9ca3af;
    }

    .msg-search input:focus {
        border-color: #6366f1;
    }

    .msg-search .s-icon {
        position: absolute;
        top: 50%;
        left: 10px;

        transform: translateY(-50%);

        color: #6b7280;
    }


    /* =========================================================
       CONVERSATION ITEM
    ========================================================= */

    .msg-item {
        display: flex;
        align-items: center;
        gap: 10px;

        padding: 12px 16px;

        border-bottom: 1px solid #e5e7eb;

        cursor: pointer;

        transition: background 0.1s;
    }

    .msg-item:hover {
        background: #f0f1ff;
    }

    .msg-item.active {
        background: #f0f1ff;
        border-left: 3px solid #6366f1;
    }

    .msg-item.unread .m-name,
    .msg-item.unread .m-preview {
        color: #111827;
        font-weight: 700;
    }


    /* =========================================================
       CONVERSATION AVATAR
    ========================================================= */

    .m-avatar {
        position: relative;

        display: flex;
        align-items: center;
        justify-content: center;

        width: 40px;
        height: 40px;
        min-width: 40px;

        overflow: hidden;

        background: #e0e7ff;
        border-radius: 50%;

        color: #4f46e5;

        font-size: 13px;
        font-weight: 700;

        flex-shrink: 0;
    }

    .m-avatar img,
    .m-avatar-img {
        display: block;

        width: 100%;
        height: 100%;

        border-radius: 50%;

        object-fit: cover;
        object-position: center;
    }

    .m-avatar .dot {
        position: absolute;
        right: -1px;
        bottom: -1px;

        width: 11px;
        height: 11px;

        background: #dc2626;
        border: 2px solid #ffffff;
        border-radius: 50%;
    }


    /* =========================================================
       CONVERSATION INFORMATION
    ========================================================= */

    .m-info {
        flex: 1;
        min-width: 0;
        overflow: hidden;
    }

    .m-sub {
        margin-bottom: 2px;

        color: #6366f1;

        font-size: 11px;
        font-weight: 600;
    }

    .m-name {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;

        color: #111827;

        font-size: 13.5px;
        font-weight: 600;
    }

    .m-name > span:first-child {
        overflow: hidden;

        white-space: nowrap;
        text-overflow: ellipsis;
    }

    .m-time {
        flex-shrink: 0;

        color: #6b7280;

        font-size: 10.5px;
        font-weight: 400;
    }

    .m-preview {
        overflow: hidden;

        color: #6b7280;

        font-size: 12px;

        white-space: nowrap;
        text-overflow: ellipsis;
    }


    /* =========================================================
       UNREAD COUNT
    ========================================================= */

    .unread-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;

        min-width: 18px;
        height: 18px;

        padding: 0 5px;

        flex-shrink: 0;

        background: #dc2626;
        border-radius: 999px;

        color: #ffffff;

        font-size: 10px;
        font-weight: 700;
        line-height: 1;
    }


    /* =========================================================
       MESSAGE THREAD
    ========================================================= */

    .msg-thread {
        display: flex;
        flex-direction: column;
        overflow: hidden;

        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
    }


    /* =========================================================
       THREAD HEADER
    ========================================================= */

    .thread-header {
        display: flex;
        align-items: center;
        gap: 12px;

        padding: 14px 18px;

        border-bottom: 1px solid #e5e7eb;
    }

    .t-avatar {
        display: flex;
        align-items: center;
        justify-content: center;

        width: 40px;
        height: 40px;
        min-width: 40px;

        overflow: hidden;

        background: #f0f1ff;
        border-radius: 50%;

        color: #6366f1;

        font-size: 13px;
        font-weight: 700;

        flex: 0 0 40px;
    }

    .t-avatar img,
    .t-avatar-img {
        display: block;

        width: 100%;
        height: 100%;

        border-radius: 50%;

        object-fit: cover;
        object-position: center;
    }

    .t-name {
        color: #111827;

        font-size: 15px;
        font-weight: 700;
    }

    .t-sub {
        color: #6b7280;

        font-size: 12px;
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


    /* =========================================================
       MESSAGE BODY
    ========================================================= */

    .thread-body {
        display: flex;
        flex: 1;
        flex-direction: column;
        justify-content: flex-start;
        gap: 12px;

        overflow-y: auto;

        padding: 18px;

        background: #f9fafb;
    }


    /* =========================================================
       MESSAGE ROW
    ========================================================= */

    .t-msg-row {
        display: flex;
        align-items: flex-end;
        gap: 8px;

        width: fit-content;
        max-width: 70%;

        margin: 0;
    }

    /* Current user's messages */
    .t-msg-row.own {
        align-self: flex-end;
        flex-direction: row-reverse;
    }


    /* =========================================================
       MESSAGE AVATAR
    ========================================================= */

    .t-msg-avatar {
        display: flex;
        align-items: center;
        justify-content: center;

        width: 28px;
        height: 28px;
        min-width: 28px;
        min-height: 28px;

        overflow: hidden;

        background: #e0e7ff;
        border-radius: 50%;

        color: #4f46e5;

        font-size: 11px;
        font-weight: 700;

        flex: 0 0 28px;
    }

    .t-msg-avatar img,
    .t-msg-avatar-img {
        display: block;

        width: 100%;
        height: 100%;

        border-radius: 50%;

        object-fit: cover;
        object-position: center;
    }


    /* =========================================================
       MESSAGE CONTENT
    ========================================================= */

    .t-message-content {
        display: flex;
        flex-direction: column;

        width: fit-content;
        max-width: 100%;
        min-width: 0;
    }

    .t-msg-row:not(.own) .t-message-content {
        align-items: flex-start;
    }

    .t-msg-row.own .t-message-content {
        align-items: flex-end;
    }

    .t-sender-name {
        margin: 0 0 3px;

        color: #374151;

        font-size: 11px;
        font-weight: 600;
    }

    .t-msg-row.own .t-sender-name {
        text-align: right;
    }


    /* =========================================================
       MESSAGE BUBBLE
    ========================================================= */

    .t-bubble {
        display: inline-block;

        width: fit-content;
        min-width: 0;
        max-width: 100%;

        padding: 10px 16px;

        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;

        color: #111827;

        font-size: 13.5px;
        line-height: 1.5;

        white-space: normal;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .t-msg-row.own .t-bubble {
        background: #6366f1;
        border: none;

        color: #ffffff;
    }


    /* =========================================================
       MESSAGE TIME
    ========================================================= */

    .t-time {
        margin-top: 4px;

        color: #6b7280;

        font-size: 10.5px;
    }

    .t-msg-row.own .t-time {
        text-align: right;
    }


    /* =========================================================
       MESSAGE INPUT
    ========================================================= */

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

        background: #ffffff;
        border: 1.5px solid #e5e7eb;
        border-radius: 22px;

        color: #111827;

        font-size: 13.5px;

        outline: none;
    }

    .thread-footer input::placeholder {
        color: #9ca3af;
    }

    .thread-footer input:focus {
        border-color: #6366f1;
    }

    .thread-footer button {
        display: flex;
        align-items: center;
        justify-content: center;

        width: 40px;
        height: 40px;

        border: none;
        border-radius: 50%;

        background: #6366f1;

        color: #ffffff;

        cursor: pointer;

        font-size: 15px;

        transition: background 0.15s;
    }

    .thread-footer button:hover {
        background: #4f46e5;
    }

    .thread-footer button:disabled {
        cursor: not-allowed;
        opacity: 0.6;
    }


    /* =========================================================
       EMPTY STATE
    ========================================================= */

    .empty-thread {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10px;

        height: 100%;

        color: #6b7280;

        font-size: 14px;
    }


    /* =========================================================
       DARK MODE
    ========================================================= */

    .dark .msg-list,
    .dark .msg-thread {
        background: #111827;
        border-color: #374151;

        color: #f9fafb;
    }

    .dark .msg-list-header,
    .dark .thread-header,
    .dark .thread-footer {
        border-color: #374151;
    }

    .dark .msg-list-header h3,
    .dark .m-name,
    .dark .t-name {
        color: #f9fafb;
    }

    .dark .msg-search input,
    .dark .thread-footer input {
        background: #1f2937;
        border-color: #4b5563;

        color: #f9fafb;
    }

    .dark .msg-search input::placeholder,
    .dark .thread-footer input::placeholder {
        color: #9ca3af;
    }

    .dark .msg-search input:focus,
    .dark .thread-footer input:focus {
        border-color: #818cf8;
    }

    .dark .msg-search .s-icon,
    .dark .m-time,
    .dark .m-preview,
    .dark .t-sub,
    .dark .t-time,
    .dark .empty-thread {
        color: #9ca3af;
    }

    .dark .msg-item {
        border-bottom-color: #374151;
    }

    .dark .msg-item:hover,
    .dark .msg-item.active {
        background: #1e1b4b;
    }

    .dark .msg-item.active {
        border-left-color: #6366f1;
    }

    .dark .msg-item.unread .m-name,
    .dark .msg-item.unread .m-preview {
        color: #f9fafb;
    }

    .dark .m-sub {
        color: #a5b4fc;
    }

    .dark .thread-body {
        background: #0f172a;
    }

    .dark .t-avatar {
        background: #1e1b4b;

        color: #a5b4fc;
    }

    .dark .t-msg-avatar {
        background: #312e81;

        color: #c7d2fe;
    }

    .dark .t-sender-name {
        color: #d1d5db;
    }

    .dark .t-bubble {
        background: #1f2937;
        border-color: #374151;

        color: #f3f4f6;
    }

    .dark .t-msg-row.own .t-bubble {
        background: #6366f1;
        border: none;

        color: #ffffff;
    }

    .dark .thread-footer {
        background: #111827;
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


    /* =========================================================
       RESPONSIVE
    ========================================================= */

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

        .t-msg-row {
            max-width: 85%;
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

                        $officeName = 'Legal Affairs Office';

                        $officeLogo = asset('images/bu-lao.png');

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

                            <img
                                src="{{ $officeLogo }}"
                                alt="{{ $officeName }}"
                                class="m-avatar-img"
                            >

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
                        <img
                            src="{{ asset('images/bu-lao.png') }}"
                            alt="Legal Affairs Office"
                            class="t-avatar-img"
                        >
                    </div>

                    <div style="flex: 1;">

                        <div class="t-name">
                            Legal Affairs Office
                        </div>

                        <div class="t-sub">

                            @if ($activeConversation?->document)
                                {{ $activeConversation->document->lao_number ?? 'Not assigned' }}
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

                        /*
                        * Client side:
                        * - own messages = current client identity
                        * - all staff messages = Legal Affairs Office
                        */
                        $displayName = $isOwn
                            ? (auth()->user()->name ?? 'You')
                            : 'Legal Affairs Office';

                        $displayPhoto = $isOwn
                            ? auth()->user()?->profile_photo_url
                            : asset('images/bu-lao.png');

                        $displayInitials = collect(
                            explode(' ', $displayName)
                        )
                            ->filter()
                            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                            ->take(2)
                            ->join('');
                    @endphp

                    <div
                        class="t-msg-row {{ $isOwn ? 'own' : '' }}"
                        wire:key="message-{{ $message->id }}"
                    >

                        <div class="t-msg-avatar">

                            @if ($displayPhoto)

                                <img
                                    src="{{ $displayPhoto }}"
                                    alt="{{ $displayName }}"
                                    class="t-msg-avatar-img"
                                >

                            @else

                                {{ $displayInitials ?: '?' }}

                            @endif

                        </div>

                        <div class="t-message-content">

                            @unless ($isOwn)

                                <div class="t-sender-name">
                                    Legal Affairs Office
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
