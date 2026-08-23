<x-filament-panels::page>

    @php
        $fileUrl = $documentRecord->file_path
            ? Storage::url($documentRecord->file_path)
            : null;
    @endphp

    <nav class="mb-3 flex items-center gap-2 text-sm">

    <a
        href="{{ \App\Filament\Pages\Incoming::getUrl() }}"
        class="font-medium text-blue-600 hover:underline"
    >
        Incoming
    </a>

    <span class="text-gray-400">/</span>

    <a
        href="{{ \App\Filament\Pages\ViewDocument::getUrl([
            'document' => $documentRecord->document_id,
        ]) }}"
        class="font-medium text-blue-600 hover:underline"
    >
        View Document
    </a>

</nav>

    <div class="document-viewer-layout">

        {{-- ========================================================= --}}
        {{-- LEFT SIDE --}}
        {{-- ========================================================= --}}
        <div class="flex min-h-0 min-w-0 flex-col bg-white">

            {{-- LEFT HEADER --}}
            <div
    class="flex h-16 shrink-0 items-center justify-between
           border-b border-gray-200 bg-white px-6"
>

    <div class="flex min-w-0 items-center gap-3">

        {{-- PDF Icon --}}
        <div
            class="flex h-10 w-10 shrink-0 items-center justify-center
                   rounded-lg bg-red-50 text-red-600"
        >
            <svg
                class="h-6 w-6"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.8"
                    d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"
                />

                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.8"
                    d="M14 2v6h6"
                />
            </svg>
        </div>


        {{-- File Details --}}
        <div class="min-w-0">

            <h1 class="truncate text-sm font-bold text-gray-950">
                {{ basename($documentRecord->file_path) }}
            </h1>

            <p class="mt-0.5 text-xs text-gray-500">
                PDF Document
            </p>

        </div>

    </div>


    {{-- Back Button --}}
    <button
        type="button"
        onclick="history.back()"
        class="inline-flex min-w-[105px] shrink-0
               items-center justify-center
               rounded-full border border-gray-300
               bg-white px-5 py-2
               text-sm font-semibold text-gray-600
               transition hover:bg-gray-50"
    >
        Back
    </button>

</div>


            {{-- ===================================================== --}}
            {{-- PDF VIEWER --}}
            {{-- ===================================================== --}}
            <div class="min-h-0 flex-1 overflow-hidden bg-gray-100">

                @if ($previewUrl)

                    <iframe
                        src="{{ $previewUrl }}"
                        class="block h-full w-full border-0"
                        title="Document Preview"
                    ></iframe>

                @else

                    <div class="flex h-full items-center justify-center">

                        <div class="text-center">

                            <div class="mb-4 text-5xl">📄</div>

                            <p class="font-semibold text-gray-700">
                                Preview unavailable
                            </p>

                            <p class="mt-1 text-sm text-gray-500">
                                This file cannot be previewed.
                            </p>

                        </div>

                    </div>

                @endif

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- RIGHT SIDEBAR --}}
        {{-- ========================================================= --}}
        <aside
            class="flex min-h-0 flex-col overflow-hidden
                   border-l border-gray-200 bg-white"
        >

            {{-- ===================================================== --}}
            {{-- SCROLLABLE PART --}}
            {{-- ===================================================== --}}
            <div class="min-h-0 flex-1 overflow-y-auto">


                {{-- ================================================= --}}
                {{-- NOTES --}}
                {{-- ================================================= --}}
                <section class="border-b border-gray-200">

                    <div class="px-4 py-4">
                        <h2 class="text-sm font-bold text-gray-950">
                            Notes
                        </h2>
                    </div>


                    <div class="px-4 pb-5">

                        @forelse ($documentRecord->notes as $note)

                            <div
                                class="mb-3 overflow-hidden
                                       rounded-lg border border-gray-200 bg-white"
                            >

                                {{-- Note Header --}}
                                <div
                                    class="flex items-center justify-between
                                           border-b border-gray-200
                                           px-3 py-3"
                                >

                                    <div class="flex min-w-0 items-center gap-3">

                                        @if (
                                            $note->user &&
                                            $note->user->profile_photo_url
                                        )

                                            <img
                                                src="{{ $note->user->profile_photo_url }}"
                                                alt="{{ $note->user->name ?? 'User' }}"
                                                class="h-9 w-9 shrink-0
                                                       rounded-full object-cover"
                                            >

                                        @else

                                            <div
                                                class="flex h-9 w-9 shrink-0
                                                       items-center justify-center
                                                       rounded-full bg-blue-100
                                                       text-xs font-bold
                                                       text-blue-700"
                                            >
                                                {{
                                                    strtoupper(
                                                        substr(
                                                            $note->user->name ?? 'U',
                                                            0,
                                                            1
                                                        )
                                                    )
                                                }}
                                            </div>

                                        @endif


                                        <span
                                            class="truncate text-sm
                                                   font-bold text-gray-950"
                                        >
                                            {{ $note->user->name ?? 'User' }}
                                        </span>

                                    </div>


                                    <button
                                        type="button"
                                        class="flex h-7 w-7 shrink-0
                                               items-center justify-center
                                               rounded-full text-gray-500
                                               hover:bg-gray-100"
                                    >
                                        <svg
                                            class="h-5 w-5"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M12 6.5h.01M12 12h.01M12 17.5h.01"
                                            />
                                        </svg>
                                    </button>

                                </div>


                                {{-- Note Body --}}
                                <div class="px-3 py-3">

                                    <p class="text-xs leading-5 text-gray-600">
                                        {{
                                            $note->body
                                            ?? $note->note
                                            ?? ''
                                        }}
                                    </p>

                                </div>

                            </div>

                        @empty

                            <div
                                class="rounded-lg border border-dashed
                                       border-gray-300 px-4 py-6 text-center"
                            >
                                <p class="text-xs text-gray-400">
                                    No notes for this document.
                                </p>
                            </div>

                        @endforelse

                    </div>

                </section>


                {{-- ================================================= --}}
                {{-- VERSIONS --}}
                {{-- ================================================= --}}
                <section class="border-b border-gray-200">

                    <div
                        class="flex items-center justify-between
                               gap-2 px-4 py-4"
                    >

                        <h2 class="text-sm font-bold text-gray-950">
                            Versions
                        </h2>


                        <button
                            type="button"
                            class="inline-flex items-center gap-1
                                   whitespace-nowrap rounded-full
                                   border border-blue-500
                                   px-2.5 py-1
                                   text-[10px] font-medium text-blue-500
                                   hover:bg-blue-50"
                        >

                            <svg
                                class="h-3.5 w-3.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>

                            Update new version

                        </button>

                    </div>


                    <div>

                        @forelse ($documentRecord->versions as $version)

                            <div
                                class="flex items-center justify-between
                                       gap-3 border-t border-gray-100
                                       px-4 py-3 hover:bg-gray-50"
                            >

                                <div
                                    class="flex min-w-0 flex-1
                                           items-center gap-3"
                                >

                                    <svg
                                        class="h-5 w-5 shrink-0 text-gray-700"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.7"
                                            d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"
                                        />

                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.7"
                                            d="M14 2v6h6"
                                        />
                                    </svg>


                                    <span
                                        class="truncate text-[11px]
                                               font-medium text-gray-700"
                                    >
                                        {{
                                            $version->name
                                            ?? $version->file_name
                                            ?? 'Document Version'
                                        }}
                                    </span>

                                </div>


                                <span
                                    class="shrink-0 text-right
                                           text-[9px] text-gray-500"
                                >
                                    {{
                                        $version->created_at
                                            ? $version->created_at
                                                ->format('g:i A - M d, Y')
                                            : ''
                                    }}
                                </span>

                            </div>


                        @empty

                            {{-- Current file if there are no versions --}}
                            <div
                                class="flex items-center justify-between
                                       gap-3 border-t border-gray-100
                                       px-4 py-3"
                            >

                                <div
                                    class="flex min-w-0 flex-1
                                           items-center gap-3"
                                >

                                    <svg
                                        class="h-5 w-5 shrink-0 text-gray-700"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.7"
                                            d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"
                                        />

                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.7"
                                            d="M14 2v6h6"
                                        />
                                    </svg>


                                    <span
                                        class="truncate text-[11px]
                                               font-medium text-gray-700"
                                    >
                                        {{
                                            $documentRecord->lao_number
                                            ?? 'Current Document'
                                        }}
                                    </span>

                                </div>


                                <span
                                    class="shrink-0 text-[9px] text-gray-500"
                                >
                                    {{
                                        $documentRecord->created_at
                                            ? $documentRecord->created_at
                                                ->format('g:i A - M d, Y')
                                            : ''
                                    }}
                                </span>

                            </div>

                        @endforelse

                    </div>

                </section>


                {{-- ================================================= --}}
                {{-- AUDIT TRAILS --}}
                {{-- ================================================= --}}
                <section>

                    <div class="px-4 pb-2 pt-5">

                        <h2 class="text-sm font-bold text-gray-950">
                            Audit Trails
                        </h2>

                    </div>


                    <div
                        class="grid grid-cols-[1.1fr_0.8fr_1.3fr]
                               gap-2 bg-gray-100 px-4 py-2
                               text-[9px] font-medium text-gray-600"
                    >
                        <span>User</span>
                        <span>Action</span>
                        <span>Timestamp</span>
                    </div>


                    <div>

                        @forelse ($documentRecord->activityLogs as $log)

                            <div
                                class="grid grid-cols-[1.1fr_0.8fr_1.3fr]
                                       items-center gap-2
                                       border-b border-gray-100
                                       px-4 py-2
                                       text-[9px] text-gray-600"
                            >

                                {{-- User --}}
                                <div
                                    class="flex min-w-0 items-center gap-2"
                                >

                                    @if (
                                        $log->user &&
                                        $log->user->profile_photo_url
                                    )

                                        <img
                                            src="{{ $log->user->profile_photo_url }}"
                                            alt="{{ $log->user->name ?? 'User' }}"
                                            class="h-5 w-5 shrink-0
                                                   rounded-full object-cover"
                                        >

                                    @else

                                        <div
                                            class="flex h-5 w-5 shrink-0
                                                   items-center justify-center
                                                   rounded-full bg-gray-200
                                                   text-[8px] font-bold
                                                   text-gray-600"
                                        >
                                            {{
                                                strtoupper(
                                                    substr(
                                                        $log->user->name ?? 'U',
                                                        0,
                                                        1
                                                    )
                                                )
                                            }}
                                        </div>

                                    @endif


                                    <span class="truncate">
                                        {{ $log->user->name ?? 'User' }}
                                    </span>

                                </div>


                                <span class="truncate">
                                    {{ $log->action ?? 'Updated' }}
                                </span>


                                <span class="truncate">
                                    {{
                                        $log->created_at
                                            ? $log->created_at
                                                ->format('g:i A - M d, Y')
                                            : ''
                                    }}
                                </span>

                            </div>

                        @empty

                            <div class="px-4 py-8 text-center">

                                <span class="text-xs text-gray-400">
                                    No activity recorded.
                                </span>

                            </div>

                        @endforelse

                    </div>

                </section>

            </div>


            {{-- ===================================================== --}}
            {{-- FIXED BOTTOM SIDEBAR --}}
            {{-- ===================================================== --}}
            <div
                class="flex shrink-0 items-center justify-between
                       gap-3 border-t border-gray-200
                       bg-white px-4 py-4"
            >

                <button
                    type="button"
                    onclick="history.back()"
                    class="inline-flex min-w-[90px]
                           items-center justify-center
                           rounded-full bg-gray-200
                           px-5 py-2 text-xs
                           font-bold text-gray-900
                           transition hover:bg-gray-300"
                >
                    Back
                </button>


                <div class="flex items-center gap-2">

                    <button
                        type="button"
                        class="inline-flex min-w-[78px]
                               items-center justify-center
                               rounded-full bg-orange-500
                               px-5 py-2 text-xs
                               font-bold text-white
                               transition hover:bg-orange-600"
                    >
                        Edit
                    </button>


                    <button
                        type="button"
                        class="inline-flex min-w-[90px]
                               items-center justify-center
                               rounded-full bg-green-500
                               px-5 py-2 text-xs
                               font-bold text-white
                               transition hover:bg-green-600"
                    >
                        Message
                    </button>

                </div>

            </div>

        </aside>

    </div>


    <style>
        /*
         * Hide Filament's own "View Documents" page heading.
         * We are drawing our own heading inside the left column.
         */
        .fi-page-header {
            display: none !important;
        }

        /*
         * Remove the normal vertical gap around this Filament page.
         */
        .fi-page {
            gap: 0 !important;
        }

        /*
         * Let the ViewDocument page use all available horizontal space.
         */
        .fi-page-content {
            width: 100% !important;
            max-width: none !important;
            padding-bottom: 0 !important;
        }

        /*
         * This is the KEY layout.
         *
         * The grid starts immediately beneath the Filament topbar.
         * LEFT = header + PDF
         * RIGHT = full-height sidebar
         */
        .document-viewer-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 380px;

            width: 100%;
            height: calc(100dvh - 4rem);

            min-height: 0;
            overflow: hidden;

            border: 1px solid rgb(229 231 235);
            background: white;
        }

        /*
         * Prevent iframe spacing.
         */
        .document-viewer-layout iframe {
            display: block;
        }

        /*
         * Smaller screens:
         * don't force the 380px desktop sidebar beside the PDF.
         */
        @media (max-width: 1023px) {
            .document-viewer-layout {
                grid-template-columns: 1fr;
                height: auto;
                min-height: calc(100dvh - 4rem);
                overflow: visible;
            }

            .document-viewer-layout > div:first-child {
                min-height: 75dvh;
            }

            .document-viewer-layout > aside {
                min-height: 60dvh;
                border-left: 0;
                border-top: 1px solid rgb(229 231 235);
            }
        }
    </style>

</x-filament-panels::page>