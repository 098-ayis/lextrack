<x-filament-panels::page>

    @php
        $latestFilePath = $documentRecord->latestVersion?->file_path;
        $selectedVersion = $selectedVersionId
            ? $documentRecord->versions->firstWhere('version_id', $selectedVersionId)
            : null;
        $displayedFilePath = $selectedVersion?->file_path
            ?? $latestFilePath;
        $displayedVersionNumber = $selectedVersion?->version_number
            ?? $documentRecord->latestVersion?->version_number
            ?? '1';
        $displayedVersionBadge = $displayedVersionNumber !== null
            ? (str_contains(strtolower((string) $displayedVersionNumber), 'version')
                || str_starts_with(strtolower((string) $displayedVersionNumber), 'v')
                ? $displayedVersionNumber
                : 'v' . $displayedVersionNumber)
            : null;
    @endphp

    <nav class="mb-3 flex items-center gap-2 text-sm">

    <a
        href="{{ \App\Filament\Pages\Document::getUrl() }}"
        class="font-medium text-blue-600 hover:underline"
    >
        Documents
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

    <header
        class="mb-0 flex items-center justify-between gap-4 border border-gray-200
               border-b-0 bg-white px-5 py-4 shadow-sm"
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
                <div class="flex min-w-0 items-center gap-2">
                    <h1 class="truncate text-sm font-bold text-gray-950">
                        {{ basename((string) $displayedFilePath) }}
                    </h1>

                    @if ($displayedVersionBadge !== null)
                        <span
                            class="inline-flex shrink-0 items-center rounded-full
                                   bg-gray-100 px-2 py-0.5 text-[10px]
                                   font-semibold text-gray-700"
                        >
                            {{ $displayedVersionBadge }}
                        </span>
                    @endif
                </div>

                <p class="mt-0.5 text-xs text-gray-500">
                    PDF Document
                </p>
            </div>
        </div>

        <button
            type="button"
            wire:click="goBack"
            class="inline-flex min-w-[105px] shrink-0 items-center justify-center
                   rounded-full border border-gray-300 bg-white px-5 py-2
                   text-sm font-semibold text-gray-600 transition hover:bg-gray-50"
        >
            Back
        </button>
    </header>

    <div class="document-viewer-layout">

        {{-- LEFT NOTES SIDEBAR --}}
        <aside
            class="document-notes-sidebar min-h-0 overflow-y-auto border-r
                   border-gray-200 bg-white"
        >
            @include('filament.pages.document-notes')
        </aside>

        {{-- ========================================================= --}}
        {{-- CENTER DOCUMENT PREVIEW --}}
        {{-- ========================================================= --}}
        <div class="document-preview-pane flex min-h-0 min-w-0 flex-col bg-white">

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
        {{-- RIGHT DOCUMENT DETAILS SIDEBAR --}}
        {{-- ========================================================= --}}
        <aside
            class="document-details-sidebar flex min-h-0 flex-col overflow-hidden
                   border-l border-gray-200 bg-white"
        >

            {{-- ===================================================== --}}
            {{-- SCROLLABLE PART --}}
            {{-- ===================================================== --}}
            <div class="min-h-0 flex-1 overflow-y-auto">

                @php
                    $latestRejection = $documentRecord->rejections
                        ->sortByDesc('created_at')
                        ->first();
                @endphp

                {{-- ================================================= --}}
                {{-- DOCUMENT DETAILS --}}
                {{-- ================================================= --}}
                <section
                    class="border-b border-gray-200"
                    x-data="{ detailsOpen: true }"
                >
                    <div
                        class="flex items-center justify-between gap-3
                               border-b border-gray-300 px-4 py-3"
                    >
                        <h2 class="text-sm font-bold text-gray-950">
                            Document Details
                        </h2>

                        <div class="flex shrink-0 items-center gap-1">
                            {{ ($this->editDocumentDetailsAction)([
                                'document' => $documentRecord->document_id,
                            ]) }}

                            <button
                                type="button"
                                @click="detailsOpen = !detailsOpen"
                                class="inline-flex h-7 w-7 items-center justify-center
                                       rounded-md text-gray-700 hover:bg-gray-100"
                                title="Toggle document details"
                                :aria-expanded="detailsOpen"
                                aria-label="Toggle document details"
                            >
                                <svg
                                    class="h-5 w-5 transition-transform"
                                    :class="detailsOpen ? 'rotate-180' : ''"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="m6 9 6 6 6-6"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <dl
                        x-show="detailsOpen"
                        class="grid grid-cols-2 gap-x-3 gap-y-3 px-4 py-4 text-xs"
                    >
                        <div class="min-w-0">
                            <dt class="font-medium text-gray-500">LAO No.</dt>
                            <dd class="mt-1 break-words font-semibold text-gray-900">
                                {{ $documentRecord->lao_number ?: 'Not set' }}
                            </dd>
                        </div>

                        <div class="min-w-0">
                            <dt class="font-medium text-gray-500">Status</dt>
                            <dd class="mt-1">
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-[10px] font-semibold {{ $documentRecord->statusClasses() }}">
                                    {{ $documentRecord->statusLabel() }}
                                </span>
                            </dd>
                        </div>

                        <div class="min-w-0">
                            <dt class="font-medium text-gray-500">Document Type</dt>
                            <dd class="mt-1 break-words font-semibold text-gray-900">
                                {{ $documentRecord->type?->type_name ?? 'Unknown' }}
                            </dd>
                        </div>

                        <div class="min-w-0">
                            <dt class="font-medium text-gray-500">Office / Unit</dt>
                            <dd class="mt-1 break-words font-semibold text-gray-900">
                                {{ $documentRecord->office_unit ?: 'Not set' }}
                            </dd>
                        </div>

                        <div class="col-span-2 min-w-0">
                            <dt class="font-medium text-gray-500">Particulars</dt>
                            <dd class="mt-1 break-words leading-5 text-gray-900">
                                {{ $documentRecord->particulars ?: 'Not set' }}
                            </dd>
                        </div>

                        <div class="col-span-2 min-w-0">
                            <dt class="font-medium text-gray-500">Uploaded By</dt>
                            <dd class="mt-1 break-words font-semibold text-gray-900">
                                {{ $documentRecord->user?->name ?? 'Unknown' }}
                                @if ($documentRecord->user?->email)
                                    <span class="block font-normal text-gray-500">
                                        {{ $documentRecord->user->email }}
                                    </span>
                                @endif
                            </dd>
                        </div>

                        <div class="min-w-0">
                            <dt class="font-medium text-gray-500">Action Taken</dt>
                            <dd class="mt-1 break-words font-semibold text-gray-900">
                                {{ $documentRecord->actionType?->action_name ?? ($documentRecord->action_taken ?: 'Not set') }}
                            </dd>
                        </div>

                        <div class="min-w-0">
                            <dt class="font-medium text-gray-500">Deadline</dt>
                            <dd class="mt-1 break-words font-semibold text-gray-900">
                                {{ $documentRecord->deadline?->format('F d, Y') ?? 'Not set' }}
                            </dd>
                        </div>

                        <div class="min-w-0">
                            <dt class="font-medium text-gray-500">Outgoing Date</dt>
                            <dd class="mt-1 break-words font-semibold text-gray-900">
                                {{ $documentRecord->outgoing_date ? \Carbon\Carbon::parse($documentRecord->outgoing_date)->format('F d, Y') : 'Not set' }}
                            </dd>
                        </div>

                        <div class="min-w-0">
                            <dt class="font-medium text-gray-500">Sent Date</dt>
                            <dd class="mt-1 break-words font-semibold text-gray-900">
                                {{ $documentRecord->sent_date ? \Carbon\Carbon::parse($documentRecord->sent_date)->format('F d, Y') : 'Not set' }}
                            </dd>
                        </div>

                        <div class="col-span-2 min-w-0">
                            <dt class="font-medium text-gray-500">Sent To</dt>
                            <dd class="mt-1 break-words font-semibold text-gray-900">
                                {{ $documentRecord->sent_to ?: 'Not set' }}
                            </dd>
                        </div>

                        <div class="min-w-0">
                            <dt class="font-medium text-gray-500">Returned From</dt>
                            <dd class="mt-1 break-words font-semibold text-gray-900">
                                {{ $documentRecord->returned_from ?: 'Not returned' }}
                            </dd>
                        </div>

                        <div class="min-w-0">
                            <dt class="font-medium text-gray-500">Date Returned</dt>
                            <dd class="mt-1 break-words font-semibold text-gray-900">
                                {{ $documentRecord->date_returned ? \Carbon\Carbon::parse($documentRecord->date_returned)->format('F d, Y') : 'Not returned' }}
                            </dd>
                        </div>

                        @if ($latestRejection)
                            <div class="col-span-2 min-w-0">
                                <dt class="font-medium text-gray-500">Rejection Reason</dt>
                                <dd class="mt-1 break-words leading-5 text-gray-900">
                                    {{ $latestRejection->reason }}
                                </dd>
                            </div>
                        @endif

                        <div class="col-span-2 min-w-0">
                            <dt class="font-medium text-gray-500">File</dt>
                            <dd class="mt-1 break-words font-semibold text-gray-900">
                                {{ basename((string) $latestFilePath) ?: 'No file' }}
                            </dd>
                        </div>

                        <div class="min-w-0">
                            <dt class="font-medium text-gray-500">Uploaded</dt>
                            <dd class="mt-1 break-words font-semibold text-gray-900">
                                {{ $documentRecord->created_at?->format('F d, Y') ?? 'Unknown' }}
                            </dd>
                        </div>

                        <div class="min-w-0">
                            <dt class="font-medium text-gray-500">Last Updated</dt>
                            <dd class="mt-1 break-words font-semibold text-gray-900">
                                {{ $documentRecord->updated_at?->format('F d, Y') ?? 'Unknown' }}
                            </dd>
                        </div>
                    </dl>
                </section>

                {{-- ================================================= --}}
                {{-- VERSIONS / ATTACHMENTS --}}
                {{-- ================================================= --}}
                @php
                    $versions = $documentRecord->versions->sortByDesc('created_at')->values();
                    $hasCurrentVersion = $versions->contains(
                        fn ($version): bool => $version->file_path === $latestFilePath
                    );
                    $showCurrentDocument = filled($latestFilePath) && !$hasCurrentVersion;
                    $attachmentCount = max(
                        1,
                        $versions->count() + ($showCurrentDocument ? 1 : 0)
                    );
                @endphp

                <section
                    class="border-b border-gray-200"
                    x-data="{ attachmentsOpen: true }"
                >
                    <div
                        class="flex items-center justify-between gap-3
                               border-b border-gray-300 px-4 py-3"
                    >
                        <div class="flex min-w-0 items-center gap-2">
                            <svg
                                class="h-5 w-5 shrink-0 text-gray-900"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.7"
                                    d="m8.5 12.5 5.9-5.9a3 3 0 1 1 4.2 4.2l-7.8 7.8a4.5 4.5 0 0 1-6.4-6.4l8.1-8.1a2 2 0 1 1 2.8 2.8l-7.6 7.6a.75.75 0 1 1-1.1-1.1l6.4-6.4"
                                />
                            </svg>

                            <span class="truncate text-sm font-medium text-gray-900">
                                {{ $attachmentCount }}
                                {{ $attachmentCount === 1 ? 'attachment' : 'attachments' }}
                            </span>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            {{ ($this->addVersionAction)([
                                'document' => $documentRecord->document_id,
                            ]) }}

                            <button
                                type="button"
                                @click="attachmentsOpen = !attachmentsOpen"
                                class="inline-flex h-7 w-7 items-center justify-center
                                       rounded-md text-gray-900 hover:bg-gray-100"
                                title="Toggle attachments"
                                :aria-expanded="attachmentsOpen"
                                aria-label="Toggle attachments"
                            >
                                <svg
                                    class="h-5 w-5 transition-transform"
                                    :class="attachmentsOpen ? 'rotate-180' : ''"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="m6 9 6 6 6-6"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div x-show="attachmentsOpen">
                        @foreach ($versions as $version)
                            @php
                                $versionNumber = trim((string) ($version->version_number ?? ''));
                                $versionBadge = $versionNumber !== ''
                                    ? (str_contains(strtolower($versionNumber), 'version')
                                        || str_starts_with(strtolower($versionNumber), 'v')
                                        ? $versionNumber
                                        : 'v' . $versionNumber)
                                    : 'v' . ($loop->iteration + 1);
                                $versionFileName = $version->file_path
                                    ? basename($version->file_path)
                                    : 'Document.pdf';
                                $isSelectedVersion = $selectedVersionId === $version->version_id;
                            @endphp

                            <div
                                wire:key="document-version-{{ $version->version_id }}"
                                class="group flex w-full items-center gap-2 border-b
                                       border-gray-300 px-4 py-3
                                       {{ $isSelectedVersion ? 'bg-blue-50' : ($loop->odd ? 'bg-white' : 'bg-gray-50') }}
                                       hover:bg-gray-100"
                            >
                                <button
                                    type="button"
                                    wire:click="selectVersion({{ $version->version_id }})"
                                    wire:loading.attr="disabled"
                                    class="flex min-w-0 flex-1 items-center gap-3 text-left"
                                    title="View {{ $versionFileName }}"
                                >
                                    <span
                                        class="inline-flex h-6 w-6 shrink-0 items-center justify-center
                                               rounded-sm bg-red-500 text-[8px] font-bold text-white"
                                    >
                                        PDF
                                    </span>

                                    <span class="min-w-0 flex-1 break-words text-xs font-medium
                                                 leading-5 text-gray-900">
                                        {{ $versionFileName }}
                                    </span>

                                    <span
                                        class="inline-flex shrink-0 items-center rounded-full
                                               bg-gray-200 px-2 py-0.5 text-[10px]
                                               font-semibold text-gray-700"
                                    >
                                        {{ $versionBadge }}
                                    </span>
                                </button>

                                <div
                                    class="flex shrink-0 items-center gap-1 opacity-0
                                           transition-opacity group-hover:opacity-100
                                           group-focus-within:opacity-100"
                                >
                                    <button
                                        type="button"
                                        wire:click="selectVersion({{ $version->version_id }})"
                                        wire:loading.attr="disabled"
                                        class="inline-flex h-7 w-7 items-center justify-center
                                               rounded-md text-gray-600 hover:bg-white
                                               hover:text-blue-600"
                                        title="View version"
                                        aria-label="View version"
                                    >
                                        <svg
                                            class="h-4 w-4"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            viewBox="0 0 24 24"
                                            aria-hidden="true"
                                        >
                                            <path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z" />
                                            <circle cx="12" cy="12" r="2.75" />
                                        </svg>
                                    </button>

                                    {{ ($this->deleteVersionAction)([
                                        'version' => $version->version_id,
                                    ]) }}
                                </div>
                            </div>
                        @endforeach

                        @if ($showCurrentDocument)
                            @php
                                $currentFileName = $latestFilePath
                                    ? basename($latestFilePath)
                                    : 'Document.pdf';
                            @endphp

                            <button
                                type="button"
                                wire:click="selectCurrentDocument"
                                wire:loading.attr="disabled"
                                class="flex w-full items-center gap-3 border-b border-gray-300
                                       px-4 py-3 text-left
                                       {{ $selectedVersionId === null ? 'bg-blue-50' : ($versions->count() % 2 === 0 ? 'bg-white' : 'bg-gray-50') }}
                                       hover:bg-gray-100"
                            >
                                <span
                                    class="inline-flex h-6 w-6 shrink-0 items-center justify-center
                                           rounded-sm bg-red-500 text-[8px] font-bold text-white"
                                >
                                    PDF
                                </span>

                                <div class="flex min-w-0 flex-1 items-center gap-2">
                                    <span class="min-w-0 break-words font-medium
                                                 leading-5 text-gray-900 text-[10px]">
                                        {{ $currentFileName }}
                                    </span>

                                    <span
                                        class="inline-flex shrink-0 items-center rounded-full
                                               bg-gray-200 px-2 py-0.5 text-[10px]
                                               font-semibold text-gray-700"
                                    >
                                        v1
                                    </span>
                                </div>
                            </button>
                        @endif

                        @if ($versions->isEmpty() && !$showCurrentDocument)
                            <div class="px-4 py-4 text-xs italic text-gray-400">
                                No document file available.
                            </div>
                        @endif
                    </div>
                </section>


                {{-- ================================================= --}}
                {{-- AUDIT TRAILS --}}
                {{-- ================================================= --}}
                <section x-data="{ auditOpen: true }">

                    <div
                        class="flex items-center justify-between gap-3
                               px-4 pb-2 pt-5"
                    >

                        <h2 class="text-sm font-bold text-gray-950">
                            Audit Trails
                        </h2>

                        <button
                            type="button"
                            @click="auditOpen = !auditOpen"
                            class="inline-flex h-7 w-7 items-center justify-center
                                   rounded-md text-gray-700 hover:bg-gray-100"
                            title="Toggle audit trails"
                            :aria-expanded="auditOpen"
                            aria-label="Toggle audit trails"
                        >
                            <svg
                                class="h-5 w-5 transition-transform"
                                :class="auditOpen ? 'rotate-180' : ''"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="m6 9 6 6 6-6"
                                />
                            </svg>
                        </button>
                    </div>

                    <div x-show="auditOpen">
                        <div
                        class="grid grid-cols-[1.1fr_0.8fr_1.3fr]
                               gap-2 bg-gray-100 px-4 py-2
                               text-[10px] font-medium text-gray-600"
                        >
                        <span>User</span>
                        <span>Action</span>
                        <span>Timestamp</span>
                        </div>


                    <div>

                        @forelse ($documentRecord->activityLogs->sortByDesc('created_at') as $log)

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


                                <span
                                    class="truncate"
                                    title="{{ $log->action_details ?? $log->action_type }}"
                                >
                                    {{ $log->action_type ?? 'Updated' }}
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
                    </div>

                </section>

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
            gap: 0 !important;
            padding-bottom: 0 !important;
        }

        /*
         * This is the KEY layout.
         *
         * The grid starts immediately beneath the Filament topbar.
         * LEFT = notes sidebar
         * CENTER = document preview
         * RIGHT = document details sidebar
         */
        .document-viewer-layout {
            display: grid;
            grid-template-columns: 320px minmax(0, 1fr) 320px;

            gap: 0;
            margin: 0;
            width: 100%;
            height: calc(100dvh - 4rem);

            min-height: 0;
            overflow: hidden;

            border: 1px solid rgb(229 231 235);
            background: white;
        }

        .document-notes-sidebar,
        .document-details-sidebar {
            margin: 0;
            padding: 0;
        }

        /*
         * Prevent iframe spacing.
         */
        .document-viewer-layout iframe {
            display: block;
        }

        .document-notes-sidebar {
            grid-column: 1;
            grid-row: 1;
        }

        .document-preview-pane {
            grid-column: 2;
            grid-row: 1;
        }

        .document-details-sidebar {
            grid-column: 3;
            grid-row: 1;
            min-width: 0;
        }

        /*
         * Smaller screens:
         * don't force the compact desktop sidebar beside the PDF.
         */
        @media (max-width: 1023px) {
            .document-viewer-layout {
                grid-template-columns: 1fr;
                height: auto;
                min-height: calc(100dvh - 4rem);
                overflow: visible;
            }

            .document-notes-sidebar,
            .document-preview-pane,
            .document-details-sidebar,
            .document-viewer-layout > div:first-child,
            .document-viewer-layout > aside {
                grid-column: auto;
                grid-row: auto;
            }

            .document-preview-pane,
            .document-viewer-layout > div:first-child {
                min-height: 75dvh;
            }

            .document-notes-sidebar,
            .document-details-sidebar,
            .document-viewer-layout > aside {
                min-height: 60dvh;
                border-left: 0;
                border-right: 0;
                border-top: 1px solid rgb(229 231 235);
            }
        }
    </style>

</x-filament-panels::page>
