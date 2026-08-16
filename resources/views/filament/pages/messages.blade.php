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

    <div class="msg-wrap">

        {{-- CONVERSATION LIST --}}
        <div class="msg-list">

            <div class="msg-list-header">

                <h3>Conversations</h3>

                <div class="msg-search">

                    <span class="s-icon">
                        <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                    </span>

                    <input
                        type="text"
                        id="msgSearch"
                        placeholder="Search Messages"
                        oninput="filterConvos(this.value)"
                    >

                </div>

            </div>

            <div class="msg-items" id="msgItems"></div>

        </div>


        {{-- MESSAGE THREAD --}}
        <div class="msg-thread" id="msgThread">

            <div class="empty-thread">

                <x-heroicon-o-chat-bubble-left-right
                    class="w-10 h-10 opacity-50"
                />

                <span>
                    Select a conversation to start messaging
                </span>

            </div>

        </div>

    </div>


    <script>

        const ACTIVE_CONVO_KEY = 'lextrack_active_convo_v1';

        let activeConvoId =
            localStorage.getItem(ACTIVE_CONVO_KEY) || null;


        function renderConvoItems() {

            const convos = lxGetConvos();

            document.getElementById('msgItems').innerHTML =
                convos.map(c => {

                    const last =
                        c.messages[c.messages.length - 1];

                    return `
                        <div
                            class="msg-item
                                ${c.unread ? 'unread' : ''}
                                ${c.id === activeConvoId ? 'active' : ''}"
                            onclick="openThread('${c.id}')"
                        >

                            <div class="m-avatar">

                                ${c.avatar}

                                ${
                                    c.unread
                                        ? '<span class="dot"></span>'
                                        : ''
                                }

                            </div>

                            <div class="m-info">

                                <div class="m-sub">
                                    ${c.subtitle}
                                </div>

                                <div class="m-name">

                                    <span>
                                        ${c.name}
                                    </span>

                                    <span class="m-time">
                                        ${
                                            last
                                                ? last.time.split(',')[0]
                                                : ''
                                        }
                                    </span>

                                </div>

                                <div class="m-preview">
                                    ${last ? last.text : ''}
                                </div>

                            </div>

                        </div>
                    `;

                }).join('');
        }


        function openThread(convoId) {

            activeConvoId = convoId;

            localStorage.setItem(
                ACTIVE_CONVO_KEY,
                convoId
            );

            const convos = lxGetConvos();

            const convo =
                convos.find(c => c.id === convoId);

            if (!convo) return;

            if (convo.unread) {

                convo.unread = false;

                lxSaveConvos(convos);

            }

            renderConvoItems();

            renderThread();

        }


        function closeThread() {

            activeConvoId = null;

            localStorage.removeItem(
                ACTIVE_CONVO_KEY
            );

            renderConvoItems();

            renderThread();

        }


        function renderThread() {

            const convos = lxGetConvos();

            const convo =
                convos.find(c => c.id === activeConvoId);

            const thread =
                document.getElementById('msgThread');


            if (!convo) {

                activeConvoId = null;

                localStorage.removeItem(
                    ACTIVE_CONVO_KEY
                );

                thread.innerHTML = `
                    <div class="empty-thread">

                        <svg
                            width="40"
                            height="40"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >

                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>

                        </svg>

                        <span>
                            Select a conversation to start messaging
                        </span>

                    </div>
                `;

                return;
            }


            thread.innerHTML = `

                <div class="thread-header">

                    <div class="t-avatar">
                        ${convo.avatar}
                    </div>

                    <div style="flex:1;">

                        <div class="t-name">
                            ${convo.name}
                        </div>

                        <div class="t-sub">
                            ${convo.subtitle}
                        </div>

                    </div>

                    <button
                        onclick="closeThread()"
                        title="Close conversation"
                        style="
                            background:none;
                            border:none;
                            font-size:18px;
                            color:#6b7280;
                            cursor:pointer;
                        "
                    >
                        ✕
                    </button>

                </div>


                <div
                    class="thread-body"
                    id="threadBody"
                >

                    ${convo.messages.map(m => `

                        <div
                            class="t-msg-row
                                ${m.from === 'me' ? 'own' : ''}"
                        >

                            <div class="t-msg-avatar">

                                ${
                                    m.from === 'me'
                                        ? 'MD'
                                        : convo.avatar
                                }

                            </div>

                            <div>

                                <div class="t-bubble">
                                    ${m.text}
                                </div>

                                <div class="t-time">
                                    ${m.time}
                                </div>

                            </div>

                        </div>

                    `).join('')}

                </div>


                <div class="thread-footer">

                    <input
                        type="text"
                        id="threadInput"
                        placeholder="Type a message..."
                        onkeydown="
                            if(event.key === 'Enter')
                                sendThreadMsg()
                        "
                    >

                    <button
                        onclick="sendThreadMsg()"
                        title="Send message"
                    >
                        ➤
                    </button>

                </div>

            `;


            const body =
                document.getElementById('threadBody');

            body.scrollTop =
                body.scrollHeight;

        }


        function sendThreadMsg() {

            const input =
                document.getElementById('threadInput');

            if (!input) return;

            const text =
                input.value.trim();

            if (!text) return;


            const convos = lxGetConvos();

            const convo =
                convos.find(c => c.id === activeConvoId);

            if (!convo) return;


            const time =
                new Date().toLocaleString(
                    'en-US',
                    {
                        month: 'short',
                        day: 'numeric',
                        hour: 'numeric',
                        minute: '2-digit'
                    }
                );


            convo.messages.push({
                from: 'me',
                text: text,
                time: time
            });


            lxSaveConvos(convos);

            input.value = '';

            renderThread();

            renderConvoItems();


            setTimeout(() => {

                const c2s = lxGetConvos();

                const c2 =
                    c2s.find(
                        c => c.id === activeConvoId
                    );

                if (!c2) return;


                const t2 =
                    new Date().toLocaleString(
                        'en-US',
                        {
                            month: 'short',
                            day: 'numeric',
                            hour: 'numeric',
                            minute: '2-digit'
                        }
                    );


                c2.messages.push({
                    from: 'them',
                    text: 'Noted, thank you for the update!',
                    time: t2
                });


                lxSaveConvos(c2s);

                if (
                    c2.id === activeConvoId
                ) {
                    renderThread();
                }

                renderConvoItems();

            }, 1400);

        }


        function filterConvos(q) {

            document
                .querySelectorAll('#msgItems .msg-item')
                .forEach(item => {

                    item.style.display =
                        item.textContent
                            .toLowerCase()
                            .includes(
                                q.toLowerCase()
                            )
                            ? ''
                            : 'none';

                });

        }


        document.addEventListener(
            'DOMContentLoaded',
            () => {

                renderConvoItems();

                if (
                    activeConvoId &&
                    lxGetConvos().some(
                        c => c.id === activeConvoId
                    )
                ) {

                    openThread(activeConvoId);

                } else {

                    renderThread();

                }

            }
        );

    </script>

</x-filament-panels::page>