@php
    $isWindowsClient = preg_match('/Windows/i', (string) request()->userAgent()) === 1;
@endphp

@if ($isWindowsClient)
    <style id="windows-client-scale">
        /* Keep client pages at the same browser-default scale as the public pages. */
        body {
            zoom: 1;
        }

        body:has(.client-document-view-page) .client-document-view-page {
            zoom: 0.9;
            width: 100%;
            max-width: 100%;
        }

        body:has(.client-request-page) .client-request-page {
            zoom: 0.85;
            width: 100%;
            max-width: 100%;
        }

        body:has(.client-upload-page) .client-upload-page {
            zoom: 0.8;
            width: 100%;
            max-width: 100%;
        }

        .fi-main:has(.client-dashboard-documents) {
            zoom: 0.9;
            width: 100%;
            max-width: 100%;
        }

        body:has(.client-dashboard-documents) .client-dashboard-documents > .grid > a {
            min-width: 0;
        }

        body:has(.client-dashboard-documents) .client-dashboard-documents > .grid > a > div:last-child {
            min-width: 0;
        }

        body:has(.client-dashboard-documents) .client-dashboard-documents > .grid > a h3 {
            min-width: 0;
            overflow-wrap: anywhere;
        }

        body:has(.client-dashboard-documents) .client-dashboard-documents > .grid > a > div:last-child > div:last-child {
            min-width: 0;
            align-items: flex-start;
            flex-wrap: wrap;
            row-gap: 0.375rem;
        }

        body:has(.client-dashboard-documents) .client-dashboard-documents > .grid > a > div:last-child > div:last-child > span:last-child {
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        @media (min-width: 64rem) {
            body:has(.client-document-view-page) .client-document-viewer-layout {
                height: calc(100dvh - 3rem) !important;
            }
        }
    </style>
@endif
