<x-filament-panels::page>
    @php
        $latestFilePath = $documentRecord->latestVersion?->file_path;
        $actionTypeColor = filled($documentRecord->action_type)
            ? \App\Models\ActionType::query()->where('action_name', $documentRecord->action_type)->value('color')
            : null;
        if (! is_string($actionTypeColor) || ! preg_match('/^#[0-9a-f]{6}$/i', $actionTypeColor)) {
            $actionTypeColor = '#64748B';
        }
        $documentTypeColor = filled($documentRecord->document_type)
            ? \App\Models\DocumentType::query()->where('type_name', $documentRecord->document_type)->value('color')
            : null;
        if (! is_string($documentTypeColor) || ! preg_match('/^#[0-9a-f]{6}$/i', $documentTypeColor)) {
            $documentTypeColor = '#64748B';
        }
        $fileBadgeClass = fn (string $fileName): string => match (strtolower((string) pathinfo($fileName, PATHINFO_EXTENSION))) {
            'pdf' => 'document-file-badge-pdf',
            'doc', 'docx' => 'document-file-badge-docx',
            default => 'document-file-badge-default',
        };
        $selectedVersion = $selectedVersionId
            ? $documentRecord->versions->firstWhere('version_id', $selectedVersionId)
            : null;
        $transmittalFiles = collect();
        foreach ($documentRecord->transmittalAttachments as $attachment) {
            $transmittalFiles->push(['id' => $attachment->transmittal_id, 'path' => $attachment->file_path]);
        }
        if ($transmittalFiles->isEmpty() && filled($documentRecord->transmittal)) {
            $transmittalFiles->push(['id' => null, 'path' => $documentRecord->transmittal]);
        }
        $transmittalFilePath = $selectedTransmittalId === null
            ? $documentRecord->transmittal
            : $documentRecord->transmittalAttachments->firstWhere('transmittal_id', $selectedTransmittalId)?->file_path;
        $displayedFilePath = $isTransmittalSelected
            ? $transmittalFilePath
            : ($selectedVersion?->file_path ?? $latestFilePath);
        $displayedFileName = filled($displayedFilePath)
            ? basename((string) $displayedFilePath)
            : 'Preview unavailable';
        $displayedFileExtension = strtolower((string) pathinfo($displayedFileName, PATHINFO_EXTENSION));
        $displayedFileTypeLabel = match ($displayedFileExtension) {
            'pdf' => 'PDF Document',
            'docx' => 'DOCX Document',
            'doc' => 'DOC Document',
            default => $displayedFileExtension !== '' ? strtoupper($displayedFileExtension) . ' Document' : 'Document',
        };
        $displayedFileIconClass = match ($displayedFileExtension) {
            'pdf' => 'document-preview-file-icon-pdf',
            'doc', 'docx' => 'document-preview-file-icon-docx',
            default => 'document-preview-file-icon-default',
        };
        $displayedVersion = $selectedVersion
            ?? (! $isTransmittalSelected ? $documentRecord->latestVersion : null);
        $displayedVersionNumber = $isTransmittalSelected || $displayedVersion?->source === 'client'
            ? null
            : ($displayedVersion?->version_number ?? '1');
        $displayedVersionBadge = $displayedVersionNumber !== null
            ? (str_contains(strtolower((string) $displayedVersionNumber), 'version')
                || str_starts_with(strtolower((string) $displayedVersionNumber), 'v')
                ? $displayedVersionNumber
                : 'v' . $displayedVersionNumber)
            : null;
        $latestRejection = $documentRecord->status === 'rejected'
            ? $documentRecord->rejections->sortByDesc('created_at')->first()
            : null;
        $allVersions = $documentRecord->versions;
        $submittedFiles = $allVersions
            ->filter(fn ($version): bool => $version->source === 'client')
            ->sortByDesc('created_at')
            ->values();
        $versions = $allVersions
            ->reject(fn ($version): bool => $version->source === 'client')
            ->sortByDesc(function ($version): int {
                preg_match('/(\d+)\s*$/', (string) $version->version_number, $matches);

                return (int) ($matches[1] ?? 0);
            })
            ->values();
        $hasCurrentVersion = $allVersions->contains(fn ($version) => $version->file_path === $latestFilePath);
        $hasPendingRevision = $this->hasPendingRevision();
        $pendingRevisionVersionId = $hasPendingRevision ? $documentRecord->latestVersion?->version_id : null;
        $showCurrentDocument = filled($latestFilePath) && ! $hasCurrentVersion;
        $activityLogs = $documentRecord->activityLogs
            ->filter(fn ($log) => $this->shouldShowActivity($log))
            ->sortByDesc('created_at')
            ->values();
        $softCopyRequest = $documentRecord->documentRequests
            ->where('copy_type', 'soft_copy')
            ->where('status', 'accepted')
            ->sortByDesc('date_of_request')
            ->first();
    @endphp

    <div class="document-viewer-page" x-data="{ activeTab: 'details', printFile(url) { const printWindow = window.open(url, '_blank'); if (!printWindow) return; printWindow.addEventListener('load', () => { printWindow.focus(); printWindow.print(); }, { once: true }); } }">
        <div class="document-viewer-layout {{ $softCopyRequest ? 'document-request-viewer-layout' : '' }}">
            <section class="document-panel-card document-preview-card" aria-label="Document preview">
                <div class="document-preview-heading">
                    <div class="document-preview-file-meta">
                        <span class="document-preview-file-icon {{ $displayedFileIconClass }}" title="{{ $displayedFileTypeLabel }}" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3.5h7l4 4V20a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4.5a1 1 0 0 1 1-1Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 3.5V8h4" />
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <h2 title="{{ $displayedFileName }}">{{ $displayedFileName }}</h2>
                            <p>{{ $displayedFileTypeLabel }}</p>
                        </div>
                    </div>
                    @if ($previewPageCount !== null)
                        <span class="document-version-badge document-page-count">{{ $previewPageCount }} {{ $previewPageCount === 1 ? 'page' : 'pages' }}</span>
                    @endif
                    @if ($displayedVersionBadge !== null)
                        <span class="document-version-badge">{{ $displayedVersionBadge }}</span>
                    @endif
                </div>

                <div class="document-preview-frame">
                @if (auth()->user()->hasRole('Super Admin'))

                    <div class="flex h-full flex-col items-center justify-center p-6 text-center">
                        <x-heroicon-o-lock-closed class="h-12 w-12 text-gray-400" />

                        <h3 class="mt-4 text-lg font-semibold text-gray-800">
                            Document Preview Restricted
                        </h3>

                        <p class="mt-2 max-w-sm text-sm text-gray-500">
                            Only authorized Legal Staff can view or download
                            the original document.
                        </p>
                    </div>

                    @elseif ($previewUrl)
                        <iframe src="{{ $previewUrl }}#toolbar=0" title="Document Preview" loading="eager"></iframe>
                    @else
                        <div class="document-preview-empty">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M14 3v6h5M8 14h8M8 17h8"/></svg>
                            <p>Preview unavailable</p>
                            <span>This file cannot be previewed.</span>
                        </div>
                    @endif
                </div>
            </section>

            <aside class="document-panel-card document-side-panel" aria-label="Document information">
                <nav class="document-side-tabs {{ $softCopyRequest ? 'document-side-tabs-two' : '' }}" role="tablist" aria-label="Document information tabs">
                    <button type="button" role="tab" :aria-selected="activeTab === 'details'" :class="{ 'is-active': activeTab === 'details' }" @click="activeTab = 'details'">Details</button>
                    @if (! $softCopyRequest)
                        <button type="button" role="tab" :aria-selected="activeTab === 'notes'" :class="{ 'is-active': activeTab === 'notes' }" @click="activeTab = 'notes'">Notes</button>
                    @endif
                    <button type="button" role="tab" :aria-selected="activeTab === 'attachments'" :class="{ 'is-active': activeTab === 'attachments' }" @click="activeTab = 'attachments'">Attachments</button>
                    @if (! $softCopyRequest)
                        <button type="button" role="tab" :aria-selected="activeTab === 'history'" :class="{ 'is-active': activeTab === 'history' }" @click="activeTab = 'history'">History</button>
                    @endif
                </nav>

                <div class="document-side-content">
            <section class="document-details-card" x-show="activeTab === 'details' || activeTab === 'attachments'" aria-label="Document details and files">
                @if (! $softCopyRequest)
                <div class="document-card-heading" x-show="activeTab === 'details'">
                    <div class="document-edit-controls">
                        @if ($isEditingDetails)
                            <button type="button" wire:click="cancelEditingDetails" class="document-cancel-button">Cancel</button>
                            <button type="button" wire:click="saveDocumentDetails" wire:loading.attr="disabled" @disabled(! $this->hasDocumentDetailsChanges()) class="document-edit-button">Save Changes</button>
                        @else
                            <button
                                type="button"
                                wire:click="startEditingDetails"
                                @disabled(in_array($documentRecord->status, ['pending', 'rejected'], true))
                                title="{{ in_array($documentRecord->status, ['pending', 'rejected'], true) ? 'Pending and rejected documents are locked' : 'Edit document details' }}"
                                aria-label="Edit document details"
                                class="document-edit-button document-edit-icon-button"
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m15 5 4 4M4 20l4.2-.8L19 8.4a2.1 2.1 0 0 0-3-3L5.2 16.2 4 20Z" /></svg>
                            </button>
                        @endif
                    </div>
                </div>
                @endif

                <div class="document-details-scroll">
                    <div class="document-detail-tab-content" x-show="activeTab === 'details'">
                    @if ($errors->any())
                        <p class="document-edit-error">{{ $errors->first() }}</p>
                    @endif
                    @if ($softCopyRequest)
                        <dl class="document-detail-list">
                            <div class="document-detail-row"><dt>Purpose</dt><dd>{{ $softCopyRequest->purpose ?: '—' }}</dd></div>
                            <div class="document-detail-row"><dt>Details</dt><dd>{{ $softCopyRequest->purpose_details ?: '—' }}</dd></div>
                            <div class="document-detail-row"><dt>Type</dt><dd>Soft copy</dd></div>
                            <div class="document-detail-row"><dt>Requested By</dt><dd>{{ $softCopyRequest->user?->name ?? '—' }}</dd></div>
                            <div class="document-detail-row"><dt>Date of Request</dt><dd>{{ $softCopyRequest->date_of_request?->format('F d, Y') ?? '—' }}</dd></div>
                            <div class="document-detail-row"><dt>Date Accepted</dt><dd>{{ $softCopyRequest->date_processed?->format('F d, Y') ?? '—' }}</dd></div>
                        </dl>
                    @else
                    <dl class="document-detail-list">
                        <div class="document-detail-row"><dt>LAO Number</dt><dd>@if ($isEditingDetails)<input class="document-inline-field document-inline-readonly" wire:model="documentDetailsForm.lao_number" aria-label="LAO Number" readonly>@else{{ $documentRecord->lao_number ?: '—' }}@endif</dd></div>
                        <div class="document-detail-row"><dt>Status</dt><dd>@if ($isEditingDetails && $documentRecord->status === 'completed')<select class="document-inline-field" wire:model.live="documentDetailsForm.status" aria-label="Status"><option value="pending">Pending</option><option value="in_progress">Incoming</option><option value="completed">Completed</option><option value="returned">Returned</option><option value="outgoing">Outgoing</option><option value="rejected">Rejected</option></select>@else<span class="document-table-badge document-status-badge {{ $documentRecord->statusClasses() }}">{{ $documentRecord->status === 'in_progress' ? 'Incoming' : $documentRecord->statusLabel() }}</span>@endif</dd></div>
                        <div class="document-detail-row">
                            <dt>Document Type</dt>
                            <dd>
                                @if ($isEditingDetails)
                                    <div class="document-type-field">
                                        @if (($documentDetailsForm['document_type'] ?? null) === \App\Filament\Pages\ViewDocument::OTHER_DOCUMENT_TYPE_VALUE)
                                            <input class="document-inline-field" wire:model.live.debounce.500ms="documentDetailsForm.document_type_other" aria-label="Other document type" placeholder="Specify document type">
                                        @else
                                            <select class="document-inline-field document-inline-select" wire:model.live="documentDetailsForm.document_type" aria-label="Document Type">
                                                <option value="">Select a document type</option>
                                                @foreach ($this->getDocumentTypeOptions() as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </div>
                                @elseif (filled($documentRecord->document_type))
                                    <span class="document-table-badge" style="--badge-color: {{ $documentTypeColor }}; background-color: color-mix(in srgb, var(--badge-color) 14%, white); color: var(--badge-color); border-color: color-mix(in srgb, var(--badge-color) 18%, transparent);">{{ $documentRecord->document_type }}</span>
                                @else
                                    <span>—</span>
                                @endif
                            </dd>
                        </div>
                        <div class="document-detail-row"><dt>Office / Unit</dt><dd>@if ($isEditingDetails)<select class="document-inline-field document-inline-select" wire:model.live="documentDetailsForm.office_unit" aria-label="Office / Unit"><option value="">Select an office or unit</option>@foreach ($this->getOfficeUnitOptions() as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>@else{{ $documentRecord->office_unit ?: '—' }}@endif</dd></div>
                        <div class="document-detail-row"><dt>Particulars</dt><dd>@if ($isEditingDetails)<textarea class="document-inline-field document-inline-textarea" wire:model.live.debounce.500ms="documentDetailsForm.particulars" aria-label="Particulars" rows="2"></textarea>@else{{ $documentRecord->particulars ?: $documentRecord->description ?: '—' }}@endif</dd></div>
                        <div class="document-detail-row"><dt>Action Taken</dt><dd>@if ($isEditingDetails)<select class="document-inline-field document-inline-select" wire:model.live="documentDetailsForm.action_type" aria-label="Action Taken"><option value="">Select an action type</option>@foreach ($this->getActionTypeOptions() as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>@elseif (filled($documentRecord->action_type))<span class="document-table-badge document-action-badge" style="--badge-color: {{ $actionTypeColor }}">{{ $documentRecord->action_type }}</span>@else<span>Not set</span>@endif</dd></div>
                        <div class="document-detail-row"><dt>Deadline</dt><dd>@if ($isEditingDetails)<input class="document-inline-field" type="date" wire:model.live="documentDetailsForm.deadline" aria-label="Deadline">@else{{ $documentRecord->deadline?->format('F d, Y') ?? 'Not set' }}@endif</dd></div>
                        @if ($documentRecord->status !== 'pending')
                            <div class="document-detail-row"><dt>Outgoing Date</dt><dd>@if ($isEditingDetails)<input class="document-inline-field" type="date" wire:model.live="documentDetailsForm.outgoing_date" aria-label="Outgoing Date">@else{{ $documentRecord->outgoing_date?->format('F d, Y') ?? 'Not set' }}@endif</dd></div>
                            <div class="document-detail-row"><dt>Sent Date</dt><dd>@if ($isEditingDetails)<input class="document-inline-field" type="date" wire:model.live="documentDetailsForm.sent_date" aria-label="Sent Date">@else{{ $documentRecord->sent_date?->format('F d, Y') ?? 'Not set' }}@endif</dd></div>
                            <div class="document-detail-row"><dt>Sent To</dt><dd>@if ($isEditingDetails)<input class="document-inline-field" wire:model.live.debounce.500ms="documentDetailsForm.sent_to" aria-label="Sent To">@else{{ $documentRecord->sent_to ?: 'Not set' }}@endif</dd></div>
                            <div class="document-detail-row"><dt>Returned From</dt><dd>@if ($isEditingDetails && $documentRecord->status === 'outgoing')<input class="document-inline-field document-inline-readonly" value="{{ $documentDetailsForm['sent_to'] ?? '' }}" aria-label="Returned From" readonly>@elseif ($isEditingDetails)<input class="document-inline-field" wire:model.live.debounce.500ms="documentDetailsForm.returned_from" aria-label="Returned From">@else{{ $documentRecord->returned_from ?: 'Not returned' }}@endif</dd></div>
                            <div class="document-detail-row"><dt>Date Returned</dt><dd>@if ($isEditingDetails)<input class="document-inline-field" type="date" wire:model.live="documentDetailsForm.date_returned" aria-label="Date Returned">@else{{ $documentRecord->date_returned?->format('F d, Y') ?? 'Not returned' }}@endif</dd></div>
                        @endif
                    </dl>

                    <details class="document-accordion">
                        <summary>Other Details</summary>
                        <dl class="document-detail-list document-secondary-details">
                            <div class="document-detail-row"><dt>File</dt><dd>{{ $latestFilePath ? basename($latestFilePath) : 'No file' }}</dd></div>
                            <div class="document-detail-row"><dt>Uploaded</dt><dd>{{ $documentRecord->created_at?->format('F d, Y') ?? 'Unknown' }}</dd></div>
                            <div class="document-detail-row"><dt>Uploaded By</dt><dd>{{ $documentRecord->user?->name ?? 'Unknown' }}</dd></div>
                            <div class="document-detail-row"><dt>Last Updated</dt><dd>{{ $documentRecord->updated_at?->format('F d, Y') ?? 'Unknown' }}</dd></div>
                            @if ($latestRejection)
                                <div class="document-detail-row document-detail-row-stacked"><dt>Rejection Reason</dt><dd>{{ $latestRejection->reason }}</dd></div>
                            @endif
                        </dl>
                    </details>
                    @endif
                    </div>

                    <section class="document-files-section" x-show="activeTab === 'attachments'">
                        @if (! $softCopyRequest)
                            <div class="document-file-group">
                                <h3>Transmittal / Endorsement</h3>
                                @if ($transmittalFiles->isNotEmpty())
                                    @foreach ($transmittalFiles as $transmittalFile)
                                        @php
                                            $transmittalFileName = basename($transmittalFile['path']);
                                            $transmittalAttachmentId = $transmittalFile['id'];
                                            $transmittalIsSelected = $isTransmittalSelected
                                                && $selectedTransmittalId === $transmittalAttachmentId;
                                            $transmittalPreviewRoute = $transmittalAttachmentId === null
                                                ? route('admin.documents.transmittal.preview', ['document' => $documentRecord->public_id])
                                                : route('admin.documents.transmittal-attachment.preview', ['document' => $documentRecord->public_id, 'attachment' => $transmittalAttachmentId]);
                                            $transmittalDownloadRoute = $transmittalAttachmentId === null
                                                ? route('admin.documents.transmittal.download', ['document' => $documentRecord->public_id])
                                                : route('admin.documents.transmittal-attachment.download', ['document' => $documentRecord->public_id, 'attachment' => $transmittalAttachmentId]);
                                        @endphp
                                    <div class="document-file-row">
                                        <button type="button" wire:click="selectTransmittal({{ $transmittalAttachmentId ?? 'null' }})" wire:loading.attr="disabled" class="document-file-select {{ $transmittalIsSelected ? 'is-selected' : '' }}" title="Preview transmittal">
                                            <span class="document-file-badge {{ $fileBadgeClass($transmittalFileName) }}">{{ strtoupper(pathinfo($transmittalFileName, PATHINFO_EXTENSION)) ?: 'FILE' }}</span>
                                            <span class="document-file-name">{{ $transmittalFileName }}</span>
                                        </button>
                                        <div class="relative shrink-0" x-data="{ menuOpen: false }">
                                            <button type="button" class="document-file-menu-button" aria-label="Transmittal options" aria-haspopup="menu" @click.stop="menuOpen = !menuOpen">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.6" d="M12 6.5h.01M12 12h.01M12 17.5h.01" /></svg>
                                            </button>
                                            <div x-cloak x-show="menuOpen" x-on:click.outside="menuOpen = false" class="document-file-menu" role="menu">
                                                <a href="{{ $transmittalDownloadRoute }}" role="menuitem">Download</a>
                                                <button type="button" role="menuitem" data-print-url="{{ $transmittalPreviewRoute }}" @click="menuOpen = false; printFile($event.currentTarget.dataset.printUrl)">Print</button>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                @else
                                    <p class="document-file-empty">No transmittal uploaded.</p>
                                @endif
                            </div>
                        @endif

                        <div class="document-file-group">
                            <h3>Files Submitted</h3>
                            @forelse ($submittedFiles as $submittedFile)
                                @php
                                    $submittedFileName = $submittedFile->file_path ? basename($submittedFile->file_path) : 'Document';
                                    $submittedFilePreviewRoute = route('admin.document.version.preview', ['document' => $documentRecord->public_id, 'version' => $submittedFile->version_id]);
                                    $submittedFileDownloadRoute = route('admin.document.version.download', ['document' => $documentRecord->public_id, 'version' => $submittedFile->version_id]);
                                @endphp
                                <div wire:key="document-submitted-file-{{ $submittedFile->version_id }}" class="document-file-row">
                                    <button type="button" wire:click="selectVersion({{ $submittedFile->version_id }})" wire:loading.attr="disabled" class="document-file-select {{ $selectedVersionId === $submittedFile->version_id && ! $isTransmittalSelected ? 'is-selected' : '' }}" title="Preview {{ $submittedFileName }}">
                                        <span class="document-file-badge {{ $fileBadgeClass($submittedFileName) }}">{{ strtoupper(pathinfo($submittedFileName, PATHINFO_EXTENSION)) ?: 'FILE' }}</span>
                                        <span class="document-file-name">{{ $submittedFileName }}</span>
                                    </button>
                                    <div class="relative shrink-0" x-data="{ menuOpen: false }">
                                        <button type="button" class="document-file-menu-button" aria-label="Submitted file options" aria-haspopup="menu" @click.stop="menuOpen = !menuOpen">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.6" d="M12 6.5h.01M12 12h.01M12 17.5h.01" /></svg>
                                        </button>
                                        <div x-cloak x-show="menuOpen" x-on:click.outside="menuOpen = false" class="document-file-menu" role="menu">
                                            <a href="{{ $submittedFileDownloadRoute }}" role="menuitem">Download</a>
                                            <button type="button" role="menuitem" data-print-url="{{ $submittedFilePreviewRoute }}" @click="menuOpen = false; printFile($event.currentTarget.dataset.printUrl)">Print</button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="document-file-empty">No files submitted.</p>
                            @endforelse
                        </div>

                        <div class="document-file-group">
                            <div class="document-files-heading document-versions-heading">
                                <h3>Revision</h3>
                                @if (! $softCopyRequest)
                                    <div class="document-version-add-action">
                                        <button
                                            type="button"
                                            wire:click="startAddingVersion"
                                            @disabled(in_array($documentRecord->status, ['pending', 'rejected'], true) || $hasPendingRevision)
                                            class="document-inline-add-button {{ in_array($documentRecord->status, ['pending', 'rejected'], true) || $hasPendingRevision ? 'is-disabled' : '' }}"
                                            title="{{ in_array($documentRecord->status, ['pending', 'rejected'], true) || $hasPendingRevision ? 'Uploading is disabled while this document is awaiting review' : 'Add revision' }}"
                                            aria-label="Add revision"
                                        >
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14M5 12h14" /></svg>
                                        </button>
                                    </div>
                                @endif
                            </div>

                            @if ($isAddingVersion)
                                <div class="document-inline-version-upload">
                                    <form wire:submit="submitVersionUpload">
                                        {{ $this->form }}
                                        <div class="document-inline-upload-actions">
                                            <button type="button" wire:click="cancelAddingVersion" class="document-inline-upload-cancel">Cancel</button>
                                            <button type="submit" wire:loading.attr="disabled" wire:target="submitVersionUpload" class="document-inline-upload-submit">
                                                <span wire:loading.remove wire:target="submitVersionUpload">Upload</span>
                                                <span wire:loading wire:target="submitVersionUpload">Uploading...</span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            @endif

                            @foreach ($versions as $version)
                                @php
                                    $versionFileName = $version->file_path ? basename($version->file_path) : 'Document';
                                    $versionNumber = trim((string) ($version->version_number ?? ''));
                                    $versionBadge = $versionNumber !== ''
                                        ? (str_contains(strtolower($versionNumber), 'version') || str_starts_with(strtolower($versionNumber), 'v') ? $versionNumber : 'v' . $versionNumber)
                                        : 'v' . ($loop->iteration + 1);
                                    $isPendingRevisionVersion = $pendingRevisionVersionId !== null && (int) $pendingRevisionVersionId === (int) $version->version_id;
                                @endphp
                                <div wire:key="document-version-{{ $version->version_id }}" class="document-file-row">
                                    @if ($isPendingRevisionVersion)
                                        <div class="document-file-select">
                                            <span class="document-file-badge {{ $fileBadgeClass($versionFileName) }}">{{ strtoupper(pathinfo($versionFileName, PATHINFO_EXTENSION)) ?: 'FILE' }}</span>
                                            <span class="document-file-name">{{ $versionFileName }}</span>
                                            <span class="document-version-badge">{{ $versionBadge }}</span>
                                        </div>
                                    @else
                                        <button type="button" wire:click="selectVersion({{ $version->version_id }})" wire:loading.attr="disabled" class="document-file-select {{ $selectedVersionId === $version->version_id ? 'is-selected' : '' }}" title="Preview {{ $versionFileName }}">
                                            <span class="document-file-badge {{ $fileBadgeClass($versionFileName) }}">{{ strtoupper(pathinfo($versionFileName, PATHINFO_EXTENSION)) ?: 'FILE' }}</span>
                                            <span class="document-file-name">{{ $versionFileName }}</span>
                                            <span class="document-version-badge">{{ $versionBadge }}</span>
                                        </button>
                                    @endif
                                    <div class="relative shrink-0" x-data="{ menuOpen: false }">
                                        <button type="button" class="document-file-menu-button" aria-label="Revision options" aria-haspopup="menu" @click.stop="menuOpen = !menuOpen">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.6" d="M12 6.5h.01M12 12h.01M12 17.5h.01" /></svg>
                                        </button>
                                        <div x-cloak x-show="menuOpen" x-on:click.outside="menuOpen = false" class="document-file-menu" role="menu">
                                            <a href="{{ route('admin.document.version.download', ['document' => $documentRecord->public_id, 'version' => $version->version_id]) }}" role="menuitem">Download</a>
                                            <button type="button" role="menuitem" data-print-url="{{ route('admin.document.version.preview', ['document' => $documentRecord->public_id, 'version' => $version->version_id]) }}" @click="menuOpen = false; printFile($event.currentTarget.dataset.printUrl)">Print</button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            @if ($showCurrentDocument)
                                @php $currentFileName = basename($latestFilePath); @endphp
                                <div class="document-file-row">
                                    <button type="button" wire:click="selectCurrentDocument" wire:loading.attr="disabled" class="document-file-select {{ $selectedVersionId === null && ! $isTransmittalSelected ? 'is-selected' : '' }}" title="Preview current document">
                                        <span class="document-file-badge {{ $fileBadgeClass($currentFileName) }}">{{ strtoupper(pathinfo($currentFileName, PATHINFO_EXTENSION)) ?: 'FILE' }}</span>
                                        <span class="document-file-name">{{ $currentFileName }}</span>
                                        <span class="document-version-badge">v1</span>
                                    </button>
                                </div>
                            @endif

                            @if ($versions->isEmpty() && ! $showCurrentDocument)
                                <p class="document-file-empty">No document file available.</p>
                            @endif
                        </div>
                    </section>
                </div>

                @if ($documentRecord->status === 'pending')
                    <div class="document-review-actions" x-show="activeTab === 'details'" aria-label="Document review actions">
                        {{ ($this->acceptDocumentAction)(['document' => $documentRecord->document_id]) }}
                        {{ ($this->rejectDocumentAction)(['document' => $documentRecord->document_id]) }}
                    </div>
                @endif
            </section>

            @if (! $softCopyRequest)
            <section class="document-activity-card" x-show="activeTab === 'notes' || activeTab === 'history'" aria-label="Document notes and history">
                <section class="document-notes-panel" x-show="activeTab === 'notes'" aria-label="Notes">
                    @include('filament.pages.document-notes')
                </section>

                <section class="document-history-panel" x-show="activeTab === 'history'" aria-label="Document History">
                    <div class="document-history-list">
                        @forelse ($activityLogs as $log)
                            @php($activityDescription = $this->activityDescription($log))
                            @php($activityActor = $this->activityActorFirstName($log))
                            @php($activityChanges = $this->activityChangeRows($log))
                            <article
                                class="document-history-item"
                                wire:key="document-history-{{ $log->log_id }}"
                            >
                                <div class="document-history-avatar">
                                    @if ($log->user && $log->user->getProfilePhotoUrl())
                                        <img src="{{ $log->user->getProfilePhotoUrl() }}" alt="{{ $log->user->name ?? 'User' }}" referrerpolicy="no-referrer">
                                    @else
                                        <span>{{ strtoupper(substr($log->user->name ?? 'U', 0, 1)) }}</span>
                                    @endif
                                </div>
                                <div class="document-history-copy">
                                    <div class="document-history-title-line">
                                        <h3>{{ $log->action_type ?? 'Document updated' }}</h3>
                                        <time>{{ $log->created_at?->format('m/d/Y | g:i A') }}</time>
                                    </div>
                                    @if ($activityChanges !== [])
                                        <details class="document-history-changes">
                                            <summary>
                                                <p><strong class="document-history-actor">{{ $activityActor }}</strong> {{ $activityDescription }}</p>
                                                <span>View changes</span>
                                            </summary>
                                            <div class="document-history-change-list">
                                                @foreach ($activityChanges as $change)
                                                    <div class="document-history-change">
                                                        <h4>{{ $change['label'] }}</h4>
                                                        <div class="document-history-change-value">
                                                            <span class="document-history-change-tag document-history-before">Before</span>
                                                            <span>{{ $change['before'] }}</span>
                                                        </div>
                                                        <div class="document-history-change-value">
                                                            <span class="document-history-change-tag document-history-after">After</span>
                                                            <span>{{ $change['after'] }}</span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </details>
                                    @elseif (filled($activityDescription))
                                        <p><strong class="document-history-actor">{{ $activityActor }}</strong> {{ $activityDescription }}</p>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <p class="document-history-empty">No document changes recorded.</p>
                        @endforelse
                    </div>
                </section>
            </section>
            @endif
                </div>
            </aside>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
        .fi-page-header, .fi-page-heading { display: none !important; }
        .fi-page { gap: 0 !important; }
        .fi-page-content { width: 100% !important; max-width: none !important; gap: 0 !important; padding-bottom: 0 !important; }
        .document-version-upload-files .filepond--list-scroller { top: 0 !important; transform: translate3d(0, 0, 0) !important; margin-top: 0 !important; }
        .document-version-upload-files .filepond--drop-label { top: auto !important; bottom: 0 !important; }
        .document-version-upload-files .filepond--item-panel { background-color: #e5e7eb !important; border: 1px solid #9ca3af !important; }
        .document-version-upload-files .filepond--file-info-main,
        .document-version-upload-files .filepond--file-info-sub,
        .document-version-upload-files .filepond--file-status-main,
        .document-version-upload-files .filepond--file-status-sub { color: #374151 !important; }
        /* Keep invalid-file controls and validation text inside the upload row. */
        .document-version-upload-files .filepond--item[data-filepond-item-state*="invalid"] .filepond--action-remove-item,
        .document-version-upload-files .filepond--item[data-filepond-item-state*="error"] .filepond--action-remove-item {
            left: auto !important;
            top: 50% !important;
            right: 0.5625em !important;
            width: 1.8rem !important;
            height: 1.8rem !important;
            margin-top: -0.9rem !important;
            transform: none !important;
            z-index: 4 !important;
        }
        .document-version-upload-files .filepond--item[data-filepond-item-state*="invalid"] .filepond--file,
        .document-version-upload-files .filepond--item[data-filepond-item-state*="error"] .filepond--file {
            min-height: 4.35rem !important;
            height: auto !important;
            align-items: flex-start !important;
            padding-right: 4.4rem !important;
        }
        .document-version-upload-files .filepond--item[data-filepond-item-state*="invalid"] .filepond--file-info,
        .document-version-upload-files .filepond--item[data-filepond-item-state*="error"] .filepond--file-info {
            min-width: 0 !important;
            flex: 1 1 52% !important;
            margin-left: 0 !important;
            margin-right: 0.5em !important;
        }
        .document-version-upload-files .filepond--item[data-filepond-item-state*="invalid"] .filepond--file-status,
        .document-version-upload-files .filepond--item[data-filepond-item-state*="error"] .filepond--file-status {
            min-width: 0 !important;
            max-width: 42% !important;
            flex: 0 1 42% !important;
            margin-left: auto !important;
            margin-right: 0.35rem !important;
            padding-right: 0 !important;
            overflow: visible !important;
            text-align: left !important;
        }
        .document-version-upload-files .filepond--item[data-filepond-item-state*="invalid"] .filepond--file-status-main,
        .document-version-upload-files .filepond--item[data-filepond-item-state*="invalid"] .filepond--file-status-sub,
        .document-version-upload-files .filepond--item[data-filepond-item-state*="error"] .filepond--file-status-main,
        .document-version-upload-files .filepond--item[data-filepond-item-state*="error"] .filepond--file-status-sub {
            display: block !important;
            max-width: 100% !important;
            overflow: visible !important;
            text-overflow: clip !important;
            white-space: normal !important;
            overflow-wrap: anywhere !important;
            word-break: break-word !important;
            line-height: 1.2 !important;
        }
        .document-version-upload-files .filepond--item[data-filepond-item-state*="invalid"] .filepond--file-status-main,
        .document-version-upload-files .filepond--item[data-filepond-item-state*="error"] .filepond--file-status-main {
            font-weight: 700 !important;
        }
        .document-version-upload-files .filepond--item[data-filepond-item-state*="invalid"] .filepond--file-status-sub,
        .document-version-upload-files .filepond--item[data-filepond-item-state*="error"] .filepond--file-status-sub {
            margin-top: 0.15rem !important;
            font-size: 0.7rem !important;
        }
        .document-version-upload-files .filepond--item[data-filepond-item-state*="invalid"] .filepond--file,
        .document-version-upload-files .filepond--item[data-filepond-item-state*="error"] .filepond--file { padding-left: 0.25rem !important; }
        .document-version-upload-files .filepond--item[data-filepond-item-state="processing-complete"] .filepond--item-panel { background-color: #dcfce7 !important; border-color: #166534 !important; }
        .document-version-upload-files .filepond--item[data-filepond-item-state="processing-complete"] .filepond--file-info-main,
        .document-version-upload-files .filepond--item[data-filepond-item-state="processing-complete"] .filepond--file-info-sub,
        .document-version-upload-files .filepond--item[data-filepond-item-state="processing-complete"] .filepond--file-status-main,
        .document-version-upload-files .filepond--item[data-filepond-item-state="processing-complete"] .filepond--file-status-sub { color: #14532d !important; }
        .document-viewer-page { position: fixed; z-index: 10; inset: 4rem 0 0 var(--collapsed-sidebar-width); padding: 1rem; overflow: hidden; background: #f3f4f6; }
        .fi-body-has-topbar:has(#fi-main-sidebar.fi-sidebar-open) .document-viewer-page { inset-inline-start: var(--sidebar-width); }
        .document-viewer-layout { display: grid; width: 100%; height: 100%; min-height: 0; grid-template-columns: minmax(0, 1.35fr) minmax(360px, 0.85fr); gap: 0; overflow: hidden; border: 1px solid #9ca3af; border-radius: 18px; background: #fff; }
        .document-viewer-layout.document-request-viewer-layout { grid-template-columns: minmax(0, 1.35fr) minmax(360px, 0.85fr); }
        .document-panel-card { min-width: 0; min-height: 0; overflow: hidden; border: 0; border-radius: 0; background: #fff; }
        .document-side-panel { display: flex; height: 100%; min-height: 0; flex-direction: column; overflow: hidden; border-left: 1px solid #d1d5db; }
        .document-side-tabs { display: grid; height: 64px; min-height: 64px; flex-shrink: 0; grid-template-columns: repeat(4, minmax(0, 1fr)); border-bottom: 1px solid #d1d5db; background: #fff; box-sizing: border-box; }
        .document-side-tabs-two { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .document-side-tabs button { height: 100%; min-height: 0; border: 0; border-bottom: 2px solid transparent; background: #fff; padding: 0.7rem 0.45rem; color: #737b8c; font-size: 0.78rem; font-weight: 650; cursor: pointer; }
        .document-side-tabs button:hover { background: #f8fafc; color: #4f46e5; }
        .document-side-tabs button.is-active { border-bottom-color: #6366f1; color: #4f46e5; }
        .document-side-content { display: flex; min-height: 0; flex: 1; overflow: hidden; }
        .document-side-content > [x-show] { min-width: 0; min-height: 0; width: 100%; height: 100%; }
        .document-side-panel .document-details-card, .document-side-panel .document-activity-card { height: 100%; min-height: 0; border: 0; border-radius: 0; }
        .document-side-panel .document-details-card { position: relative; display: flex; flex-direction: column; }
        .document-side-panel .document-activity-card { display: flex; flex-direction: column; padding: 0.75rem; }
        .document-side-panel .document-notes-panel, .document-side-panel .document-history-panel { min-height: 0; height: 100%; max-height: none; flex: 1; }
        .document-details-card { display: flex; flex-direction: column; }
        .document-card-heading { position: absolute; top: 0; right: 0; z-index: 2; display: flex; min-height: 0; align-items: center; justify-content: space-between; gap: 0.75rem; border-bottom: 0; padding: 0.35rem 0.55rem; }
        .document-preview-heading { display: flex; min-height: 64px; align-items: center; justify-content: space-between; gap: 0.75rem; border-bottom: 1px solid #d1d5db; padding: 0.65rem 1rem; }
        .document-card-heading .document-edit-controls, .document-notes-panel > div:first-child .add-note-button { margin-inline-start: auto; }
        .document-card-heading h1, .document-preview-heading h2, .document-history-heading h2 { margin: 0; color: #111827; font-size: 1.08rem; font-weight: 650; line-height: 1.3; }
        .document-edit-controls { display: flex; flex-shrink: 0; align-items: center; gap: 0.35rem; white-space: nowrap; }
        .document-edit-button, .document-cancel-button { flex: 0 0 auto; border: 0; border-radius: 0.45rem; background: transparent; padding: 0.45rem 0.55rem; color: #6366F1; font-size: 0.76rem; font-weight: 650; line-height: 1.2; white-space: nowrap; cursor: pointer; }
        .document-edit-icon-button { display: inline-flex; width: 2rem; height: 2rem; align-items: center; justify-content: center; padding: 0.35rem; color: #1f2937; }
        .document-edit-icon-button svg { width: 1.2rem; height: 1.2rem; }
        .document-edit-button:hover, .document-cancel-button:hover { background: #f3f4f6; }
        .document-edit-button:disabled { cursor: not-allowed; opacity: 0.45; }
        .document-cancel-button { color: #6b7280; }
        .document-note-action-button { background: #6366F1; }
        .document-note-action-button:hover:not(:disabled) { background: #5558E8; }
        .document-files-heading .fi-ac { display: flex; width: 2rem; height: 2rem; flex: 0 0 2rem; align-items: center; justify-content: center; }
        .document-version-add-action { position: relative; left: 0.25rem; flex: 0 0 2rem; }
        .document-inline-add-button { display: inline-flex; width: 2rem; height: 2rem; align-items: center; justify-content: center; border: 0; border-radius: 999px; background: transparent; color: #111827; cursor: pointer; }
        .document-inline-add-button:hover:not(:disabled) { background: #f3f4f6; }
        .document-inline-add-button.is-disabled, .document-inline-add-button:disabled { cursor: not-allowed; opacity: 0.45; }
        .document-inline-add-button svg { width: 1.2rem; height: 1.2rem; }
        .document-inline-version-upload { margin: 0.25rem 0 0.9rem; border: 1px solid #e5e7eb; border-radius: 0.7rem; background: #f8fafc; padding: 0.85rem; }
        .document-inline-version-upload .document-version-upload-files { margin: 0; }
        .document-inline-upload-actions { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 0.8rem; }
        .document-inline-upload-cancel, .document-inline-upload-submit { border-radius: 0.45rem; padding: 0.45rem 0.8rem; font-size: 0.75rem; font-weight: 650; line-height: 1.2; cursor: pointer; }
        .document-inline-upload-cancel { border: 1px solid #d1d5db; background: #fff; color: #4b5563; }
        .document-inline-upload-cancel:hover { background: #f3f4f6; }
        .document-inline-upload-submit { border: 1px solid #6366f1; background: #6366f1; color: #fff; }
        .document-inline-upload-submit:hover:not(:disabled) { background: #5558e8; }
        .document-inline-upload-submit:disabled { cursor: not-allowed; opacity: 0.6; }
        .document-versions-heading .fi-ac .fi-icon-btn { display: inline-flex !important; width: 2rem !important; height: 2rem !important; align-items: center; justify-content: center; border: 0 !important; border-radius: 999px !important; background: #fff !important; color: #111827 !important; padding: 0.3rem !important; box-shadow: none !important; }
        .document-versions-heading .fi-ac .fi-icon-btn:hover { background: #f9fafb !important; }
        .document-versions-heading .fi-ac .fi-icon-btn svg { width: 1.2rem !important; height: 1.2rem !important; }
        .document-edit-error { margin: 0.5rem 0; border-radius: 0.4rem; background: #fef2f2; padding: 0.5rem 0.65rem; color: #b91c1c; font-size: 0.75rem; }
        .document-card-heading .document-edit-icon-button { width: 2rem !important; height: 2rem !important; border: 0 !important; border-radius: 0.25rem !important; color: #111827 !important; }
        .document-details-scroll { min-height: 0; flex: 1; overflow-y: auto; padding: 2.75rem 1.1rem 1.25rem; }
        .document-detail-list { display: grid; gap: 0; margin: 0; padding: 0.25rem 0; }
        .document-detail-row { display: grid; grid-template-columns: minmax(115px, 0.82fr) minmax(0, 1.18fr); align-items: start; gap: 0.65rem; padding: 0.48rem 0; font-size: 0.84rem; line-height: 1.35; }
        .document-detail-row dt { color: #737b8c; font-weight: 500; }
        .document-detail-row dd { min-width: 0; margin: 0; overflow-wrap: anywhere; color: #1f2937; font-weight: 600; }
        .document-inline-field { display: block; width: 100%; min-width: 0; border: 1px solid #d1d5db; border-radius: 0.4rem; background: #fff; padding: 0.35rem 0.45rem; color: #1f2937; font-size: 0.76rem; font-weight: 500; line-height: 1.35; }
        .document-inline-field:focus { border-color: #6366f1; outline: 2px solid rgb(99 102 241 / 15%); }
        .document-inline-readonly { background: #f9fafb; color: #6b7280; cursor: not-allowed; }
        .document-inline-select { -webkit-appearance: none; appearance: none; padding-right: 2.25rem; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='none' stroke='%236b7280' stroke-width='1.75'%3E%3Cpath d='m6 8 4 4 4-4' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E"); background-position: right 0.8rem center; background-repeat: no-repeat; background-size: 0.85rem; }
        .document-type-field { min-width: 0; }
        .document-inline-textarea { resize: vertical; }
        .document-detail-row-stacked { grid-template-columns: 1fr; gap: 0.25rem; }
        .document-table-badge { display: inline-flex; align-items: center; border: 1px solid color-mix(in srgb, currentColor 18%, transparent); border-radius: 0.375rem; padding: 0.125rem 0.5rem; font-size: 0.6875rem; font-weight: 600; line-height: 1rem; white-space: nowrap; }
        .document-status-badge { border-color: color-mix(in srgb, currentColor 18%, transparent); }
        .document-action-badge { border-color: color-mix(in srgb, var(--badge-color) 18%, transparent); background-color: color-mix(in srgb, var(--badge-color) 14%, white); color: var(--badge-color); }
        .document-accordion { border: 0; }
        .document-accordion summary { display: flex; cursor: pointer; list-style: none; align-items: center; justify-content: space-between; padding: 0.8rem 0; color: #111827; font-size: 1rem; font-weight: 650; }
        .document-accordion summary::-webkit-details-marker { display: none; }
        .document-accordion summary::after { display: block; width: 2rem; height: 2rem; flex: 0 0 2rem; margin-right: 0.4rem; background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='none' stroke='%23111827' stroke-width='2.5'%3E%3Cpath d='m5 7.5 5 5 5-5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") center / 1.05rem 1.05rem no-repeat; content: ''; transition: transform 150ms ease; }
        .document-accordion[open] summary::after { transform: rotate(180deg); }
        .document-secondary-details { padding: 0 0 0.8rem; }
        .document-files-section { padding: 0.35rem 0.15rem 0.5rem; }
        .document-files-heading { display: flex; align-items: center; justify-content: space-between; gap: 0.5rem; padding: 0 0 0.7rem; }
        .document-files-heading h2 { margin: 0; color: #111827; font-size: 1rem; font-weight: 650; }
        .document-file-group { padding: 0.45rem 0 1rem; }
        .document-file-group h3 { margin: 0 0 0.55rem; padding: 0 0.1rem; color: #374151; font-size: 0.9rem; font-weight: 600; }
        .document-versions-heading { padding-bottom: 0.2rem; }
        .document-versions-heading h3 { margin: 0; }
        .document-file-row { display: flex; min-width: 0; align-items: center; gap: 0.55rem; border-bottom: 1px solid #f3f4f6; padding: 0.15rem 0; }
        .document-file-select { display: flex; min-width: 0; flex: 1; align-items: center; gap: 0.7rem; border: 0; border-radius: 0.45rem; background: transparent; padding: 0.65rem 0.35rem; text-align: left; }
        button.document-file-select { cursor: pointer; }
        .document-file-select:hover, .document-file-select.is-selected { background: #f9fafb; }
        .document-file-badge { display: inline-flex; width: 2rem; height: 2.1rem; flex-shrink: 0; align-items: center; justify-content: center; border: 1px solid transparent; border-radius: 0.35rem; font-size: 0.55rem; font-weight: 700; }
        .document-file-badge-pdf { border-color: #fecaca; background: #fee2e2; color: #ef3340; }
        .document-file-badge-docx { border-color: #bfdbfe; background: #dbeafe; color: #2563eb; }
        .document-file-badge-default { border-color: #cbd5e1; background: #e2e8f0; color: #64748b; }
        .document-file-name { min-width: 0; flex: 1; overflow: hidden; color: #737b8c; font-size: 0.8rem; font-weight: 550; text-overflow: ellipsis; white-space: nowrap; }
        .document-version-badge { display: inline-flex; flex-shrink: 0; align-items: center; border-radius: 999px; background: #f3f4f6; padding: 0.2rem 0.5rem; color: #4b5563; font-size: 0.68rem; font-weight: 600; }
        .document-file-menu-button { display: inline-flex; width: 2rem; height: 2rem; flex-shrink: 0; align-items: center; justify-content: center; border: 0; border-radius: 999px; background: transparent; color: #111827; cursor: pointer; }
        .document-file-menu-button:hover { background: #f3f4f6; }
        .document-file-menu-button svg { width: 1.2rem; height: 1.2rem; }
        .document-file-menu { position: absolute; top: calc(100% + 0.25rem); right: 0; z-index: 30; display: flex; width: 6rem; flex-direction: column; gap: 0.25rem; border: 1px solid #e5e7eb; border-radius: 0.375rem; background: #fff; padding: 0.25rem; box-shadow: 0 8px 18px rgb(15 23 42 / 0.12); }
        .document-file-menu a, .document-file-menu button { display: flex; width: 100%; align-items: center; border: 0; border-radius: 0.375rem; background: transparent; padding: 0.5rem 0.75rem; color: #374151; font-size: 0.75rem; line-height: 1rem; text-align: left; text-decoration: none; cursor: pointer; }
        .document-file-menu a:hover, .document-file-menu button:hover { background: #f3f4f6; }
        .document-file-empty { margin: 0; padding: 0.45rem 0.2rem; color: #858b98; font-size: 0.75rem; }
        .document-review-actions { display: flex; flex-shrink: 0; gap: 0.6rem; border-top: 1px solid #e5e7eb; background: #fff; padding: 0.75rem 1rem; }
        .document-review-actions > * { flex: 1; }
        .document-review-actions button { width: 100%; justify-content: center; }
        .document-preview-card { display: flex; height: 100%; flex-direction: column; }
        .document-preview-heading { height: 64px; min-height: 64px; justify-content: flex-start; padding-inline: 1rem; box-sizing: border-box; }
        .document-preview-heading > div { min-width: 0; flex: 1; padding-top: 0; }
        .document-preview-file-meta { display: flex; min-width: 0; align-items: center; gap: 0.7rem; }
        .document-preview-file-icon { display: inline-flex; width: 2.5rem; height: 2.7rem; flex: 0 0 2.5rem; align-items: center; justify-content: center; border-radius: 0.45rem; }
        .document-preview-file-icon svg { width: 1.45rem; height: 1.45rem; }
        .document-preview-file-icon-pdf { background: #fee2e2; color: #dc2626; }
        .document-preview-file-icon-docx { background: #dbeafe; color: #2563eb; }
        .document-preview-file-icon-default { background: #e5e7eb; color: #475569; }
        .document-preview-file-meta > div { min-width: 0; }
        .document-preview-heading h2 { font-size: 1.08rem; }
        .document-preview-heading p { margin: 0.2rem 0 0; overflow: hidden; color: #8b8d94; font-size: 0.875rem; font-weight: 550; text-overflow: ellipsis; white-space: nowrap; }
        .document-preview-frame { min-height: 0; flex: 1; overflow: hidden; background: #fff; }
        .document-preview-frame iframe { display: block; width: 100%; height: 100%; border: 0; background: white; }
        .document-preview-empty { display: flex; height: 100%; min-height: 300px; flex-direction: column; align-items: center; justify-content: center; gap: 0.45rem; color: #858b98; }
        .document-preview-empty svg { width: 2.5rem; height: 2.5rem; }
        .document-preview-empty p { margin: 0; color: #374151; font-size: 0.95rem; font-weight: 600; }
        .document-preview-empty span { font-size: 0.8rem; }
        .document-activity-card { display: flex; flex-direction: column; padding: 0.75rem; }
        .document-notes-panel { min-height: 0; height: 40%; max-height: 40%; flex: 0 0 40%; overflow-y: auto; }
        .document-notes-panel > div:first-child { padding: 0.15rem 0.75rem 0.7rem; }
        .document-notes-panel > div:first-child p { margin: 0; color: #111827; font-size: 1.08rem; font-weight: 650; letter-spacing: 0; text-transform: none; }
        .document-note-author, .document-note-content, .document-note-empty { font-size: 0.84rem !important; }
        .document-note-timestamp { font-size: 0.72rem !important; }
        .document-notes-panel .add-note-button { display: inline-flex; width: 2rem !important; height: 2rem !important; align-items: center; justify-content: center; border: 0 !important; border-radius: 999px !important; background: white !important; color: #111827 !important; padding: 0.3rem !important; box-shadow: none !important; }
        .document-notes-panel .add-note-button:hover { background: #f9fafb !important; }
        .document-notes-panel .add-note-button svg { width: 1.2rem; height: 1.2rem; }
        .document-notes-panel .document-note-actions { margin-right: -0.65rem; }
        .document-notes-panel > .p-3 { padding: 0 !important; }
        .document-notes-panel > .p-3 > div { margin-bottom: 0.5rem; }
        .document-history-panel { display: flex; min-height: 0; flex: 1; flex-direction: column; padding-top: 0.7rem; }
        .document-history-heading { display: flex; flex-shrink: 0; align-items: center; justify-content: space-between; gap: 0.5rem; padding: 0 0.1rem 0.7rem; }
        .document-history-list { min-height: 0; flex: 1; overflow-y: auto; padding: 0.15rem 0.1rem; }
        .document-history-item { position: relative; display: grid; grid-template-columns: 3rem minmax(0, 1fr); gap: 0.6rem; min-height: 5.7rem; }
        .document-history-item:not(:last-child)::after { position: absolute; top: 3.25rem; bottom: 0.1rem; left: 1.45rem; width: 1px; background: #cbd0d8; content: ''; }
        .document-history-avatar { z-index: 1; display: flex; width: 2.6rem; height: 2.6rem; align-items: center; justify-content: center; overflow: hidden; border-radius: 50%; background: #f3f4f6; color: #525866; }
        .document-history-avatar img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
        .document-history-avatar span { font-size: 0.9rem; font-weight: 700; }
        .document-history-copy { min-width: 0; padding: 0.1rem 0 0.8rem; }
        .document-history-title-line { display: flex; align-items: baseline; justify-content: space-between; gap: 0.45rem; border-bottom: 1px solid #111827; padding-bottom: 0.25rem; }
        .document-history-title-line h3 { min-width: 0; margin: 0; color: #111827; font-size: 0.84rem; font-weight: 650; line-height: 1.35; }
        .document-history-title-line time { flex-shrink: 0; color: #4b5563; font-size: 0.72rem; font-weight: 600; white-space: nowrap; }
        .document-history-copy p { margin: 0.35rem 0 0; color: #4b5563; font-size: 0.84rem; line-height: 1.4; white-space: pre-line; overflow-wrap: anywhere; }
        .document-history-actor { color: #1f2937; font-weight: 700; }
        .document-history-changes { margin-top: 0.35rem; }
        .document-history-changes summary { display: flex; cursor: pointer; list-style: none; align-items: center; gap: 0.5rem; color: #4f46e5; font-size: 0.76rem; font-weight: 650; line-height: 1.4; }
        .document-history-changes summary p { min-width: 0; flex: 1; margin: 0; color: #4b5563; font-size: 0.84rem; font-weight: 400; white-space: pre-line; overflow-wrap: anywhere; }
        .document-history-changes summary > span { flex: 0 0 auto; white-space: nowrap; }
        .document-history-changes summary::-webkit-details-marker { display: none; }
        .document-history-changes summary::after { width: 0.42rem; height: 0.42rem; border-right: 1.5px solid currentColor; border-bottom: 1.5px solid currentColor; content: ''; transform: rotate(45deg) translateY(-0.1rem); transition: transform 150ms ease; }
        .document-history-changes[open] summary::after { transform: rotate(225deg) translate(-0.05rem, -0.05rem); }
        .document-history-change-list { display: grid; width: 100%; max-height: min(45vh, 20rem); gap: 0.4rem; overflow-y: auto; margin: 0.4rem 0 0; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 0.65rem; background: #ffffff; }
        .document-history-change { display: grid; gap: 0.3rem; padding: 0.45rem 0.55rem; border: 1px solid #e5e7eb; border-radius: 0.55rem; background: #f8fafc; }
        .document-history-change h4 { margin: 0; color: #374151; font-size: 0.78rem; font-weight: 700; line-height: 1.3; }
        .document-history-change-value { display: grid; grid-template-columns: 3.25rem minmax(0, 1fr); align-items: start; gap: 0.4rem; color: #374151; font-size: 0.78rem; line-height: 1.35; overflow-wrap: anywhere; }
        .document-history-change-tag { width: fit-content; padding: 0.08rem 0.3rem; border-radius: 999px; font-size: 0.6rem; font-weight: 700; line-height: 1.35; }
        .document-history-before { background: #fee2e2; color: #b91c1c; }
        .document-history-after { background: #e0e7ff; color: #4338ca; }
        .dark .document-history-actor { color: #f9fafb; }
        .dark .document-history-changes summary { color: #a5b4fc; }
        .dark .document-history-change-list { border-color: #374151; background: #111827; }
        .dark .document-history-change { border-color: #374151; background: #1f2937; }
        .dark .document-history-change h4, .dark .document-history-change-value { color: #e5e7eb; }
        .document-history-empty { padding: 1rem 0.25rem; color: #858b98; font-size: 0.8rem; text-align: center; }
        @media (max-width: 1200px) {
            .document-viewer-layout { grid-template-columns: minmax(0, 1.2fr) minmax(320px, 0.85fr); }
            .document-viewer-page { padding: 0.7rem; }
            .document-detail-row { grid-template-columns: minmax(95px, 0.8fr) minmax(0, 1.2fr); gap: 0.4rem; }
        }
        @media (max-width: 900px) {
            .document-viewer-page { position: static; height: auto; min-height: calc(100dvh - 4rem); overflow: visible; padding: 0.75rem; }
            .document-viewer-layout { height: auto; grid-template-columns: minmax(0, 1fr); }
            .document-preview-card, .document-side-panel { height: auto; }
            .document-panel-card { min-height: 30rem; }
            .document-details-card { min-height: 38rem; }
            .document-preview-card { min-height: 70dvh; }
            .document-activity-card { min-height: 36rem; }
            .document-side-panel { min-height: 36rem; }
            .document-side-panel { border-top: 1px solid #d1d5db; border-left: 0; }
            .document-side-panel .document-details-card, .document-side-panel .document-activity-card { min-height: 0; }
            .document-notes-panel { height: 45%; max-height: 45%; flex-basis: 45%; }
            .document-history-panel { min-height: 18rem; }
        }
        @media (max-width: 560px) {
            .document-viewer-page { padding: 0.5rem; }
            .document-viewer-layout { gap: 0; }
            .document-viewer-layout { border-radius: 13px; }
            .document-panel-card { border-radius: 0; }
            .document-detail-row { font-size: 0.78rem; }
            .document-preview-heading { padding-inline: 0.9rem; }
            .document-preview-heading p { font-size: 0.72rem; }
            .document-history-title-line { align-items: flex-start; flex-direction: column; gap: 0.15rem; }
        }
    </style>

    <script>
        (() => {
            if (window.__lexTrackDocumentUploadMessagesInitialized) {
                return;
            }

            window.__lexTrackDocumentUploadMessagesInitialized = true;

            const uploadSelector = '.document-version-upload-files';
            const observedRoots = new WeakSet();

            const replaceInvalidTypeMessage = (root) => {
                root.querySelectorAll('.filepond--item[data-filepond-item-state*="invalid"], .filepond--item[data-filepond-item-state*="error"]').forEach((item) => {
                    const statusMain = item.querySelector('.filepond--file-status-main');
                    const statusSub = item.querySelector('.filepond--file-status-sub');

                    if (!statusMain || !statusSub || !/expects/i.test(statusSub.textContent || '')) {
                        return;
                    }

                    statusMain.textContent = 'Invalid file';
                    statusSub.textContent = 'Only PDF or DOCX files are accepted.';
                });
            };

            const observeUpload = (root) => {
                if (observedRoots.has(root)) {
                    return;
                }

                observedRoots.add(root);

                new MutationObserver(() => replaceInvalidTypeMessage(root)).observe(root, {
                    attributes: true,
                    attributeFilter: ['data-filepond-item-state'],
                    characterData: true,
                    childList: true,
                    subtree: true,
                });

                replaceInvalidTypeMessage(root);
            };

            const scanForUploads = () => {
                document.querySelectorAll(uploadSelector).forEach(observeUpload);
            };

            scanForUploads();

            if (document.body) {
                new MutationObserver(scanForUploads).observe(document.body, {
                    childList: true,
                    subtree: true,
                });
            }
        })();
    </script>

    <x-filament-actions::modals />
</x-filament-panels::page>
