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

            @media (min-width: 64rem) {
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
