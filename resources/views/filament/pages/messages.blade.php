<x-filament-panels::page>

<style>
    [x-cloak] {
        display: none !important;
    }

    /* Keep the messages screen inside the viewport. The list and thread
       handle their own scrolling instead of making the whole page scroll. */
    html:has(.msg-wrap),
    body:has(.msg-wrap) {
        overflow: hidden;
    }

    .fi-page:has(.msg-wrap),
    .fi-page:has(.msg-wrap) .fi-page-content,
    .fi-page:has(.msg-wrap) .fi-page-content-ctn {
        min-height: 0;
    }

    /* =========================
       MAIN LAYOUT
    ========================== */

    .msg-wrap {
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 16px;
        height: calc(100vh - 180px);
        height: calc(100dvh - 180px);
        min-height: 0;
        max-height: calc(100dvh - 180px);
        overflow: hidden;
    }


    /* =========================
       CONVERSATION LIST
    ========================== */

    .msg-list {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: visible;
        display: flex;
        flex-direction: column;
        min-height: 0;
        height: 100%;
    }

    .msg-list-header {
        padding: 14px 16px;
        border-bottom: 1px solid #e5e7eb;
    }

    .msg-list-header h3 {
        color: #111827;
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
        background: #f0f1ff;
    }

    .msg-item.active {
        background: #f0f1ff;
        border-left: 3px solid #6366f1;
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

        background: #e0e7ff;
        color: #4f46e5;

        font-size: 13px;
        font-weight: 700;

        flex-shrink: 0;
        position: relative;
    }

    .m-avatar-icon {
    width: 40px;
    height: 40px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 50%;
    background-color: #DBEAFE;

    color: #000000;

    flex-shrink: 0;
    }

    .m-avatar-icon svg {
        width: 24px;
        height: 24px;
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
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;

        display: flex;
        flex-direction: column;
        min-height: 0;
        height: 100%;

        overflow: hidden;
    }

    .thread-header,
    .thread-footer {
        flex-shrink: 0;
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

        background: #f0f1ff;
        color: #6366f1;

        flex: 0 0 40px;
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
        min-height: 0;
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

        background: #e0e7ff;

        display: flex;
        align-items: center;
        justify-content: center;

        font-size: 11px;
        font-weight: 700;
        color: #4f46e5;

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
        background: #e0e7ff;
        color: #4f46e5;
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
        background: #6366f1;
        color: #ffffff;
        border: none;
    }

    .t-msg-row.client-message .t-bubble {
        background: #ffffff;
        color: #111827;
    }

    .t-bubble.revision-bubble {
        padding: 0;
        background: transparent !important;
        border: none !important;
    }

    .t-message-content > .t-bubble + .t-bubble {
        margin-top: 4px;
    }

    .message-reply-context {
        display: flex;
        flex-direction: column;
        width: min(100%, 320px);
        min-width: 0;
        gap: 2px;
        margin-bottom: 4px;
        padding: 6px 9px;
        background: #f0f1ff;
        border-left: 3px solid #6366f1;
        border-radius: 8px;
        color: #4b5563;
        font-size: 11px;
    }

    .message-reply-context-label {
        color: #4f46e5;
        font-weight: 700;
    }

    .message-reply-context-text {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .message-interactions {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        flex-wrap: wrap;
        gap: 4px;
        margin-top: 4px;
    }

    .t-msg-row.staff-message .message-interactions {
        justify-content: flex-end;
    }

    .message-reply-button,
    .message-reaction,
    .message-reaction-trigger {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 24px;
        padding: 3px 8px;
        background: transparent;
        border: 1px solid #e5e7eb;
        border-radius: 999px;
        color: #6b7280;
        cursor: pointer;
        font-size: 11px;
        line-height: 1;
        transition: background 0.15s, border-color 0.15s, color 0.15s;
    }

    .message-reply-button:hover,
    .message-reaction:hover,
    .message-reaction-trigger:hover,
    .message-reaction.is-reacted {
        background: #eef2ff;
        border-color: #a5b4fc;
        color: #4f46e5;
    }

    .message-reaction-picker {
        position: relative;
    }

    .message-reaction-menu {
        position: absolute;
        z-index: 30;
        bottom: calc(100% + 6px);
        left: 0;
        display: flex;
        gap: 2px;
        padding: 4px;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 9px;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.16);
    }

    .t-msg-row.staff-message .message-reaction-menu {
        right: 0;
        left: auto;
    }

    .message-reaction-menu button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        padding: 0;
        background: transparent;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 15px;
    }

    .message-reaction-menu button:hover {
        background: #f0f1ff;
    }

    .reply-composer-preview {
        order: -2;
        display: flex;
        align-items: center;
        flex: 1 0 100%;
        gap: 8px;
        min-width: 0;
        margin: 0 2px;
        padding: 6px 8px;
        background: #f8fafc;
        border-left: 3px solid #6366f1;
        border-radius: 8px;
        color: #4b5563;
        font-size: 11px;
    }

    .reply-composer-preview-content {
        display: flex;
        flex: 1 1 auto;
        flex-direction: column;
        min-width: 0;
        gap: 2px;
    }

    .reply-composer-preview-label {
        color: #4f46e5;
        font-weight: 700;
    }

    .reply-composer-preview-text {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .reply-composer-cancel {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        width: 24px;
        height: 24px;
        padding: 0;
        background: transparent;
        border: none;
        border-radius: 50%;
        color: #6b7280;
        cursor: pointer;
    }

    .reply-composer-cancel:hover {
        background: #e5e7eb;
        color: #111827;
    }

    .revision-card {
        width: min(286px, 100%);
        overflow: hidden;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        color: #111827;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.1);
    }

    .revision-card-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 14px;
    }

    .revision-card-brand {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        flex: 0 0 34px;
        background: #e0e7ff;
        border-radius: 50%;
        color: #4f46e5;
    }

    .revision-card-heading {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }

    .revision-card-heading strong {
        font-size: 13px;
        line-height: 1.25;
    }

    .revision-card-heading span {
        margin-top: 2px;
        color: #6b7280;
        font-size: 10.5px;
    }

    .revision-card-banner {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 112px;
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 52%, #c026d3 100%);
        color: #ffffff;
    }

    .revision-card-banner-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 58px;
        height: 58px;
        background: rgba(255, 255, 255, 0.2);
        border: 2px solid rgba(255, 255, 255, 0.7);
        border-radius: 50%;
        box-shadow: 0 6px 16px rgba(30, 27, 75, 0.2);
    }

    .revision-card-content {
        padding: 13px 14px 14px;
        background: #ffffff;
    }

    .revision-card-content p {
        margin: 0 0 12px;
        color: #374151;
        font-size: 12px;
        line-height: 1.4;
        text-align: center;
    }

    .revision-card-action {
        display: block;
        width: 100%;
        padding: 9px 12px;
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 9px;
        color: #4f46e5;
        cursor: default;
        font-size: 12px;
        font-weight: 700;
        text-align: center;
    }

    .t-bubble a {
        color: inherit;
        text-decoration: underline;
        text-underline-offset: 2px;
    }

    .t-attachment {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 8px;
        color: inherit;
        font-size: 12px;
        font-weight: 600;
        text-decoration: underline;
        text-underline-offset: 2px;
    }

    .t-attachment-image {
        display: block;
        max-width: 220px;
        max-height: 180px;
        margin-top: 8px;
        border-radius: 10px;
        object-fit: cover;
    }

    .attachment-image-bubble {
        padding: 8px;
    }

    .attachment-image-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 6px;
        width: 360px;
        max-width: 100%;
    }

    .attachment-image-link {
        display: block;
        min-width: 0;
    }

    .attachment-image-grid .t-attachment-image {
        width: 100%;
        height: 140px;
        max-width: none;
        max-height: none;
        margin-top: 0;
        border-radius: 8px;
    }

    .attachment-image-grid.single {
        width: 220px;
    }

    .attachment-image-grid.single .t-attachment-image {
        height: 180px;
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
        padding: 14px 18px;
        border-top: 1px solid #e5e7eb;
    }

    .message-composer-shell {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        width: 100%;
        min-height: 58px;
        padding: 5px 6px 5px 8px;
        box-sizing: border-box;
        background: #ffffff;
        border: 2px solid #e5e7eb;
        border-radius: 30px;
    }

    .message-action-drawer {
        display: flex;
        align-items: center;
        gap: 6px;
        flex: 0 0 auto;
        max-width: 160px;
        overflow: hidden;
        padding: 0 10px 0 2px;
        border-right: 1px solid #e5e7eb;
        opacity: 1;
        transform: translateX(0);
        transition:
            max-width 0.28s ease,
            padding 0.28s ease,
            border-color 0.2s ease,
            opacity 0.18s ease,
            transform 0.28s ease;
    }

    .message-drawer-action,
    .thread-footer .message-composer-menu-toggle {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 34px;
        width: 34px;
        height: 34px;
        padding: 0;
        background: transparent;
        color: #6366f1;
        border: none;
        border-radius: 7px;
        cursor: pointer;
        transition: background 0.15s, color 0.15s, transform 0.15s;
    }

    .message-drawer-action:hover {
        background: #f0f1ff;
        color: #4f46e5;
        transform: none;
    }

    .message-drawer-action[data-tooltip]::after {
        position: absolute;
        z-index: 40;
        top: calc(100% + 7px);
        left: 50%;
        width: max-content;
        max-width: 150px;
        padding: 5px 8px;
        background: #111827;
        border-radius: 6px;
        color: #ffffff;
        content: attr(data-tooltip);
        font-size: 11px;
        font-weight: 600;
        line-height: 1.2;
        opacity: 0;
        pointer-events: none;
        transform: translate(-50%, -3px);
        transition: opacity 0.15s ease, transform 0.15s ease;
        white-space: nowrap;
    }

    .message-drawer-action[data-tooltip]:hover::after,
    .message-drawer-action[data-tooltip]:focus-visible::after {
        opacity: 1;
        transform: translate(-50%, 0);
    }

    .thread-footer .message-composer-menu-toggle {
        flex: 0 0 0;
        width: 0;
        visibility: hidden;
        opacity: 0;
        pointer-events: none;
        background: transparent;
        color: #6366f1;
        transition:
            flex-basis 0.28s ease,
            width 0.28s ease,
            opacity 0.18s ease,
            visibility 0.28s ease,
            background 0.15s,
            color 0.15s,
            transform 0.15s;
    }

    .thread-footer .message-composer-menu-toggle:hover {
        background: transparent;
        color: #4f46e5;
    }

    .message-composer-shell.is-composing .message-action-drawer {
        max-width: 0;
        padding-left: 0;
        padding-right: 0;
        border-right-color: transparent;
        opacity: 0;
        transform: translateX(-18px);
        pointer-events: none;
    }

    .message-composer-shell.is-composing .message-composer-menu-toggle {
        flex-basis: 34px;
        width: 34px;
        visibility: visible;
        opacity: 1;
        pointer-events: auto;
    }

    .message-composer-input {
        display: flex;
        align-items: center;
        position: relative;
        flex: 1 1 auto;
        gap: 8px;
        min-width: 0;
    }

    .message-composer-input input {
        flex: 1 1 auto;
        min-width: 0;
        width: 100%;
        padding: 10px 8px;
        background: transparent;
        border: none;
        border-radius: 0;
        color: #111827;
        font-size: 13.5px;
        outline: none;
    }

    .message-composer-input input::placeholder {
        color: #9ca3af;
    }

    .message-composer-input input:focus {
        border: none;
        box-shadow: none;
    }

    .thread-footer .message-composer-send {
        flex: 0 0 auto;
        width: 40px;
        height: 40px;
        background: #6366f1;
        color: #ffffff;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        font-size: 15px;
        transition: background 0.15s, transform 0.15s;
    }

    .thread-footer .message-composer-send:hover {
        background: #4f46e5;
        transform: scale(1.04);
    }

    .thread-footer .message-composer-send:disabled,
    .thread-footer .message-composer-menu-toggle:disabled,
    .message-drawer-action:disabled {
        cursor: not-allowed;
        opacity: 0.6;
        transform: none;
    }

    .message-action-menu {
        position: absolute;
        z-index: 20;
        bottom: calc(100% + 8px);
        left: 0;
        width: 210px;
        padding: 6px;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.16);
    }

    .thread-footer .message-action-menu button {
        display: flex;
        align-items: center;
        gap: 9px;
        width: 100%;
        height: auto;
        padding: 9px 10px;
        background: transparent;
        border: none;
        border-radius: 7px;
        color: #374151;
        font-size: 12px;
        font-weight: 600;
        text-align: left;
    }

    .thread-footer .message-action-menu button:hover {
        background: #f0f1ff;
        color: #4f46e5;
    }

    .attachment-preview-row {
        order: -1;
        display: flex;
        align-items: center;
        flex: 1 0 100%;
        flex-wrap: wrap;
        gap: 6px;
        min-width: 0;
        padding: 2px 4px 7px;
        border-bottom: 1px solid #e5e7eb;
    }

    .attachment-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        min-width: 0;
        max-width: 220px;
        padding: 6px 7px 6px 10px;
        background: #eef2ff;
        border: 1px solid #c7d2fe;
        border-radius: 999px;
        color: #4f46e5;
        font-size: 11px;
        font-weight: 600;
        line-height: 1.2;
    }

    .attachment-pill-name {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .attachment-pill-remove {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        width: 20px;
        height: 20px;
        padding: 0;
        background: transparent;
        border: none;
        border-radius: 50%;
        color: #4f46e5;
        cursor: pointer;
    }

    .attachment-pill-remove:hover {
        background: rgba(79, 70, 229, 0.12);
        color: #3730a3;
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

    .dark .msg-search input {
        background: #1f2937;
        border-color: #4b5563;
        color: #f9fafb;
    }

    .dark .message-composer-shell {
        background: #111827;
        border-color: #4b5563;
    }

    .dark .message-action-drawer {
        border-color: #374151;
    }

    .dark .message-composer-input input {
        color: #f9fafb;
    }

    .dark .msg-search input::placeholder,
    .dark .message-composer-input input::placeholder {
        color: #9ca3af;
    }

    .dark .msg-search input:focus {
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

    .dark .message-reply-context,
    .dark .reply-composer-preview {
        background: #1f2937;
        border-left-color: #818cf8;
        color: #d1d5db;
    }

    .dark .message-reply-context-label,
    .dark .reply-composer-preview-label {
        color: #a5b4fc;
    }

    .dark .message-reply-button,
    .dark .message-reaction,
    .dark .message-reaction-trigger {
        border-color: #4b5563;
        color: #9ca3af;
    }

    .dark .message-reply-button:hover,
    .dark .message-reaction:hover,
    .dark .message-reaction-trigger:hover,
    .dark .message-reaction.is-reacted {
        background: #312e81;
        border-color: #6366f1;
        color: #e0e7ff;
    }

    .dark .message-reaction-menu {
        background: #1f2937;
        border-color: #374151;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.35);
    }

    .dark .message-reaction-menu button:hover {
        background: #312e81;
    }

    .dark .reply-composer-cancel:hover {
        background: #374151;
        color: #f9fafb;
    }

    .dark .t-msg-row.client-message .t-bubble {
        background: #1f2937;
        border-color: #374151;
        color: #f3f4f6;
    }

    .dark .t-msg-row.staff-message .t-bubble {
        background: #6366f1;
        border: none;
        color: #ffffff;
    }

    .dark .revision-card {
        background: #1f2937;
        border-color: #374151;
        color: #f9fafb;
    }

    .dark .revision-card-brand {
        background: #312e81;
        color: #c7d2fe;
    }

    .dark .revision-card-heading span,
    .dark .revision-card-content p {
        color: #d1d5db;
    }

    .dark .revision-card-content {
        background: #1f2937;
    }

    .dark .revision-card-action {
        background: #111827;
        border-color: #4b5563;
        color: #c7d2fe;
    }

    .dark .t-msg-row.own .t-bubble {
        background: #6366f1;
        border: none;

        color: #ffffff;
    }

    .dark .thread-footer {
        background: #111827;
    }

    .dark .thread-footer .message-composer-send {
        background: #6366f1;
    }

    .dark .message-drawer-action:hover,
    .dark .thread-footer .message-composer-send:hover {
        background: #312e81;
        color: #ffffff;
    }

    .dark .message-action-menu {
        background: #1f2937;
        border-color: #374151;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.35);
    }

    .dark .thread-footer .message-action-menu button {
        background: transparent;
        color: #f3f4f6;
    }

    .dark .thread-footer .message-action-menu button:hover {
        background: #312e81;
        color: #ffffff;
    }

    .dark .attachment-preview-row {
        border-bottom-color: #374151;
    }

    .dark .attachment-pill {
        background: #312e81;
        border-color: #4338ca;
        color: #c7d2fe;
    }

    .dark .attachment-pill-remove {
        color: #c7d2fe;
    }

    .dark .attachment-pill-remove:hover {
        background: rgba(199, 210, 254, 0.14);
        color: #ffffff;
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

    @media (max-width: 1024px) {
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

    @media (max-width: 1024px) {
        .msg-wrap {
            height: calc(100vh - 180px);
            height: calc(100dvh - 180px);
            min-height: 0;
        }

        .msg-wrap.has-selection .msg-list,
        .msg-wrap:not(.has-selection) .msg-thread {
            display: none;
        }

        .msg-wrap:not(.has-selection) .msg-list,
        .msg-wrap.has-selection .msg-thread {
            height: 100%;
            min-height: 0;
        }

        .thread-body {
            min-height: 0;
        }
    }
</style>

    <div
    class="msg-wrap {{ $selectedConversation ? 'has-selection' : 'no-selection' }}"
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
                    $documentTitle = $conversation->document?->particulars
                    ?? 'Untitled Document';

                    $latestMessage = $conversation
                        ->messages
                        ->sortBy('created_at')
                        ->last();

                    $latestIsRevisionRequest = $latestMessage && (
                        $latestMessage->body === 'revision_request'
                        || str_contains(
                            (string) $latestMessage->body,
                            'Please upload a revised version of your document using this link:'
                        )
                    );

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

                           <div class="m-avatar-icon">
                               <x-heroicon-o-document-text />
                           </div>

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

                                @if ($latestIsRevisionRequest)
                                    Revision request
                                @else
                                    {{ \Illuminate\Support\Str::limit(
                                        $latestMessage->body,
                                        55
                                    ) }}
                                @endif

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

                $documentTitle = $activeConversation->document?->particulars
                    ?? 'Untitled Document';
            @endphp


            {{-- =====================================================
                THREAD HEADER
            ====================================================== --}}
            <div class="thread-header">

                <div class="t-avatar">
                     <div class="m-avatar-icon">
                               <x-heroicon-o-document-text />
                      </div>
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


                    </div>

                </div>


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

                x-data

                x-on:message-sent.window="
                    $nextTick(() => {
                        $el.scrollTo({
                            top: $el.scrollHeight,
                            behavior: 'smooth'
                        })
                    })
                "

                x-on:conversation-opened.window="
                    $nextTick(() => {
                        $el.scrollTop = $el.scrollHeight
                    })
                "
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

                            @php
                                $isRevisionRequest =
                                    $message->body === 'revision_request'
                                    || str_contains(
                                        (string) $message->body,
                                        'Please upload a revised version of your document using this link:'
                                    );
                            @endphp

                            @if ($message->replyTo)
                                @php
                                    $replySenderName = $message->replyTo->sender?->name ?? 'Message';
                                    $replyPreview = $message->replyTo->body;

                                    if ($replyPreview === 'revision_request') {
                                        $replyPreview = 'Revision request';
                                    } elseif ($replyPreview === 'Attachment sent.') {
                                        $replyPreview = $message->replyTo->attachments->first()?->original_name ?? 'Attachment';
                                    }
                                @endphp

                                <div class="message-reply-context">
                                    <span class="message-reply-context-label">
                                        Replying to {{ $replySenderName }}
                                    </span>
                                    <span class="message-reply-context-text">
                                        {{ \Illuminate\Support\Str::limit((string) $replyPreview, 72) }}
                                    </span>
                                </div>
                            @endif

                            @if ($isRevisionRequest)
                                <div class="t-bubble revision-bubble">
                                    <div class="revision-card">
                                        <div class="revision-card-header">
                                            <div class="revision-card-brand">
                                                <x-heroicon-o-document-text class="h-5 w-5" />
                                            </div>

                                            <div class="revision-card-heading">
                                                <strong>Legal Affairs Office</strong>
                                                <span>Revision request</span>
                                            </div>
                                        </div>

                                        <div class="revision-card-banner">
                                            <div class="revision-card-banner-icon">
                                                <x-heroicon-o-arrow-path class="h-8 w-8" />
                                            </div>
                                        </div>

                                        <div class="revision-card-content">
                                            <p>
                                                Please upload a revised version of your document.
                                            </p>

                                            <div class="revision-card-action">
                                                Revision request sent
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @elseif ($message->body !== 'Attachment sent.')
                                <div class="t-bubble">
                                    {!! nl2br(e($message->body)) !!}
                                </div>
                            @endif

                            @php
                                $imageAttachments = $message->attachments->filter(
                                    fn ($attachment) => \Illuminate\Support\Str::startsWith(
                                        (string) $attachment->mime_type,
                                        'image/'
                                    )
                                );
                                $fileAttachments = $message->attachments->reject(
                                    fn ($attachment) => \Illuminate\Support\Str::startsWith(
                                        (string) $attachment->mime_type,
                                        'image/'
                                    )
                                );
                            @endphp

                            @if ($imageAttachments->isNotEmpty())
                                <div
                                    class="t-bubble attachment-bubble attachment-image-bubble"
                                    wire:key="message-{{ $message->id }}-image-grid"
                                >
                                    <div class="attachment-image-grid {{ $imageAttachments->count() === 1 ? 'single' : '' }}">
                                        @foreach ($imageAttachments as $attachment)
                                            @php
                                                $attachmentUrl = route('messages.attachment', [
                                                    'message' => $message->id,
                                                    'attachment' => $attachment->id,
                                                ]);
                                            @endphp

                                            <a
                                                href="{{ $attachmentUrl }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="attachment-image-link"
                                            >
                                                <img
                                                    src="{{ $attachmentUrl }}"
                                                    alt="{{ $attachment->original_name ?: 'Attached image' }}"
                                                    class="t-attachment-image"
                                                >
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @foreach ($fileAttachments as $attachment)
                                @php
                                    $attachmentUrl = route('messages.attachment', [
                                        'message' => $message->id,
                                        'attachment' => $attachment->id,
                                    ]);
                                @endphp

                                <div
                                    class="t-bubble attachment-bubble"
                                    wire:key="message-{{ $message->id }}-attachment-{{ $attachment->id }}"
                                >
                                    <a
                                        href="{{ $attachmentUrl }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="t-attachment"
                                    >
                                        <x-heroicon-o-paper-clip class="h-4 w-4" />
                                        {{ $attachment->original_name ?: 'Attached document' }}
                                    </a>
                                </div>
                            @endforeach

                            <div class="message-interactions">
                                <button
                                    type="button"
                                    class="message-reply-button"
                                    wire:click="startReply({{ $message->id }})"
                                    title="Reply to message"
                                >
                                    <x-heroicon-o-arrow-uturn-left class="mr-1 h-3.5 w-3.5" />
                                    Reply
                                </button>

                                @foreach ($message->reactions->groupBy('reaction') as $reaction => $reactionUsers)
                                    <button
                                        type="button"
                                        class="message-reaction {{ $reactionUsers->contains('user_id', auth()->id()) ? 'is-reacted' : '' }}"
                                        wire:click="reactToMessage({{ $message->id }}, '{{ $reaction }}')"
                                        title="Toggle {{ $reaction }} reaction"
                                    >
                                        {{ $reaction }} {{ $reactionUsers->count() }}
                                    </button>
                                @endforeach

                                <div
                                    class="message-reaction-picker"
                                    x-data="{ open: false }"
                                    @click.outside="open = false"
                                >
                                    <button
                                        type="button"
                                        class="message-reaction-trigger"
                                        @click="open = ! open"
                                        :aria-expanded="open.toString()"
                                        aria-label="Add reaction"
                                        title="Add reaction"
                                    >
                                        ☺
                                    </button>

                                    <div
                                        x-cloak
                                        x-show="open"
                                        class="message-reaction-menu"
                                    >
                                        @foreach (['👍', '❤️', '😂', '😮', '😢', '🙏'] as $reaction)
                                            <button
                                                type="button"
                                                wire:click="reactToMessage({{ $message->id }}, '{{ $reaction }}')"
                                                @click="open = false"
                                                aria-label="React {{ $reaction }}"
                                            >
                                                {{ $reaction }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
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

                    <div
                        class="message-composer-shell"
                        x-data="{ open: false, hasText: false }"
                        :class="{ 'is-composing': hasText }"
                        @click.outside="open = false"
                        x-on:reply-started.window="$nextTick(() => $refs.messageInput?.focus())"
                    >

                        <div class="message-action-drawer" aria-label="Quick message actions">
                            <button
                                type="button"
                                class="message-drawer-action"
                                @click="$wire.set('attachmentKind', 'image'); $refs.imageFile.click()"
                                aria-label="Attach image"
                                title="Attach image"
                                data-tooltip="Attach image"
                            >
                                <x-heroicon-o-photo class="h-5 w-5" />
                            </button>

                            <button
                                type="button"
                                class="message-drawer-action"
                                @click="$wire.set('attachmentKind', 'document'); $refs.documentFile.click()"
                                aria-label="Attach PDF or DOCX"
                                title="Attach PDF or DOCX"
                                data-tooltip="Attach PDF or DOCX"
                            >
                                <x-heroicon-o-document-text class="h-5 w-5" />
                            </button>

                            @if ($activeConversation?->document)
                                <button
                                    type="button"
                                    class="message-drawer-action"
                                    wire:click="requestRevision"
                                    wire:loading.attr="disabled"
                                    wire:target="requestRevision"
                                    aria-label="Ask for revision"
                                    title="Ask for revision"
                                    data-tooltip="Ask for revision"
                                >
                                    <x-heroicon-o-arrow-path class="h-5 w-5" />
                                </button>
                            @endif
                        </div>

                        @if ($replyingToMessageId)
                            @php
                                $replyingToMessage = collect($messages)->firstWhere('id', $replyingToMessageId);
                            @endphp

                            @if ($replyingToMessage)
                                @php
                                    $replyingToPreview = $replyingToMessage->body;

                                    if ($replyingToPreview === 'revision_request') {
                                        $replyingToPreview = 'Revision request';
                                    } elseif ($replyingToPreview === 'Attachment sent.') {
                                        $replyingToPreview = $replyingToMessage->attachments->first()?->original_name ?? 'Attachment';
                                    }
                                @endphp

                                <div class="reply-composer-preview">
                                    <div class="reply-composer-preview-content">
                                        <span class="reply-composer-preview-label">Replying to message</span>
                                        <span class="reply-composer-preview-text">
                                            {{ \Illuminate\Support\Str::limit((string) $replyingToPreview, 72) }}
                                        </span>
                                    </div>

                                    <button
                                        type="button"
                                        class="reply-composer-cancel"
                                        wire:click="cancelReply"
                                        aria-label="Cancel reply"
                                        title="Cancel reply"
                                    >
                                        <x-heroicon-o-x-mark class="h-4 w-4" />
                                    </button>
                                </div>
                            @endif
                        @endif

                        @if (count($attachments) > 0)
                            <div class="attachment-preview-row" aria-label="Selected attachments">
                                @foreach ($attachments as $index => $attachment)
                                    <div
                                        class="attachment-pill"
                                        wire:key="pending-attachment-{{ $index }}"
                                        title="{{ $attachment->getClientOriginalName() }}"
                                    >
                                        <span class="attachment-pill-name">
                                            {{ \Illuminate\Support\Str::limit($attachment->getClientOriginalName(), 24) }}
                                        </span>

                                        <button
                                            type="button"
                                            class="attachment-pill-remove"
                                            wire:click="removeAttachment({{ $index }})"
                                            wire:loading.attr="disabled"
                                            wire:target="removeAttachment({{ $index }})"
                                            aria-label="Remove {{ $attachment->getClientOriginalName() }}"
                                            title="Remove file"
                                        >
                                            <x-heroicon-o-x-mark class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="message-composer-input">
                            <button
                                type="button"
                                class="message-composer-menu-toggle"
                                @click="open = ! open"
                                :aria-expanded="open.toString()"
                                aria-label="Show message actions"
                                title="Show message actions"
                            >
                                <x-heroicon-o-squares-2x2 class="h-5 w-5" />
                            </button>

                            <div
                                x-cloak
                                x-show="open"
                                class="message-action-menu"
                            >
                                <button
                                    type="button"
                                    @click="$wire.set('attachmentKind', 'image'); $refs.imageFile.click(); open = false"
                                >
                                    <x-heroicon-o-photo class="h-4 w-4" />
                                    Attach image
                                </button>

                                <button
                                    type="button"
                                    @click="$wire.set('attachmentKind', 'document'); $refs.documentFile.click(); open = false"
                                >
                                    <x-heroicon-o-document-text class="h-4 w-4" />
                                    Attach PDF or DOCX
                                </button>

                                @if ($activeConversation?->document)
                                    <button
                                        type="button"
                                        wire:click="requestRevision"
                                        wire:loading.attr="disabled"
                                        wire:target="requestRevision"
                                        @click="open = false"
                                    >
                                        <x-heroicon-o-arrow-path class="h-4 w-4" />
                                        Ask for revision
                                    </button>
                                @endif
                            </div>

                            <input
                                type="text"
                                x-ref="messageInput"
                                wire:model="newMessage"
                                wire:keydown.enter="sendMessage"
                                @input="hasText = $event.target.value.length > 0"
                                @keydown.enter="hasText = false"
                                placeholder="Type a message..."
                                maxlength="5000"
                                autocomplete="off"
                            >

                            <button
                                type="button"
                                class="message-composer-send"
                                wire:click="sendMessage"
                                wire:loading.attr="disabled"
                                wire:target="sendMessage"
                                @click="hasText = false"
                                title="Send message"
                            >
                                <span wire:loading.remove wire:target="sendMessage">➤</span>
                                <span wire:loading wire:target="sendMessage">...</span>
                            </button>
                        </div>

                        <input
                            x-ref="imageFile"
                            type="file"
                            multiple
                            accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                            wire:model="attachments"
                            wire:change="$set('attachmentKind', 'image')"
                            class="hidden"
                        >

                        <input
                            x-ref="documentFile"
                            type="file"
                            multiple
                            accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                            wire:model="attachments"
                            wire:change="$set('attachmentKind', 'document')"
                            class="hidden"
                        >

                    </div>

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

                @error('attachments')

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

                @error('attachments.*')

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
        Livewire.on('messages-read', (event) => {
            const count = Number(event.count ?? 0);

            const messagesLink = document.querySelector(
                'a[href$="/admin/messages"]'
            );

            if (! messagesLink) {
                return;
            }

            const badge = messagesLink.querySelector('.fi-badge');

            if (count <= 0) {
                if (badge) {
                    badge.remove();
                }

                return;
            }

            if (badge) {
                badge.textContent = count;
            }
        });
    });
</script>

</x-filament-panels::page>
