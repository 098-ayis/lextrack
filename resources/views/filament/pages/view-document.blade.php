<x-filament-panels::page>

    @php
        $latestFilePath = $documentRecord->latestVersion?->file_path;
        $selectedVersion = $selectedVersionId
            ? $documentRecord->versions->firstWhere('version_id', $selectedVersionId)
            : null;
        $displayedFilePath = $isTransmittalSelected
            ? $documentRecord->transmittal
            : ($selectedVersion?->file_path ?? $latestFilePath);
        $displayedExtension = strtolower(pathinfo((string) $displayedFilePath, PATHINFO_EXTENSION));
        $displayedFileType = match ($displayedExtension) {
            'docx' => 'Word Document (DOCX)',
            'doc' => 'Word Document (DOC)',
            'pdf' => 'PDF Document',
            default => 'Document',
        };
        $displayedVersionNumber = $isTransmittalSelected
            ? null
            : ($selectedVersion?->version_number
                ?? $documentRecord->latestVersion?->version_number
                ?? '1');
        $displayedVersionBadge = $displayedVersionNumber !== null
            ? (str_contains(strtolower((string) $displayedVersionNumber), 'version')
                || str_starts_with(strtolower((string) $displayedVersionNumber), 'v')
                ? $displayedVersionNumber
                : 'v' . $displayedVersionNumber)
            : null;
        $latestRejection = $documentRecord->rejections->sortByDesc('created_at')->first();
        $versions = $documentRecord->versions->sortByDesc('created_at')->values();
        $hasCurrentVersion = $versions->contains(
            fn ($version): bool => $version->file_path === $latestFilePath
        );
        $hasPendingRevision = $this->hasPendingRevision();
        $pendingRevisionVersionId = $hasPendingRevision
            ? $documentRecord->latestVersion?->version_id
            : null;
        $showCurrentDocument = filled($latestFilePath) && !$hasCurrentVersion;
        $attachmentCount = max(1, $versions->count() + ($showCurrentDocument ? 1 : 0));
        $transmittalFilePath = $documentRecord->transmittal;
        $transmittalFileName = filled($transmittalFilePath)
            ? basename((string) $transmittalFilePath)
            : null;
        $transmittalExtension = $transmittalFileName
            ? strtoupper(pathinfo($transmittalFileName, PATHINFO_EXTENSION))
            : 'FILE';
        $transmittalIconColor = in_array(strtolower((string) pathinfo((string) $transmittalFileName, PATHINFO_EXTENSION)), ['doc', 'docx'], true)
            ? 'bg-blue-600'
            : 'bg-red-500';
        $documentTableSection = match ((string) $documentRecord->status) {
            'pending' => 'pending',
            'in_progress' => 'incoming',
            'outgoing' => 'outgoing',
            'completed' => 'completed',
            'rejected' => 'rejected',
            'archived' => 'archived',
            default => 'incoming',
        };
        $activityLogs = $documentRecord->activityLogs->sortByDesc('created_at')->values();
    @endphp

    <div class="document-viewer-page" x-data="{ activeTab: 'details', activityDate: '' }">
        <header class="document-viewer-header flex items-center justify-between gap-4 border border-gray-200 bg-white px-4 py-3 shadow-sm">
            <div class="flex min-w-0 items-center gap-3">
                <div class="document-file-icon flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ in_array($displayedExtension, ['doc', 'docx'], true) ? 'bg-blue-50 text-blue-600' : 'bg-red-50 text-red-600' }}">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 2v6h6" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M8 14h8M8 17h5" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="flex min-w-0 items-center gap-2">
                        <h1 class="truncate text-sm font-bold text-gray-950">
                            {{ $documentRecord->document_name ?: basename((string) $displayedFilePath) ?: 'Document' }}
                        </h1>
                        @if ($displayedVersionBadge !== null)
                            <span class="inline-flex shrink-0 items-center rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-700">{{ $displayedVersionBadge }}</span>
                        @endif
                    </div>
                    <p class="mt-0.5 text-xs text-gray-500">{{ $displayedFileType }}</p>
                </div>
            </div>
            <a
                href="{{ \App\Filament\Pages\Document::getUrl(['section' => $documentTableSection]) }}"
                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-gray-300 bg-white text-gray-600 transition hover:bg-gray-50 hover:text-gray-900"
                aria-label="Exit document view"
                title="Exit document view"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 6 18 18M6 18 18 6" />
                </svg>
            </a>
        </header>

        <div class="document-viewer-layout">
            <section class="document-preview-pane flex min-h-0 min-w-0 flex-col bg-gray-100" aria-label="Document preview">
                <div class="min-h-0 flex-1 overflow-hidden bg-gray-100">
                    @if ($previewUrl)
                        <iframe src="{{ $previewUrl }}" class="block h-full w-full border-0" title="Document Preview"></iframe>
                    @else
                        <div class="flex h-full items-center justify-center p-6">
                            <div class="text-center">
                                <div class="mb-4 text-5xl">📄</div>
                                <p class="font-semibold text-gray-700">Preview unavailable</p>
                                <p class="mt-1 text-sm text-gray-500">This file cannot be previewed.</p>
                            </div>
                        </div>
                    @endif
                </div>
            </section>

            <aside class="document-details-sidebar flex min-h-0 min-w-0 flex-col overflow-hidden border-l border-gray-200 bg-white" aria-label="Document information panel">
                <div class="document-panel-tabs grid grid-cols-4 border-b border-gray-300 bg-gray-50" role="tablist" aria-label="Document details">
                    @foreach ([
                        'details' => 'Details',
                        'notes' => 'Notes',
                        'attachments' => 'Attachments',
                        'activity' => 'History',
                    ] as $tab => $label)
                        <button
                            type="button"
                            id="{{ $tab }}-tab"
                            role="tab"
                            aria-controls="document-panel-{{ $tab }}"
                            @click="activeTab = '{{ $tab }}'"
                            :aria-selected="activeTab === '{{ $tab }}'"
                            :class="activeTab === '{{ $tab }}' ? 'document-panel-tab-active bg-white text-indigo-600' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'"
                            class="document-panel-tab inline-flex min-h-[42px] items-center justify-center border-r border-gray-300 px-2 text-center text-xs font-semibold transition last:border-r-0"
                        >{{ $label }}</button>
                    @endforeach
                </div>

                <div class="document-panel-body min-h-0 flex-1 overflow-y-auto">
                    <section id="document-panel-details" x-show="activeTab === 'details'" x-cloak role="tabpanel" aria-labelledby="details-tab" class="document-panel-section">
                        <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-4 py-3">
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-500">Overview</p>
                            </div>
                            <div class="shrink-0">
                                {{ ($this->editDocumentDetailsAction)(['document' => $documentRecord->document_id]) }}
                            </div>
                        </div>

                        <div class="space-y-5 px-4 pb-24 pt-4">
                            <section>
                                <dl class="mt-3 space-y-3">
                                    <div class="document-detail-row"><dt>Description</dt><dd>{{ $documentRecord->description ?: 'Not set' }}</dd></div>
                                    <div class="document-detail-row"><dt>Document Name</dt><dd>{{ $documentRecord->document_name ?: (basename((string) $latestFilePath) ?: 'Not set') }}</dd></div>
                                    <div class="document-detail-row"><dt>LAO Number</dt><dd>{{ $documentRecord->lao_number ?: 'Not set' }}</dd></div>
                                    <div class="document-detail-row"><dt>Status</dt><dd><span class="inline-flex items-center rounded-full px-2 py-1 text-[10px] font-semibold {{ $documentRecord->statusClasses() }}">{{ $documentRecord->statusLabel() }}</span></dd></div>
                                    <div class="document-detail-row"><dt>Document Type</dt><dd>{{ $documentRecord->document_type ?? 'Unknown' }}</dd></div>
                                    <div class="document-detail-row"><dt>Office / Unit</dt><dd>{{ $documentRecord->office_unit ?: 'Not set' }}</dd></div>
                                    <div class="document-detail-row"><dt>Particulars</dt><dd>{{ $documentRecord->particulars ?: 'Not set' }}</dd></div>
                                    @if ($documentRecord->status !== 'pending')
                                        <div class="document-detail-row"><dt>Action Taken</dt><dd>{{ $documentRecord->action_type ?? 'Not set' }}</dd></div>
                                        <div class="document-detail-row"><dt>Deadline</dt><dd>{{ $documentRecord->deadline?->format('F d, Y') ?? 'Not set' }}</dd></div>
                                    @endif
                                </dl>
                            </section>

                            @if ($documentRecord->status !== 'pending')
                                <section class="border-t border-gray-200 pt-4">
                                    <dl class="mt-3 space-y-3">
                                        <div class="document-detail-row"><dt>Outgoing Date</dt><dd>{{ $documentRecord->outgoing_date ? \Carbon\Carbon::parse($documentRecord->outgoing_date)->format('F d, Y') : 'Not set' }}</dd></div>
                                        <div class="document-detail-row"><dt>Sent Date</dt><dd>{{ $documentRecord->sent_date ? \Carbon\Carbon::parse($documentRecord->sent_date)->format('F d, Y') : 'Not set' }}</dd></div>
                                        <div class="document-detail-row"><dt>Sent To</dt><dd>{{ $documentRecord->sent_to ?: 'Not set' }}</dd></div>
                                        <div class="document-detail-row"><dt>Returned From</dt><dd>{{ $documentRecord->returned_from ?: 'Not returned' }}</dd></div>
                                        <div class="document-detail-row"><dt>Date Returned</dt><dd>{{ $documentRecord->date_returned ? \Carbon\Carbon::parse($documentRecord->date_returned)->format('F d, Y') : 'Not returned' }}</dd></div>
                                        @if ($latestRejection)
                                            <div class="document-detail-row document-detail-row-stacked"><dt>Rejection Reason</dt><dd>{{ $latestRejection->reason }}</dd></div>
                                        @endif
                                    </dl>
                                </section>
                            @endif

                            <section class="border-t border-gray-200 pt-4">
                                <dl class="mt-3 space-y-3">
                                    <div class="document-detail-row"><dt>File</dt><dd>{{ basename((string) $latestFilePath) ?: 'No file' }}</dd></div>
                                    <div class="document-detail-row"><dt>Uploaded</dt><dd>{{ $documentRecord->created_at?->format('F d, Y') ?? 'Unknown' }}</dd></div>
                                    <div class="document-detail-row"><dt>Uploaded By</dt><dd>{{ $documentRecord->user?->name ?? 'Unknown' }}</dd></div>
                                    <div class="document-detail-row"><dt>Last Updated</dt><dd>{{ $documentRecord->updated_at?->format('F d, Y') ?? 'Unknown' }}</dd></div>
                                </dl>
                            </section>
                        </div>
                    </section>

                    <section id="document-panel-notes" x-show="activeTab === 'notes'" x-cloak role="tabpanel" class="document-panel-section">
                        @include('filament.pages.document-notes')
                    </section>

                    <section id="document-panel-attachments" x-show="activeTab === 'attachments'" x-cloak role="tabpanel" class="document-panel-section">
                        <section class="border-b border-gray-200">
                            <div class="flex items-center justify-between gap-3 px-4 py-3">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-500">Transmittal</p>
                            </div>

                        @if ($transmittalFileName)
                            <div
                                class="border-b border-gray-200"
                            >
                                <div class="flex items-center">
                                    <button
                                        type="button"
                                        wire:click="selectTransmittal"
                                        wire:loading.attr="disabled"
                                        class="flex min-w-0 flex-1 items-center gap-3 px-4 py-3 text-left transition {{ $isTransmittalSelected ? 'bg-blue-50' : 'hover:bg-gray-50' }}"
                                        title="View transmittal in document preview"
                                    >
                                    <span class="relative inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-sm border border-gray-900 bg-white text-gray-700">
                                        <svg class="absolute inset-0 h-full w-full p-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.4" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.4" d="M14 2v6h6" />
                                        </svg>
                                        <span class="absolute bottom-0.5 left-0.5 right-0.5 {{ $transmittalIconColor }} text-center text-[7px] font-bold leading-3 text-white">
                                            {{ $transmittalExtension }}
                                        </span>
                                    </span>
                                        <span class="min-w-0 flex-1 truncate text-xs text-gray-900">{{ $transmittalFileName }}</span>
                                    </button>
                                    <a
                                        href="{{ route('admin.documents.transmittal.download', ['document' => $documentRecord->public_id]) }}"
                                        class="mr-4 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-gray-600 transition hover:bg-gray-100 hover:text-blue-600"
                                        title="Download transmittal"
                                        aria-label="Download transmittal"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14" />
                                        </svg>
                                    </a>
                                </div>

                            </div>
                        @else
                            <div class="px-4 py-4 text-xs italic text-gray-400">
                                No transmittal uploaded.
                            </div>
                        @endif
                        </section>

                        <section>
                            <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-500">Document Versions</p>
                                    <span class="text-[10px] text-gray-400">({{ $attachmentCount }})</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    {{ ($this->addVersionAction)(['document' => $documentRecord->document_id]) }}
                                </div>
                            </div>

                        @foreach ($versions as $version)
                            @php
                                $versionNumber = trim((string) ($version->version_number ?? ''));
                                $versionBadge = $versionNumber !== ''
                                    ? (str_contains(strtolower($versionNumber), 'version') || str_starts_with(strtolower($versionNumber), 'v') ? $versionNumber : 'v' . $versionNumber)
                                    : 'v' . ($loop->iteration + 1);
                                $versionFileName = $version->file_path ? basename($version->file_path) : 'Document';
                                $isSelectedVersion = $selectedVersionId === $version->version_id;
                                $isPendingRevisionVersion = $pendingRevisionVersionId !== null && (int) $pendingRevisionVersionId === (int) $version->version_id;
                            @endphp
                            <div wire:key="document-version-{{ $version->version_id }}" class="group flex w-full items-center gap-2 border-b border-gray-200 px-4 py-3 {{ $isSelectedVersion ? 'bg-blue-50' : 'bg-white' }} hover:bg-gray-50">
                                <button type="button" wire:click="selectVersion({{ $version->version_id }})" wire:loading.attr="disabled" class="flex min-w-0 flex-1 items-center gap-3 text-left" title="View {{ $versionFileName }}">
                                    <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded bg-red-500 text-[8px] font-bold text-white">{{ strtoupper(pathinfo($versionFileName, PATHINFO_EXTENSION)) ?: 'FILE' }}</span>
                                    <span class="min-w-0 flex-1 break-words text-xs font-medium leading-5 text-gray-900">{{ $versionFileName }}</span>
                                    <span class="inline-flex shrink-0 items-center rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-700">{{ $versionBadge }}</span>
                                </button>
                                <div class="flex shrink-0 items-center gap-1 transition-opacity {{ $isPendingRevisionVersion ? 'opacity-100' : 'opacity-0 group-hover:opacity-100 group-focus-within:opacity-100' }}">
                                    @if ($isPendingRevisionVersion)
                                        <a
                                            href="{{ route('admin.document.version.download', ['document' => $documentRecord->public_id, 'version' => $version->version_id]) }}"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-md text-gray-600 hover:bg-white hover:text-blue-600"
                                            title="Download version"
                                            aria-label="Download version"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14" />
                                            </svg>
                                        </a>
                                    @else
                                        <button type="button" wire:click="selectVersion({{ $version->version_id }})" wire:loading.attr="disabled" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-gray-600 hover:bg-white hover:text-blue-600" title="View version" aria-label="View version">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z" /><circle cx="12" cy="12" r="2.75" /></svg>
                                        </button>
                                        <a
                                            href="{{ route('admin.document.version.download', ['document' => $documentRecord->public_id, 'version' => $version->version_id]) }}"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-md text-gray-600 hover:bg-white hover:text-blue-600"
                                            title="Download version"
                                            aria-label="Download version"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14" />
                                            </svg>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        @if ($showCurrentDocument)
                            @php $currentFileName = $latestFilePath ? basename($latestFilePath) : 'Document'; @endphp
                            <button type="button" wire:click="selectCurrentDocument" wire:loading.attr="disabled" class="flex w-full items-center gap-3 border-b border-gray-200 px-4 py-3 text-left {{ $selectedVersionId === null && !$isTransmittalSelected ? 'bg-blue-50' : 'bg-white' }} hover:bg-gray-50">
                                <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded bg-red-500 text-[8px] font-bold text-white">{{ strtoupper(pathinfo($currentFileName, PATHINFO_EXTENSION)) ?: 'FILE' }}</span>
                                <span class="min-w-0 flex-1 break-words text-xs font-medium leading-5 text-gray-900">{{ $currentFileName }}</span>
                                <span class="inline-flex shrink-0 items-center rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-700">v1</span>
                            </button>
                        @endif

                        @if ($versions->isEmpty() && !$showCurrentDocument)
                            <div class="px-4 py-8 text-center text-xs italic text-gray-400">No document file available.</div>
                        @endif
                        </section>
                    </section>

                    <section id="document-panel-activity" x-show="activeTab === 'activity'" x-cloak role="tabpanel" class="document-panel-section">
                        <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-4 py-3">
                            <div>
                                <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-gray-500">History</p>
                            </div>
                            <label class="flex items-center gap-2 text-[11px] font-medium text-gray-600">
                                <span>Date</span>
                                <input
                                    type="date"
                                    x-model="activityDate"
                                    class="h-8 rounded-md border border-gray-300 bg-white px-2 text-[11px] text-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    aria-label="Filter activity logs by date"
                                >
                                <button
                                    type="button"
                                    @click="activityDate = ''"
                                    class="h-8 rounded-md border border-gray-300 bg-white px-2 text-[11px] font-semibold text-gray-600 shadow-sm transition hover:bg-gray-50"
                                >
                                    Reset
                                </button>
                            </label>
                        </div>
                        <div class="grid grid-cols-[1.1fr_0.9fr_1.3fr] gap-2 bg-gray-50 px-4 py-2 text-[10px] font-medium text-gray-600"><span>User</span><span>Action</span><span>Timestamp</span></div>
                        @forelse ($activityLogs as $log)
                            <div
                                x-show="!activityDate || activityDate === '{{ $log->created_at?->format('Y-m-d') }}'"
                                data-activity-date="{{ $log->created_at?->format('Y-m-d') }}"
                                class="grid grid-cols-[1.1fr_0.9fr_1.3fr] items-center gap-2 border-b border-gray-100 px-4 py-3 text-[10px] text-gray-600"
                            >
                                <div class="flex min-w-0 items-center gap-2">
                                    @if ($log->user && $log->user->profile_photo_url)
                                        <img src="{{ $log->user->profile_photo_url }}" alt="{{ $log->user->name ?? 'User' }}" class="h-5 w-5 shrink-0 rounded-full object-cover">
                                    @else
                                        <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-gray-200 text-[8px] font-bold text-gray-600">{{ strtoupper(substr($log->user->name ?? 'U', 0, 1)) }}</div>
                                    @endif
                                    <span class="truncate">{{ $log->user->name ?? 'User' }}</span>
                                </div>
                                <span class="truncate" title="{{ $log->action_details ?? $log->action_type }}">{{ $log->action_type ?? 'Updated' }}</span>
                                <span class="truncate">{{ $log->created_at ? $log->created_at->format('g:i A - M d, Y') : '' }}</span>
                            </div>
                        @empty
                            <div class="px-4 py-8 text-center text-xs text-gray-400">No activity recorded.</div>
                        @endforelse
                    </section>
                </div>
            </aside>
        </div>

        @if ($documentRecord->status === 'pending')
            <div
                class="document-review-actions document-review-actions-document"
                aria-label="Document review actions"
            >
                {{ ($this->acceptDocumentAction)(['document' => $documentRecord->document_id]) }}
                {{ ($this->rejectDocumentAction)(['document' => $documentRecord->document_id]) }}
            </div>
        @endif
    </div>

    <style>
        [x-cloak] { display: none !important; }
        .fi-page-header, .fi-page-heading { display: none !important; }
        .fi-page { gap: 0 !important; }
        .fi-page-content { width: 100% !important; max-width: none !important; gap: 0 !important; padding-bottom: 0 !important; }
        .document-viewer-page { width: 100%; }
        .document-viewer-breadcrumb { margin-bottom: 0.75rem; }
        .document-viewer-header { min-height: 60px; }
        .document-viewer-layout { display: flex; width: 100%; height: calc(100dvh - 9.25rem); min-height: 600px; overflow: hidden; border: 1px solid rgb(229 231 235); border-top: 0; background: white; }
        .document-preview-pane { flex: 1 1 auto; min-width: 0; }
        .document-details-sidebar { flex: 0 0 min(440px, 38vw); min-width: 360px; }
        .document-details-sidebar, .document-details-sidebar * { font-size: 12px; }
        .document-panel-tabs { flex: 0 0 auto; }
        .document-panel-tab { border-bottom: 2px solid transparent; }
        .document-panel-tab-active { border-bottom-color: rgb(79 70 229); }
        .document-panel-body { overscroll-behavior: contain; }
        .document-panel-section { min-height: 100%; }
        .document-section-heading { color: rgb(55 65 81); font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; }
        .document-detail-row { display: grid; grid-template-columns: minmax(112px, 0.85fr) minmax(0, 1.35fr); gap: 0.75rem; align-items: start; }
        .document-detail-row dt { color: rgb(107 114 128); font-weight: 500; }
        .document-detail-row dd { min-width: 0; overflow-wrap: anywhere; color: rgb(17 24 39); font-weight: 600; line-height: 1.35; }
        .document-detail-row-stacked { grid-template-columns: 1fr; gap: 0.25rem; }
        .document-notes-sidebar .add-note-button > .fi-icon { color: #ffffff !important; }
        .document-review-actions {
            position: fixed;
            right: calc(1rem + (min(440px, 38vw) - 18rem) / 2);
            bottom: 1rem;
            z-index: 30;
            width: 18rem;
            box-sizing: border-box;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.5rem;
            border: 1px solid rgb(229 231 235);
            border-radius: 0.5rem;
            background: rgb(255 255 255 / 0.96);
            padding: 0.5rem;
            box-shadow: 0 8px 20px rgb(15 23 42 / 0.12);
            backdrop-filter: blur(8px);
        }
        .document-review-actions-document button {
            flex: 1 1 0;
            min-width: 0;
            width: 100%;
            justify-content: center;
        }
        @media (min-width: 64rem) {
            .document-viewer-page {
                position: fixed;
                z-index: 10;
                inset: 4rem 0 0 var(--collapsed-sidebar-width);
                display: flex;
                width: auto;
                height: auto;
                flex-direction: column;
                overflow: hidden;
                padding: 1rem;
                background: rgb(249 250 251);
            }

            .fi-body-has-topbar:has(#fi-main-sidebar.fi-sidebar-open) .document-viewer-page {
                inset-inline-start: var(--sidebar-width);
            }

            .document-viewer-layout {
                min-height: 0;
                height: auto;
                flex: 1 1 auto;
            }
        }
        @media (max-width: 1100px) { .document-details-sidebar { flex-basis: 390px; min-width: 330px; } }
        @media (max-width: 1100px) { .document-review-actions { right: calc(1rem + (390px - 18rem) / 2); } }
        @media (max-width: 900px) {
            .document-viewer-page { position: static; display: block; height: auto; overflow: visible; padding: 0; background: transparent; }
            .document-viewer-layout { height: auto; min-height: 0; overflow: visible; flex-direction: column; }
            .document-preview-pane { min-height: 65dvh; }
            .document-details-sidebar { flex: 0 0 auto; width: 100%; min-width: 0; min-height: 520px; border-top: 1px solid rgb(229 231 235); border-left: 0; }
            .document-panel-body { max-height: none; }
            .document-review-actions { right: auto; bottom: 1rem; left: 50%; width: min(18rem, calc(100vw - 2rem)); transform: translateX(-50%); }
        }
        @media (max-width: 560px) {
            .document-viewer-breadcrumb { font-size: 11px; }
            .document-viewer-header { padding: 0.75rem; }
            .document-file-icon { height: 2rem; width: 2rem; }
            .document-preview-toolbar { align-items: flex-start; flex-direction: column; gap: 0.25rem; }
            .document-panel-tab { min-height: 48px; padding-inline: 0.25rem; font-size: 10px; }
            .document-detail-row { grid-template-columns: minmax(96px, 0.8fr) minmax(0, 1.2fr); gap: 0.5rem; }
        }
    </style>

    <x-filament-actions::modals />

</x-filament-panels::page>
