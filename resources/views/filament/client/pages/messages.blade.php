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
        grid-template-columns: 320px minmax(0, 1fr);
        gap: 16px;
        height: calc(100vh - 128px);
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

    .thread-header,
    .thread-footer {
        flex-shrink: 0;
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

    .thread-body-shell {
        position: relative;

        display: flex;
        flex: 1;
        min-height: 0;
    }

    .thread-body {
        position: relative;

        display: flex;
        flex: 1;
        flex-direction: column;
        justify-content: flex-start;
        gap: 12px;

        overflow-y: auto;

        padding: 18px;

        background: #f9fafb;
    }

    .new-messages-jump {
        position: absolute;
        z-index: 5;
        bottom: 14px;
        left: 50%;

        display: inline-flex;
        align-items: center;
        gap: 6px;

        min-height: 34px;
        padding: 7px 12px;

        transform: translateX(-50%);

        background: #6366f1;
        border: 1px solid #4f46e5;
        border-radius: 999px;
        box-shadow: 0 8px 18px rgba(79, 70, 229, 0.28);

        color: #ffffff;
        cursor: pointer;

        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }

    .new-messages-jump:hover {
        background: #4f46e5;
    }

    .new-messages-jump:focus-visible {
        outline: 3px solid rgba(129, 140, 248, 0.5);
        outline-offset: 2px;
    }

    /* Single message image: show the whole photo */
    .attachment-image-grid.single {
        display: flex;
        width: fit-content;
        max-width: min(420px, 100%);
    }

    .attachment-image-grid.single .attachment-image-link {
        display: block;
        width: auto;
        max-width: 100%;
        height: auto;
        padding: 0;
        border: 0;
        background: transparent;
        cursor: pointer;
    }

    .attachment-image-grid.single .t-attachment-image {
        display: block;
        width: auto;
        max-width: 100%;
        height: auto;
        max-height: 420px;

        /* Important: don't crop a single image */
        object-fit: contain;

        border-radius: 14px;
    }

    [x-cloak] {
        display: none !important;
    }

    .message-image-preview {
        position: fixed;
        inset: 0;
        z-index: 99999;

        display: flex;
        align-items: center;
        justify-content: center;

        padding: 32px;

        background: rgba(15, 23, 42, 0.88);
        backdrop-filter: blur(4px);
    }

    .message-image-preview-content {
        display: flex;
        align-items: center;
        justify-content: center;

        width: 100%;
        height: 100%;
    }

    .message-image-preview-img {
        display: block;

        max-width: 92vw;
        max-height: 90vh;

        width: auto;
        height: auto;

        object-fit: contain;

        border-radius: 12px;
    }

    .message-image-preview-close {
        position: fixed;
        top: 20px;
        right: 24px;

        z-index: 100000;

        display: flex;
        align-items: center;
        justify-content: center;

        width: 44px;
        height: 44px;

        border: 0;
        border-radius: 9999px;

        background: rgba(255, 255, 255, 0.15);
        color: white;

        font-size: 32px;
        line-height: 1;

        cursor: pointer;
    }

    .message-image-preview-close:hover {
        background: rgba(255, 255, 255, 0.25);
    }

    /* =========================================================
       MESSAGE ROW
    ========================================================= */

    .t-msg-row {
        display: flex;
        align-items: flex-end;
        gap: 8px;

        position: relative;

        width: fit-content;
        max-width: 70%;

        margin: 0;
    }

    .t-msg-row:hover,
    .t-msg-row:focus-within {
        z-index: 30;
    }

    .t-msg-row.message-continuation {
        margin-top: -8px;
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

    .t-msg-avatar-placeholder {
        visibility: hidden;
    }


    /* =========================================================
       MESSAGE CONTENT
    ========================================================= */

    .t-message-content {
        display: flex;
        flex-direction: column;

        position: relative;

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

    .t-bubble.revision-bubble {
        display: block;
        width: 286px;
        max-width: 100%;
        margin: 0 !important;
        padding: 0;
        background: transparent;
        border: none;
        flex: 0 0 auto;
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
        border-left: none;
        border: 0;
        border-radius: 8px;
        color: #4b5563;
        cursor: pointer;
        font: inherit;
        font-size: 11px;
        text-align: left;
        transition: background 0.15s ease, box-shadow 0.15s ease;
    }

    .message-reply-context:hover,
    .message-reply-context:focus-visible {
        background: #e8eaff;
        outline: none;
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

    .t-msg-row.message-targeted .t-bubble,
    .t-msg-row.message-targeted .message-reply-context {
        animation: message-target-pulse 1.8s ease;
    }

    @keyframes message-target-pulse {
        0%, 100% {
            box-shadow: none;
        }
        25%, 65% {
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.28);
        }
    }

    .message-interactions {
        position: absolute;
        top: 50%;
        z-index: 20;

        display: flex;
        align-items: center;
        justify-content: center;
        flex-wrap: nowrap;
        gap: 4px;
        width: max-content;
        margin: 0;
        padding: 4px;
        transform: translateY(-50%);

        opacity: 0;
        pointer-events: none;
        transition: opacity 0.15s ease;

        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 999px;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.14);
    }

    .t-msg-row:hover .message-interactions,
    .t-msg-row:focus-within .message-interactions {
        opacity: 1;
        pointer-events: auto;
    }

    .message-interactions::before {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 8px;
        content: '';
    }

    .t-msg-row:not(.own) .message-interactions {
        left: calc(100% + 8px);
    }

    .t-msg-row:not(.own) .message-interactions::before {
        right: 100%;
    }

    .t-msg-row.own .message-interactions {
        right: calc(100% + 8px);
    }

    .t-msg-row.own .message-interactions::before {
        left: 100%;
    }

    .message-reply-button,
    .message-reaction-trigger {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 30px;
        height: 30px;
        min-height: 30px;
        padding: 0;
        background: transparent;
        border: 1px solid #e5e7eb;
        border-radius: 999px;
        color: #6b7280;
        cursor: pointer;
        font-size: 11px;
        line-height: 1;
        transition: background 0.15s, border-color 0.15s, color 0.15s;
    }

    .message-reaction-trigger {
        font-size: 15px;
        line-height: 1;
        text-align: center;
    }

    .message-reaction-trigger-icon {
        display: block;
        width: 20px;
        height: 20px;
    }

    .message-reaction {
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
        white-space: nowrap;
        transition: background 0.15s, border-color 0.15s, color 0.15s;
    }

    .message-reactions {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        width: fit-content;
        max-width: 100%;
        margin-top: 0;
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

    .t-msg-row.own .message-reaction-menu {
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
        font-size: 18px;
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
        border-left: none;
        border-radius: 8px;
        color: #4b5563;
        font-size: 11px;
        cursor: pointer;
        transition: background 0.15s ease;
    }

    .reply-composer-preview:hover,
    .reply-composer-preview:focus-visible {
        background: #eef2ff;
        outline: none;
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
        width: 100%;
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
        cursor: pointer;
        font-size: 12px;
        font-weight: 700;
        text-align: center;
        transition: background 0.15s, border-color 0.15s, transform 0.15s;
    }

    .revision-card-action:hover {
        background: #eef2ff;
        border-color: #a5b4fc;
        transform: translateY(-1px);
    }

    .revision-card-action:disabled {
        cursor: wait;
        opacity: 0.65;
        transform: none;
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
        padding: 0 !important;
        background: transparent !important;
        border: none !important;
        border-radius: 0;
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
        padding: 0;
        background: transparent;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        overflow: hidden;
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

    .dark .msg-search input {
        background: #1f2937;
        border-color: #4b5563;

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

    .dark .new-messages-jump {
        background: #818cf8;
        border-color: #a5b4fc;
        color: #111827;
    }

    .dark .new-messages-jump:hover {
        background: #a5b4fc;
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

    .dark .message-interactions {
        background: #1f2937;
        border-color: #374151;
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.35);
    }

    .dark .message-reply-context,
    .dark .reply-composer-preview {
        background: #1f2937;
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

    .dark .t-msg-row.own .t-bubble {
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

    .dark .revision-card-action:hover {
        background: #312e81;
        border-color: #6366f1;
        color: #ffffff;
    }

    .dark .thread-footer {
        background: #111827;
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

    .dark .message-drawer-action:hover,
    .dark .message-drawer-action:focus-visible {
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

    .dark .message-composer-send {
        background: #6366f1 !important;
    }

    .dark .message-composer-send:hover {
        background: #4f46e5 !important;
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


    /* =========================================================
       RESPONSIVE
    ========================================================= */

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
            height: calc(100vh - 128px);
            height: calc(100dvh - 128px);
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
    .msg-list, .msg-thread { min-width: 0; }

    @media (max-width: 640px) {
        .thread-header, .thread-footer { padding: 12px; }
        .thread-header { gap: 8px; }
        .t-msg-row { max-width: 100%; }
        .t-bubble { overflow-wrap: anywhere; }
    }

    /* Allow the page to scroll when a landscape viewport cannot fit the composer. */
    @media (max-height: 600px) {
        html:has(.msg-wrap), body:has(.msg-wrap) { overflow-y: auto; }
        .msg-wrap { min-height: 360px; max-height: none; }
    }
</style>

    <div
        class="msg-wrap {{ $selectedConversation ? 'has-selection' : 'no-selection' }}"
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
                        wire:model.live.debounce.300ms="search"
                    >

                </div>

            </div>


            <div class="msg-items">

                @forelse ($conversations as $conversation)

                    @php
                        $latestMessage = $conversation->messages->last();

                        $latestIsRevisionRequest = $latestMessage && (
                            $latestMessage->body === 'revision_request'
                            || str_contains(
                                (string) $latestMessage->body,
                                'Please upload a revised version of your document using this link:'
                            )
                        );

                        $displayName = $conversation->document?->particulars
                            ?: $conversation->documentRequest?->purpose_details
                            ?: $conversation->documentRequest?->purpose
                            ?: 'General Conversation';

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

                        </div>

                        <div class="m-info">

                            <div class="m-sub">

                                @if ($conversation->document)
                                    {{ $conversation->document->lao_number ?? 'Not assigned' }}
                                @elseif ($conversation->documentRequest)
                                    Request #{{ $conversation->documentRequest->request_id }}
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

                                    @if ($latestIsRevisionRequest)
                                        Revision request
                                    @else
                                        {{ \Illuminate\Support\Str::limit(
                                            $latestMessage->body,
                                            60
                                        ) }}
                                    @endif

                                @else
                                    No messages yet
                                @endif

                            </div>

                        </div>

                    </div>

                @empty

                    <div class="p-6 text-center text-gray-500">
                        {{ filled(trim($search)) ? 'No conversations match your search.' : 'No conversations yet.' }}
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
                $activeConversation = $activeConversationRecord
                    ?? $conversations->firstWhere('id', $selectedConversation);

                $threadName = $activeConversation?->document?->particulars
                    ?: $activeConversation?->documentRequest?->purpose_details
                    ?: $activeConversation?->documentRequest?->purpose
                    ?: 'Untitled Document';
                
            @endphp


            {{-- THREAD HEADER --}}
                <div class="thread-header">

                    @if ($activeConversation?->document?->public_id)
                        <a
                            href="{{ \App\Filament\Client\Pages\Documents::getUrl([
                                'tab' => 'all',
                                'document' => $activeConversation->document->public_id,
                            ]) }}"
                            class="t-avatar transition-opacity hover:opacity-80"
                            aria-label="View document in Documents"
                            title="View document in Documents"
                        >
                            <div class="m-avatar-icon">
                                <x-heroicon-o-document-text />
                            </div>
                        </a>
                    @else
                        <div class="t-avatar">
                            <div class="m-avatar-icon">
                                <x-heroicon-o-document-text />
                            </div>
                        </div>
                    @endif

                    <div style="flex: 1;">

                        <div class="t-name">
                           {{ $threadName }}
                        </div>

                        <div class="t-sub">

                            @if ($activeConversation?->document)
                                {{ $activeConversation->document->lao_number ?? 'Not assigned' }}
                            @elseif ($activeConversation?->documentRequest)
                                Request #{{ $activeConversation->documentRequest->request_id }}
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
                class="thread-body-shell"

                x-data="{
                    imagePreview: null,
                    imagePreviewName: '',
                    isNearBottom: true,
                    showNewMessages: false,
                    newMessageCount: 0,
                    readRequestPending: false,
                    scrollThreshold: 96,
                    openImage(url, name) {
                        this.imagePreview = url
                        this.imagePreviewName = name
                    },
                    closeImage() {
                        this.imagePreview = null
                        this.imagePreviewName = ''
                    },
                    scrollToMessage(messageId) {
                        const target = Array.from(
                            this.$refs.threadBody?.querySelectorAll('[data-message-id]') ?? []
                        ).find((element) => element.dataset.messageId === String(messageId))

                        if (! target) {
                            return
                        }

                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center',
                        })
                        target.classList.remove('message-targeted')
                        void target.offsetWidth
                        target.classList.add('message-targeted')

                        window.setTimeout(() => {
                            target.classList.remove('message-targeted')
                        }, 1800)
                    },
                    init() {
                        this.$nextTick(() => this.scrollToBottom(false, false))
                    },
                    updateScrollState() {
                        const threadBody = this.$refs.threadBody ?? this.$el
                        const distanceFromBottom = threadBody.scrollHeight
                            - threadBody.scrollTop
                            - threadBody.clientHeight
                        const wasNearBottom = this.isNearBottom

                        this.isNearBottom = distanceFromBottom <= this.scrollThreshold

                        if (this.isNearBottom) {
                            this.showNewMessages = false
                            this.newMessageCount = 0

                            if (! wasNearBottom) {
                                this.markActiveMessagesRead()
                            }
                        }
                    },
                    markActiveMessagesRead() {
                        if (this.readRequestPending || ! this.$wire) {
                            return null
                        }

                        this.readRequestPending = true
                        const request = this.$wire.markMessagesAsRead()

                        if (request && typeof request.finally === 'function') {
                            request.finally(() => {
                                this.readRequestPending = false
                            })
                        } else {
                            this.readRequestPending = false
                        }

                        return request
                    },
                    scrollToBottom(smooth = true, markRead = false) {
                        this.showNewMessages = false
                        this.newMessageCount = 0

                        this.$nextTick(() => {
                            const scroll = () => {
                                const threadBody = this.$refs.threadBody
                                    ?? this.$root
                                    ?? this.$el

                                threadBody.scrollTop = threadBody.scrollHeight
                                threadBody.scrollTo({
                                    top: threadBody.scrollHeight,
                                    behavior: smooth ? 'smooth' : 'auto'
                                })
                            }

                            scroll()

                            this.isNearBottom = true

                            if (markRead) {
                                const request = this.markActiveMessagesRead()

                                if (request && typeof request.finally === 'function') {
                                    request.finally(() => {
                                        this.$nextTick(() => scroll())
                                    })
                                }
                            }
                        })
                    },
                    handleNewMessages(event) {
                        const detail = event.detail ?? event
                        const incomingCount = Number(detail.incomingCount ?? 0)
                        const outgoingCount = Number(detail.outgoingCount ?? 0)

                        if (incomingCount <= 0 && outgoingCount <= 0) {
                            return
                        }

                        if (outgoingCount > 0 || this.isNearBottom) {
                            this.scrollToBottom(true, incomingCount > 0)
                            return
                        }

                        this.newMessageCount += incomingCount
                        this.showNewMessages = this.newMessageCount > 0
                    },
                    handleMessageSent() {
                        this.scrollToBottom(true, false)
                    }
                }"
                x-on:keydown.escape.window="closeImage()"
                x-on:new-messages-available.window="handleNewMessages($event)"
                x-on:message-sent.window="handleMessageSent()"
                x-on:jump-to-message.window="scrollToMessage($event.detail.messageId)"
                x-on:conversation-opened.window="
                    scrollToBottom(false, false)
                "
            >

                <div
                    class="thread-body"
                    id="threadBody"
                    x-ref="threadBody"
                    x-on:scroll.passive="updateScrollState()"
                >

                @forelse ($messages as $message)

                    @php
                        $isOwn =
                            (int) $message->sender_id
                            === (int) auth()->id();

                        $previousMessage = $messages->get($loop->index - 1);
                        $nextMessage = $messages->get($loop->index + 1);

                        $previousIsOwn = $previousMessage
                            && (int) $previousMessage->sender_id === (int) auth()->id();

                        $nextIsOwn = $nextMessage
                            && (int) $nextMessage->sender_id === (int) auth()->id();

                        $isSameSenderAsPrevious = $previousMessage
                            && $previousIsOwn === $isOwn;

                        $isSameSenderAsNext = $nextMessage
                            && $nextIsOwn === $isOwn;

                        $showSenderName = ! $isSameSenderAsPrevious;
                        $showSenderProfile = ! $isSameSenderAsNext;

                        /*
                        * Client side:
                        * - own messages = current client identity
                        * - all staff messages = Legal Affairs Office
                        */
                        $displayName = $isOwn
                            ? (auth()->user()->name ?? 'You')
                            : 'Legal Affairs Office';

                        $displayPhoto = $isOwn
                            ? auth()->user()?->getProfilePhotoUrl()
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
                        class="t-msg-row {{ $isOwn ? 'own' : '' }} {{ $isSameSenderAsPrevious ? 'message-continuation' : '' }}"
                        data-message-id="{{ $message->id }}"
                        wire:key="message-{{ $message->id }}"
                    >

                        <div
                            class="t-msg-avatar {{ $showSenderProfile ? '' : 't-msg-avatar-placeholder' }}"
                            @if (! $showSenderProfile) aria-hidden="true" @endif
                        >

                            @if ($showSenderProfile && $displayPhoto)

                                <img
                                    src="{{ $displayPhoto }}"
                                    alt="{{ $displayName }}"
                                    class="t-msg-avatar-img"
                                    referrerpolicy="no-referrer"
                                >

                            @elseif ($showSenderProfile)

                                {{ $displayInitials ?: '?' }}

                            @endif

                        </div>

                        <div class="t-message-content">

                            @if (! $isOwn && $showSenderName)

                                <div class="t-sender-name">
                                    Legal Affairs Office
                                </div>

                            @endif

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
                                    $replySenderName = (int) $message->replyTo->sender_id === (int) auth()->id()
                                        ? (auth()->user()->name ?? 'You')
                                        : 'Legal Affairs Office';
                                    $replyPreview = $message->replyTo->body;

                                    if ($replyPreview === 'revision_request') {
                                        $replyPreview = 'Revision request';
                                    } elseif ($replyPreview === 'Attachment sent.') {
                                        $replyPreview = $message->replyTo->attachments->first()?->original_name ?? 'Attachment';
                                    }
                                @endphp

                                <button
                                    type="button"
                                    class="message-reply-context"
                                    @click="scrollToMessage({{ $message->replyTo->id }})"
                                    aria-label="Go to replied message"
                                    title="Go to replied message"
                                >
                                    <span class="message-reply-context-label">
                                        {{ $replySenderName }}
                                    </span>
                                    <span class="message-reply-context-text">
                                        {{ \Illuminate\Support\Str::limit((string) $replyPreview, 72) }}
                                    </span>
                                </button>
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

                                            <button
                                                type="button"
                                                class="revision-card-action"
                                                wire:click="openRevisionRequest({{ $message->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="openRevisionRequest"
                                            >
                                                <span wire:loading.remove wire:target="openRevisionRequest">
                                                    View Revision Request
                                                </span>

                                                <span wire:loading wire:target="openRevisionRequest">
                                                    Opening...
                                                </span>
                                            </button>
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

                                            <button
                                                type="button"
                                                class="attachment-image-link"
                                                @click="openImage(
                                                    @js($attachmentUrl),
                                                    @js($attachment->original_name ?: 'Attached image')
                                                )"
                                            >
                                                <img
                                                    src="{{ $attachmentUrl }}"
                                                    alt="{{ $attachment->original_name ?: 'Attached image' }}"
                                                    class="t-attachment-image"
                                                >
                                            </button>
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

                            @if ($message->reactions->isNotEmpty())
                                <div class="message-reactions" aria-label="Message reactions">
                                    @foreach ($message->reactions->groupBy('reaction') as $reaction => $reactionUsers)
                                        <button
                                            type="button"
                                            class="message-reaction {{ $reactionUsers->contains('user_id', auth()->id()) ? 'is-reacted' : '' }}"
                                            wire:click="reactToMessage({{ $message->id }}, '{{ $reaction }}')"
                                            aria-label="Toggle {{ $reaction }} reaction"
                                            title="Toggle {{ $reaction }} reaction"
                                        >
                                            {{ $reaction }} {{ $reactionUsers->count() }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif

                            <div class="message-interactions">
                                <button
                                    type="button"
                                    class="message-reply-button"
                                    wire:click="startReply({{ $message->id }})"
                                    aria-label="Reply to message"
                                    title="Reply to message"
                                >
                                    <x-heroicon-o-arrow-uturn-left class="h-4 w-4" />
                                </button>

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
                                        <x-heroicon-o-face-smile class="message-reaction-trigger-icon" aria-hidden="true" />
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

                <template x-teleport="body">
                    <div
                        x-cloak
                        x-show="imagePreview"
                        x-transition.opacity
                        class="message-image-preview"
                        @click.self="closeImage()"
                    >
                        <button
                            type="button"
                            class="message-image-preview-close"
                            @click="closeImage()"
                            aria-label="Close image preview"
                        >
                            &times;
                        </button>

                        <div class="message-image-preview-content">
                            <img
                                :src="imagePreview"
                                :alt="imagePreviewName"
                                class="message-image-preview-img"
                            >
                        </div>
                    </div>
                </template>

            </div>

            <button
                type="button"
                class="new-messages-jump"
                x-cloak
                x-show="showNewMessages"
                @click="scrollToBottom(true, true)"
                :aria-label="newMessageCount === 1 ? 'Jump to 1 new message' : 'Jump to ' + newMessageCount + ' new messages'"
            >
                <x-heroicon-o-arrow-down class="h-4 w-4" />
                <span x-text="newMessageCount === 1 ? '1 new message' : newMessageCount + ' new messages'"></span>
            </button>

            </div>


            {{-- MESSAGE INPUT --}}
                @if ($activeConversation && $activeConversation->status === 'active')

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

                                @php
                                    $replyingToName = (int) $replyingToMessage->sender_id === (int) auth()->id()
                                        ? (auth()->user()->name ?? 'You')
                                        : 'Legal Affairs Office';
                                @endphp

                                <div
                                    role="button"
                                    tabindex="0"
                                    class="reply-composer-preview"
                                    @click="window.dispatchEvent(new CustomEvent('jump-to-message', { detail: { messageId: {{ $replyingToMessage->id }} } }))"
                                    @keydown.enter.prevent="window.dispatchEvent(new CustomEvent('jump-to-message', { detail: { messageId: {{ $replyingToMessage->id }} } }))"
                                    @keydown.space.prevent="window.dispatchEvent(new CustomEvent('jump-to-message', { detail: { messageId: {{ $replyingToMessage->id }} } }))"
                                    aria-label="Go to selected message"
                                    title="Go to selected message"
                                >
                                    <div class="reply-composer-preview-content">
                                        <span class="reply-composer-preview-label">{{ $replyingToName }}</span>
                                        <span class="reply-composer-preview-text">
                                            {{ \Illuminate\Support\Str::limit((string) $replyingToPreview, 72) }}
                                        </span>
                                    </div>

                                    <button
                                        type="button"
                                        class="reply-composer-cancel"
                                        wire:click="cancelReply"
                                        @click.stop
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
                            padding: 4px 16px 10px;
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
        const updateMessagesBadge = (event) => {
            const count = Number(event.count ?? 0);

            const messagesLink = document.querySelector(
                'a[href$="/client/messages"]'
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
        };

        Livewire.on('messages-read', updateMessagesBadge);
        Livewire.on('messages-unread', updateMessagesBadge);
    });
</script>

</x-filament-panels::page>
