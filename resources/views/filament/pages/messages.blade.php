<x-filament-panels::page>

<style>
    /* =========================
       MAIN LAYOUT
    ========================== */

    .msg-wrap {
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 16px;
        height: calc(100vh - 180px);
    }


    /* =========================
       CONVERSATION LIST
    ========================== */

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

    .msg-items {
        flex: 1;
        overflow-y: auto;
    }


    /* =========================
       SEARCH
    ========================== */

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


    /* =========================
       CONVERSATION ITEM
    ========================== */

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

    .msg-item.unread .m-preview,
    .msg-item.unread .m-name {
        font-weight: 700;
        color: #111827;
    }


    /* =========================
       CONVERSATION AVATAR
    ========================== */

    .m-avatar {
        width: 40px;
        height: 40px;
        min-width: 40px;

        border-radius: 50%;
        overflow: hidden;

        display: flex;
        align-items: center;
        justify-content: center;

        background: #ffe0b2;
        color: #a05200;

        font-size: 13px;
        font-weight: 700;

        flex-shrink: 0;
        position: relative;
    }

    .m-avatar-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        border-radius: 50%;
        display: block;
    }


    /* =========================
       CONVERSATION INFO
    ========================== */

    .m-info {
        overflow: hidden;
        flex: 1;
        min-width: 0;
    }

    .m-name {
        font-size: 13.5px;
        font-weight: 600;
        color: #111827;

        display: flex;
        justify-content: space-between;
        gap: 8px;
    }

    .m-time {
        font-size: 10.5px;
        color: #6b7280;
        font-weight: 400;
        flex-shrink: 0;
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


    /* =========================
       UNREAD BADGE
    ========================== */

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

        flex-shrink: 0;
    }


    /* =========================
       MESSAGE THREAD
    ========================== */

    .msg-thread {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;

        display: flex;
        flex-direction: column;

        overflow: hidden;
    }


    /* =========================
       THREAD HEADER
    ========================== */

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
        min-width: 40px;

        border-radius: 50%;
        overflow: hidden;

        display: flex;
        align-items: center;
        justify-content: center;

        background: #f3f8fc;
        color: #1b6ca8;

        flex: 0 0 40px;
    }

    .t-avatar-img {
        width: 100%;
        height: 100%;

        object-fit: cover;
        object-position: center;

        border-radius: 50%;
        display: block;
    }

    .t-name {
        font-size: 15px;
        font-weight: 700;
    }

    .t-sub {
        font-size: 12px;
        color: #6b7280;
    }


    /* =========================
       THREAD BODY
    ========================== */

    .thread-body {
        flex: 1;
        overflow-y: auto;

        padding: 18px;

        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        gap: 12px;

        background: #f9fafb;
    }


    /* =========================
       MESSAGE ROW
    ========================== */

    .t-msg-row {
        display: flex;
        align-items: flex-end;
        gap: 8px;

        width: fit-content;
        max-width: 70%;

        margin: 0;
    }

    /* Client messages = LEFT */
    .t-msg-row.client-message {
        align-self: flex-start;
        flex-direction: row;
    }

    /* Admin/staff messages = RIGHT */
    .t-msg-row.staff-message {
        align-self: flex-end;
        flex-direction: row-reverse;
    }


    /* =========================
       MESSAGE AVATAR
    ========================== */

    .t-msg-avatar {
        width: 28px;
        height: 28px;
        min-width: 28px;

        border-radius: 50%;
        overflow: hidden;

        background: #ffe0b2;

        display: flex;
        align-items: center;
        justify-content: center;

        font-size: 11px;
        font-weight: 700;
        color: #a05200;

        flex: 0 0 28px;
    }

    .t-msg-avatar-img {
        width: 100%;
        height: 100%;

        object-fit: cover;
        object-position: center;

        border-radius: 50%;
        display: block;
    }

    .t-msg-row.staff-message .t-msg-avatar {
        background: #dbeafe;
        color: #1b6ca8;
    }


    /* =========================
       MESSAGE CONTENT
    ========================== */

    .t-message-content {
        display: flex;
        flex-direction: column;

        width: fit-content;
        max-width: 100%;
        min-width: 0;
    }

    .t-msg-row.client-message .t-message-content {
        align-items: flex-start;
    }

    .t-msg-row.staff-message .t-message-content {
        align-items: flex-end;
    }


    /* =========================
       SENDER NAME
    ========================== */

    .t-sender-name {
        font-size: 11px;
        color: #6b7280;
        margin: 0 0 3px;
    }

    .t-msg-row.staff-message .t-sender-name {
        text-align: right;
    }


    /* =========================
       MESSAGE BUBBLE
    ========================== */

    .t-bubble {
        display: inline-block;

        width: fit-content;
        min-width: 0;
        max-width: 100%;

        padding: 10px 16px;

        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 16px;

        font-size: 13.5px;
        line-height: 1.5;

        white-space: normal;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    /* Admin/staff bubble */
    .t-msg-row.staff-message .t-bubble {
        background: #1b6ca8;
        color: white;
        border: none;
    }


    /* =========================
       MESSAGE TIME
    ========================== */

    .t-time {
        font-size: 10.5px;
        color: #6b7280;
        margin-top: 4px;
    }

    .t-msg-row.staff-message .t-time {
        text-align: right;
    }


    /* =========================
       MESSAGE INPUT
    ========================== */

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
        width: 40px;
        height: 40px;

        background: #1b6ca8;
        color: white;

        border: none;
        border-radius: 50%;

        cursor: pointer;
        font-size: 15px;
    }


    /* =========================
       EMPTY THREAD
    ========================== */

    .empty-thread {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
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


    /* =========================
       RESPONSIVE
    ========================== */

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

    {{-- =========================================================
        CONVERSATION LIST
    ========================================================== --}}
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
                    /*
                     * For the staff inbox, show the client who
                     * originally owns/created the conversation.
                     */
                    $client = $conversation?->document?->user;
                    $clientName = $client?->name ?? 'Unknown Client';
                    $clientPhoto = $client?->profile_photo_url;
                    $documentTitle = $conversation->document?->particulars
                    ?? 'Untitled Document';

                    $initials = collect(explode(' ', $clientName))
                        ->filter()
                        ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                        ->take(2)
                        ->join('');

                    $latestMessage = $conversation
                        ->messages
                        ->sortBy('created_at')
                        ->last();

                    $searchText = strtolower(
                        $clientName . ' ' .
                        ($conversation->document?->lao_number ?? '') . ' ' .
                        ($conversation->document?->particulars ?? '')
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

                        @if ($clientPhoto)

                            <img
                                src="{{ $clientPhoto }}"
                                alt="{{ $clientName }}"
                                class="m-avatar-img"
                                referrerpolicy="no-referrer"
                            >

                        @else

                            {{ $initials ?: 'CL' }}

                        @endif

                        @if ($conversation->unread_messages_count > 0)
                            <span class="dot"></span>
                        @endif

                    </div>


                    <div class="m-info">

                        <div class="m-sub">

                            @if ($conversation->document?->lao_number)

                                {{ $conversation->document->lao_number }}

                            @else

                                Document #{{ $conversation->document_id }}

                            @endif

                        </div>


                        <div class="m-name">

                            <span>
                                {{ $documentTitle }}
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
                                    (int) $latestMessage->sender_id
                                    === (int) auth()->id()
                                )
                                    You:
                                @endif

                                {{ \Illuminate\Support\Str::limit(
                                    $latestMessage->body,
                                    55
                                ) }}

                            @else

                                No messages yet

                            @endif

                        </div>

                    </div>

                </div>

            @empty

                <div
                    style="
                        padding: 24px;
                        text-align: center;
                        color: #6b7280;
                        font-size: 13px;
                    "
                >
                    No conversations available.
                </div>

            @endforelse

        </div>

    </div>



    {{-- =========================================================
        MESSAGE THREAD
    ========================================================== --}}
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

                $client = $activeConversation?->document?->user;

                $clientName = $client?->name ?? 'Unknown Client';

                $clientPhoto = $client?->profile_photo_url;

                $clientInitials = collect(explode(' ', $clientName))
                    ->filter()
                    ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                    ->take(2)
                    ->join('');

                $documentTitle = $activeConversation->document?->particulars
                    ?? 'Untitled Document';
            @endphp


            {{-- =====================================================
                THREAD HEADER
            ====================================================== --}}
            <div class="thread-header">

                <div class="t-avatar">
                    @if ($clientPhoto)

                        <img
                            src="{{ $clientPhoto }}"
                            alt="{{ $clientName }}"
                            class="t-avatar-img"
                            referrerpolicy="no-referrer"
                        >

                    @else

                        {{ $clientInitials ?: 'CL' }}

                    @endif
                </div>


                <div style="flex: 1;">

                    <div class="t-name">
                        {{ $documentTitle }}
                    </div>


                    <div class="t-sub">

                        @if ($activeConversation?->document?->lao_number)

                            {{ $activeConversation->document->lao_number }}

                        @elseif ($activeConversation?->document_id)

                            Document #{{ $activeConversation->document_id }}

                        @endif


                        @if ($activeConversation?->assignedStaff)

                            &nbsp; • &nbsp;

                            Primary handler:
                            {{ $activeConversation->assignedStaff->name }}

                        @else

                            &nbsp; • &nbsp;

                            Unassigned

                        @endif

                    </div>

                </div>


                {{-- Optional Assign to Me --}}
                @if (
                    $activeConversation
                    && ! $activeConversation->assigned_to
                )

                    <button
                        type="button"
                        wire:click="assignToMe({{ $activeConversation->id }})"
                        style="
                            background: #1b6ca8;
                            color: white;
                            border: none;
                            border-radius: 7px;
                            padding: 7px 12px;
                            font-size: 12px;
                            font-weight: 600;
                            cursor: pointer;
                        "
                    >
                        Assign to Me
                    </button>

                @endif


                <button
                    type="button"
                    wire:click="$set('selectedConversation', null)"
                    title="Close thread"
                    style="
                        background: none;
                        border: none;
                        font-size: 18px;
                        color: #6b7280;
                        cursor: pointer;
                        margin-left: 8px;
                    "
                >
                    ✕
                </button>

            </div>



            {{-- =====================================================
                MESSAGE BODY
            ====================================================== --}}
            <div
                class="thread-body"
                id="threadBody"
            >

                @forelse ($messages as $message)

                    @php
                        $sender = $message->sender;

                        $senderName = $sender?->name ?? 'Unknown User';

                        $senderPhoto = $sender?->profile_photo_url;

                        $clientUserId = $activeConversation->document?->user_id;

                        /*
                        * Admin/shared inbox rule:
                        * client = conversation creator
                        * everyone else = Legal Office staff
                        */
                        $isStaffMessage =
                            (int) $message->sender_id
                            !== (int) $clientUserId;

                        $isCurrentUser =
                            (int) $message->sender_id
                            === (int) auth()->id();

                        $senderInitials = collect(explode(' ', $senderName))
                            ->filter()
                            ->map(
                                fn ($part) =>
                                    strtoupper(substr($part, 0, 1))
                            )
                            ->take(2)
                            ->join('');
                    @endphp

                    <div
                        class="
                            t-msg-row
                            {{ $isStaffMessage ? 'staff-message' : 'client-message' }}
                        "
                        wire:key="message-{{ $message->id }}"
                    >

                        <div class="t-msg-avatar">

                            @if ($senderPhoto)

                                <img
                                    src="{{ $senderPhoto }}"
                                    alt="{{ $senderName }}"
                                    class="t-msg-avatar-img"
                                    referrerpolicy="no-referrer"
                                >

                            @else

                                <span>
                                    {{ $senderInitials ?: '?' }}
                                </span>

                            @endif

                        </div>

                        <div class="t-message-content">

                            <div class="t-sender-name">
                                {{ $senderName }}
                            </div>

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

                        <x-heroicon-o-chat-bubble-left-right
                            class="w-8 h-8 opacity-40"
                        />

                        <span>
                            No messages yet.
                        </span>

                    </div>

                @endforelse

            </div>



            {{-- =====================================================
                MESSAGE INPUT
            ====================================================== --}}
            @if (
                $activeConversation
                && $activeConversation->status === 'active'
            )

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
                        wire:target="sendMessage"
                        title="Send message"
                    >

                        <span
                            wire:loading.remove
                            wire:target="sendMessage"
                        >
                            ➤
                        </span>

                        <span
                            wire:loading
                            wire:target="sendMessage"
                        >
                            ...
                        </span>

                    </button>

                </div>


                @error('newMessage')

                    <div
                        style="
                            color: #dc2626;
                            font-size: 12px;
                            padding: 0 18px 12px;
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
                        font-size: 13px;
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