@php
    $isWindowsClient = preg_match('/Windows/i', (string) request()->userAgent()) === 1;
@endphp

@if ($isWindowsClient)
    <style id="windows-admin-scale">
        /* Keep the admin sidebar and top bar at their normal size. */
        body {
            zoom: 1;
        }

        .fi-main {
            zoom: 0.75;
            width: 100%;
            max-width: 100%;
        }

        .fi-main:has(.msg-wrap) {
            zoom: 1;
        }

        .fi-main:has(.calendar-layout) {
            zoom: 1;
        }

        /* Keep table action dropdowns anchored correctly, matching the client
         * documents page. Fixed dropdown coordinates are incorrect inside the
         * zoomed admin content container. */
        .fi-main:has(.admin-documents-page) {
            zoom: 1;
        }

        body:has(.document-viewer-page) .fi-main {
            zoom: 1;
        }

            body:has(.document-viewer-page) .document-viewer-page {
                zoom: 1;
                width: 100%;
                max-width: 100%;
            }

            body:has(.msg-wrap) .revision-card-banner {
                min-height: 88px;
            }

            body:has(.msg-wrap) .revision-card-content {
                padding: 10px 12px 12px;
            }

            .fi-main:has(.msg-wrap) .msg-item {
                box-sizing: border-box;
                gap: 8px;
                height: auto !important;
                min-height: 0 !important;
                max-height: none !important;
                padding: 9px 12px !important;
            }

            .fi-main:has(.msg-wrap) .msg-wrap {
                zoom: 0.85;
                box-sizing: border-box;
                width: 100% !important;
                height: calc((100dvh - 128px) / 0.85) !important;
                max-height: none !important;
            }

            .fi-main:has(.msg-wrap) .m-avatar,
            .fi-main:has(.msg-wrap) .m-avatar-icon {
                width: 36px !important;
                height: 36px !important;
                min-width: 36px !important;
            }

            .fi-main:has(.msg-wrap) .m-avatar-icon svg {
                width: 21px;
                height: 21px;
            }

            @media (min-width: 64rem) {
            .fi-main:has(.calendar-layout) .calendar-layout {
                zoom: 0.9;
                width: 100%;
                height: calc((100dvh - 8rem) / 0.9);
                min-height: 0;
                max-height: none;
            }

            .fi-main:has(.calendar-layout) .calendar-panel,
            .fi-main:has(.calendar-layout) .calendar-layout > div:nth-child(2) {
                height: 100% !important;
            }

            body:has(.document-viewer-page) .document-viewer-layout {
                transform: scale(0.75);
                transform-origin: top left;
                width: 110% !important;
                height: 133.333333% !important;
                max-width: none !important;
            }

            body:has(.document-viewer-page):not(:has(#fi-main-sidebar.fi-sidebar-open)) .document-viewer-layout {
                width: 127% !important;
            }
        }
    </style>
@endif
