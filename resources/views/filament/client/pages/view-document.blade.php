<x-filament-panels::page>
    @php
        $latestVersion = $documentRecord->latestVersion;
        $latestFilePath = $latestVersion?->file_path;
        $documentSubject = $documentRecord->particulars ?: $documentRecord->description;
        $displayedFileName = filled($latestFilePath)
            ? basename((string) $latestFilePath)
            : 'Preview unavailable';
        $displayedFileExtension = strtolower((string) pathinfo($displayedFileName, PATHINFO_EXTENSION));
        $displayedFileTypeLabel = match ($displayedFileExtension) {
            'pdf' => 'PDF Document',
            'docx' => 'DOCX Document',
            'doc' => 'DOC Document',
            default => $displayedFileExtension !== ''
                ? strtoupper($displayedFileExtension) . ' Document'
                : 'Document',
        };
        $displayedFileIconClass = match ($displayedFileExtension) {
            'pdf' => 'client-document-file-icon-pdf',
            'doc', 'docx' => 'client-document-file-icon-docx',
            default => 'client-document-file-icon-default',
        };
        $versionNumber = trim((string) ($latestVersion?->version_number ?? '1'));
        $versionBadge = $versionNumber !== ''
            ? (str_contains(strtolower($versionNumber), 'version') || str_starts_with(strtolower($versionNumber), 'v')
                ? $versionNumber
                : 'v' . $versionNumber)
            : 'v1';
        $isSoftCopyRequest = $requestRecord?->copy_type === 'soft_copy';
        $statusValue = $documentRecord->status;
        $statusLabel = blank($statusValue)
            ? '—'
            : match (strtolower((string) $statusValue)) {
                'archived' => 'Completed',
                'outgoing' => 'In Progress',
                'accepted' => 'Accepted',
                default => ucwords(str_replace('_', ' ', (string) $statusValue)),
            };
        $statusBadgeColor = match (strtolower((string) $statusValue)) {
            'pending', 'for filing' => '#f59e0b',
            'accepted', 'completed', 'archived' => '#16a34a',
            'rejected' => '#dc2626',
            'active', 'in_progress', 'outgoing' => '#2563eb',
            default => '#6b7280',
        };
        $rejectionReason = $documentRecord->rejections->first()?->reason
            ?? $documentRecord->rejection_reason;
        $fileBadgeClass = fn (string $fileName): string => match (strtolower((string) pathinfo($fileName, PATHINFO_EXTENSION))) {
            'pdf' => 'client-document-file-badge-pdf',
            'doc', 'docx' => 'client-document-file-badge-docx',
            default => 'client-document-file-badge-default',
        };
        $fileTypeLabel = fn (string $fileName): string => match (strtolower((string) pathinfo($fileName, PATHINFO_EXTENSION))) {
            'pdf' => 'PDF Document',
            'docx' => 'DOCX Document',
            'doc' => 'DOC Document',
            default => pathinfo($fileName, PATHINFO_EXTENSION) !== ''
                ? strtoupper((string) pathinfo($fileName, PATHINFO_EXTENSION)) . ' Document'
                : 'Document',
        };
        $fileIconClass = fn (string $fileName): string => match (strtolower((string) pathinfo($fileName, PATHINFO_EXTENSION))) {
            'pdf' => 'client-document-file-icon-pdf',
            'doc', 'docx' => 'client-document-file-icon-docx',
            default => 'client-document-file-icon-default',
        };
        $transmittalFiles = collect();
        foreach ($documentRecord->transmittalAttachments as $attachment) {
            if (filled($attachment->file_path)) {
                $transmittalFiles->push([
                    'id' => $attachment->transmittal_id,
                    'path' => (string) $attachment->file_path,
                ]);
            }
        }
        if ($transmittalFiles->isEmpty() && filled($documentRecord->transmittal)) {
            $transmittalFiles->push([
                'id' => null,
                'path' => (string) $documentRecord->transmittal,
            ]);
        }
        $allVersions = $documentRecord->versions;
        $submittedFiles = $allVersions
            ->filter(fn ($version): bool => $version->source === 'client')
            ->sortByDesc('created_at')
            ->values();
        $latestAdminRevision = $allVersions
            ->first(fn ($version): bool => $version->source === 'admin');
        $versions = $latestAdminRevision ? collect([$latestAdminRevision]) : collect();
    @endphp

    <div
        class="client-document-view-page"
        x-data="{
            activeTab: 'details',
            previewUrl: @js($previewUrl),
            previewFileName: @js($displayedFileName),
            previewTypeLabel: @js($displayedFileTypeLabel),
            previewExtension: @js($displayedFileExtension),
            previewIconClass: @js($displayedFileIconClass),
            previewVersionBadge: @js($versionBadge),
            selectFile(url, fileName, typeLabel, extension, iconClass, versionBadge = '') {
                this.previewUrl = url;
                this.previewFileName = fileName;
                this.previewTypeLabel = typeLabel;
                this.previewExtension = extension;
                this.previewIconClass = iconClass;
                this.previewVersionBadge = versionBadge;
            },
            printFile(url) {
                const printWindow = window.open(url, '_blank');
                if (!printWindow) return;
                printWindow.addEventListener('load', () => {
                    printWindow.focus();
                    printWindow.print();
                }, { once: true });
            }
        }"
    >
        <div class="client-document-viewer-layout">
            <section class="client-document-preview-panel" aria-label="Document preview">
                <div class="client-document-preview-heading">
                    <div class="client-document-preview-file-meta">
                        <a
                            href="{{ $returnPage === 'dashboard'
                                ? \App\Filament\Client\Pages\Dashboard::getUrl()
                                : \App\Filament\Client\Pages\Documents::getUrl(['tab' => $returnTab]) }}"
                            class="client-document-back-button"
                            title="Back"
                            aria-label="Back"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                            </svg>
                        </a>
                        <span class="client-document-file-icon" :class="previewIconClass" :title="previewTypeLabel" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3.5h7l4 4V20a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4.5a1 1 0 0 1 1-1Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 3.5V8h4" />
                            </svg>
                        </span>
                        <div class="client-document-preview-file-copy">
                            <h2 x-text="previewFileName" :title="previewFileName"></h2>
                            <p x-text="previewTypeLabel"></p>
                        </div>
                    </div>

                    <div class="client-document-preview-actions">
                        <span x-show="previewVersionBadge" x-text="previewVersionBadge" class="client-document-version-badge"></span>
                    </div>
                </div>

                <div class="client-document-preview-frame">
                    <template x-if="previewUrl && ['pdf', 'doc', 'docx'].includes(previewExtension)">
                        <iframe :src="`${previewUrl}#toolbar=0`" title="Document Preview" loading="eager"></iframe>
                    </template>
                    <template x-if="previewUrl && ['jpg', 'jpeg', 'png', 'webp'].includes(previewExtension)">
                        <div class="client-document-image-preview">
                            <img :src="previewUrl" alt="Document Preview">
                        </div>
                    </template>
                    <template x-if="previewUrl && !['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'webp'].includes(previewExtension)">
                        <div class="client-document-preview-empty">
                            <p>Preview is not available for this file type.</p>
                            <a :href="previewUrl" target="_blank" rel="noopener">Open document</a>
                        </div>
                    </template>
                    <template x-if="!previewUrl">
                        <div class="client-document-preview-empty"><p>No document file available.</p></div>
                    </template>
                </div>
            </section>

            <aside class="client-document-info-panel" aria-label="Document information">
                <nav class="client-document-tabs" role="tablist" aria-label="Document information tabs">
                    <button type="button" role="tab" :aria-selected="activeTab === 'details'" :class="{ 'is-active': activeTab === 'details' }" @click="activeTab = 'details'">Details</button>
                    <button type="button" role="tab" :aria-selected="activeTab === 'attachments'" :class="{ 'is-active': activeTab === 'attachments' }" @click="activeTab = 'attachments'">Attachments</button>
                </nav>

                <div class="client-document-tab-content">
                    <section x-show="activeTab === 'details'" class="client-document-details-content" aria-label="Document details">
                        <div class="client-document-details-scroll">
                            @if ($isSoftCopyRequest)
                                <dl class="client-document-detail-list">
                                    <div class="client-document-detail-row"><dt>Purpose</dt><dd>{{ $requestRecord->purpose ?: '—' }}</dd></div>
                                    <div class="client-document-detail-row"><dt>Details</dt><dd>{{ $requestRecord->purpose_details ?: '—' }}</dd></div>
                                    <div class="client-document-detail-row"><dt>Type</dt><dd>Soft copy</dd></div>
                                    <div class="client-document-detail-row"><dt>Requested By</dt><dd>{{ $requestRecord->user?->name ?: '—' }}</dd></div>
                                    <div class="client-document-detail-row"><dt>Date of Request</dt><dd>{{ $requestRecord->date_of_request?->format('F d, Y') ?: '—' }}</dd></div>
                                    <div class="client-document-detail-row"><dt>Date Accepted</dt><dd>{{ $requestRecord->date_processed?->format('F d, Y') ?: '—' }}</dd></div>
                                </dl>
                            @else
                                <dl class="client-document-detail-list">
                                    @if ($statusValue !== 'rejected')
                                        <div class="client-document-detail-row"><dt>LAO Number</dt><dd>{{ $documentRecord->lao_number ?: '—' }}</dd></div>
                                    @endif
                                    <div class="client-document-detail-row"><dt>Status</dt><dd><span class="client-document-table-status" style="--badge-color: {{ $statusBadgeColor }};">{{ $statusLabel }}</span></dd></div>
                                    <div class="client-document-detail-row"><dt>Document Type</dt><dd>{{ $documentRecord->document_type ?: '—' }}</dd></div>
                                    <div class="client-document-detail-row"><dt>Office / Unit</dt><dd>{{ $documentRecord->office_unit ?: '—' }}</dd></div>
                                    <div class="client-document-detail-row"><dt>Particulars</dt><dd>{{ $documentSubject ?: '—' }}</dd></div>
                                    @if ($documentRecord->deadline)
                                        <div class="client-document-detail-row"><dt>Deadline</dt><dd>{{ $documentRecord->deadline->format('F d, Y') }}</dd></div>
                                    @endif
                                    <div class="client-document-detail-row"><dt>Date Submitted</dt><dd>{{ $documentRecord->created_at?->format('F d, Y') ?: '—' }}</dd></div>
                                    @if ($statusValue === 'rejected')
                                        <div class="client-document-detail-row"><dt class="is-rejected-label">Reason for Rejection</dt><dd class="is-rejected-value">{{ $rejectionReason ?: 'No rejection reason provided.' }}</dd></div>
                                    @endif
                                </dl>
                            @endif
                        </div>
                    </section>

                    <section x-show="activeTab === 'attachments'" class="client-document-attachments-content" aria-label="Document attachments">
                        @if ($transmittalFiles->isNotEmpty())
                            <div class="client-document-file-group">
                                <h3>Transmittal / Endorsement</h3>
                                @foreach ($transmittalFiles as $transmittalFile)
                                    @php
                                        $transmittalFileName = basename($transmittalFile['path']);
                                        $transmittalPreviewUrl = $transmittalFile['id'] === null
                                            ? route('client.document.transmittal.preview', ['document' => $documentRecord->getPublicRouteKey()])
                                            : route('client.document.transmittal-attachment.preview', ['document' => $documentRecord->getPublicRouteKey(), 'attachment' => $transmittalFile['id']]);
                                        $transmittalDownloadUrl = $transmittalFile['id'] === null
                                            ? route('client.document.transmittal.download', ['document' => $documentRecord->getPublicRouteKey()])
                                            : route('client.document.transmittal-attachment.download', ['document' => $documentRecord->getPublicRouteKey(), 'attachment' => $transmittalFile['id']]);
                                    @endphp
                                    <div class="client-document-file-row">
                                        <button
                                            type="button"
                                            class="client-document-file-select"
                                            title="View {{ $transmittalFileName }}"
                                            aria-label="View {{ $transmittalFileName }}"
                                            @click="selectFile(@js($transmittalPreviewUrl), @js($transmittalFileName), @js($fileTypeLabel($transmittalFileName)), @js(strtolower(pathinfo($transmittalFileName, PATHINFO_EXTENSION))), @js($fileIconClass($transmittalFileName)), '')"
                                        >
                                            <span class="client-document-file-badge {{ $fileBadgeClass($transmittalFileName) }}">{{ strtoupper(pathinfo($transmittalFileName, PATHINFO_EXTENSION)) ?: 'FILE' }}</span>
                                            <span class="client-document-file-name">{{ $transmittalFileName }}</span>
                                        </button>
                                        <div class="client-document-file-menu-wrap" x-data="{ menuOpen: false }">
                                            <button type="button" class="client-document-file-menu-button" aria-label="File options" aria-haspopup="menu" @click.stop="menuOpen = !menuOpen">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.6" d="M12 6.5h.01M12 12h.01M12 17.5h.01" /></svg>
                                            </button>
                                            <div x-cloak x-show="menuOpen" x-on:click.outside="menuOpen = false" class="client-document-file-menu" role="menu">
                                                <a href="{{ $transmittalDownloadUrl }}" target="_blank" rel="noopener" role="menuitem">Download</a>
                                                <button type="button" role="menuitem" data-print-url="{{ $transmittalPreviewUrl }}" @click="menuOpen = false; printFile($event.currentTarget.dataset.printUrl)">Print</button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="client-document-file-group">
                            <h3>Files Submitted</h3>
                            @forelse ($submittedFiles as $submittedFile)
                                @php
                                    $submittedFileName = $submittedFile->file_path ? basename((string) $submittedFile->file_path) : 'Document';
                                    $submittedFilePreviewUrl = route('client.document.version.preview', ['document' => $documentRecord->getPublicRouteKey(), 'version' => $submittedFile->version_id]);
                                    $submittedFileExtension = strtolower((string) pathinfo($submittedFileName, PATHINFO_EXTENSION));
                                @endphp
                                <div class="client-document-file-row">
                                    <button
                                        type="button"
                                        class="client-document-file-select"
                                        title="View {{ $submittedFileName }}"
                                        aria-label="View {{ $submittedFileName }}"
                                        @click="selectFile(@js($submittedFilePreviewUrl), @js($submittedFileName), @js($fileTypeLabel($submittedFileName)), @js($submittedFileExtension), @js($fileIconClass($submittedFileName)), '')"
                                    >
                                        <span class="client-document-file-badge {{ $fileBadgeClass($submittedFileName) }}">{{ strtoupper(pathinfo($submittedFileName, PATHINFO_EXTENSION)) ?: 'FILE' }}</span>
                                        <span class="client-document-file-name">{{ $submittedFileName }}</span>
                                    </button>
                                    <div class="client-document-file-menu-wrap" x-data="{ menuOpen: false }">
                                        <button type="button" class="client-document-file-menu-button" aria-label="Submitted file options" aria-haspopup="menu" @click.stop="menuOpen = !menuOpen">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.6" d="M12 6.5h.01M12 12h.01M12 17.5h.01" /></svg>
                                        </button>
                                        <div x-cloak x-show="menuOpen" x-on:click.outside="menuOpen = false" class="client-document-file-menu" role="menu">
                                            <a href="{{ route('client.document.version.download', ['document' => $documentRecord->getPublicRouteKey(), 'version' => $submittedFile->version_id]) }}" target="_blank" rel="noopener" role="menuitem">Download</a>
                                            <button type="button" role="menuitem" data-print-url="{{ route('client.document.version.preview', ['document' => $documentRecord->getPublicRouteKey(), 'version' => $submittedFile->version_id]) }}" @click="menuOpen = false; printFile($event.currentTarget.dataset.printUrl)">Print</button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="client-document-file-empty">No files submitted.</p>
                            @endforelse
                        </div>

                        <div class="client-document-file-group">
                            <h3>Revision</h3>
                            @forelse ($versions as $version)
                                @php
                                    $versionFileName = $version->file_path ? basename((string) $version->file_path) : 'Document';
                                    $versionValue = trim((string) ($version->version_number ?? ''));
                                    $versionLabel = $versionValue !== ''
                                        ? (str_contains(strtolower($versionValue), 'version') || str_starts_with(strtolower($versionValue), 'v') ? $versionValue : 'v' . $versionValue)
                                        : 'v' . $loop->iteration;
                                @endphp
                                <div class="client-document-file-row">
                                    <button
                                        type="button"
                                        class="client-document-file-select"
                                        title="View {{ $versionFileName }}"
                                        aria-label="View {{ $versionFileName }}"
                                        @click="selectFile(@js(route('client.document.version.preview', ['document' => $documentRecord->getPublicRouteKey(), 'version' => $version->version_id])), @js($versionFileName), @js($fileTypeLabel($versionFileName)), @js(strtolower(pathinfo($versionFileName, PATHINFO_EXTENSION))), @js($fileIconClass($versionFileName)), @js($versionLabel))"
                                    >
                                        <span class="client-document-file-badge {{ $fileBadgeClass($versionFileName) }}">{{ strtoupper(pathinfo($versionFileName, PATHINFO_EXTENSION)) ?: 'FILE' }}</span>
                                        <span class="client-document-file-name">{{ $versionFileName }}</span>
                                    </button>
                                    <span class="client-document-version-badge">{{ $versionLabel }}</span>
                                    <div class="client-document-file-menu-wrap" x-data="{ menuOpen: false }">
                                        <button type="button" class="client-document-file-menu-button" aria-label="File options" aria-haspopup="menu" @click.stop="menuOpen = !menuOpen">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.6" d="M12 6.5h.01M12 12h.01M12 17.5h.01" /></svg>
                                        </button>
                                        <div x-cloak x-show="menuOpen" x-on:click.outside="menuOpen = false" class="client-document-file-menu" role="menu">
                                            <a href="{{ route('client.document.version.download', ['document' => $documentRecord->getPublicRouteKey(), 'version' => $version->version_id]) }}" target="_blank" rel="noopener" role="menuitem">Download</a>
                                            <button type="button" role="menuitem" data-print-url="{{ route('client.document.version.preview', ['document' => $documentRecord->getPublicRouteKey(), 'version' => $version->version_id]) }}" @click="menuOpen = false; printFile($event.currentTarget.dataset.printUrl)">Print</button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="client-document-file-empty">No document file available.</p>
                            @endforelse
                        </div>
                    </section>
                </div>
            </aside>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }

        .client-document-view-page { width: 100%; }
        .client-document-viewer-layout {
            display: grid;
            width: 100%;
            height: calc(100dvh - 8rem);
            min-height: 38rem;
            grid-template-columns: minmax(0, 1.35fr) minmax(22rem, 0.85fr);
            overflow: hidden;
            border: 1px solid #9ca3af;
            border-radius: 1.125rem;
            background: #fff;
        }
        .client-document-preview-panel,
        .client-document-info-panel { min-width: 0; min-height: 0; background: #fff; }
        .client-document-preview-panel { display: flex; flex-direction: column; overflow: hidden; }
        .client-document-info-panel { display: flex; flex-direction: column; overflow: hidden; border-left: 1px solid #d1d5db; }
        .client-document-preview-heading {
            display: flex;
            height: 4rem;
            min-height: 4rem;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            border-bottom: 1px solid #d1d5db;
            box-sizing: border-box;
            padding: 0.5rem 1.125rem;
        }
        .client-document-preview-file-meta,
        .client-document-preview-actions,
        .client-document-file-row { display: flex; align-items: center; }
        .client-document-preview-file-meta { min-width: 0; gap: 0.75rem; }
        .client-document-back-button,
        .client-document-preview-action {
            display: inline-flex;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            transition: background-color 150ms ease, color 150ms ease;
        }
        .client-document-back-button { width: 2rem; height: 2rem; border-radius: 0.5rem; }
        .client-document-back-button:hover,
        .client-document-preview-action:hover { background: #f3f4f6; color: #111827; }
        .client-document-back-button svg { width: 1.35rem; height: 1.35rem; }
        .client-document-file-icon {
            display: inline-flex;
            width: 2.75rem;
            height: 2.75rem;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
        }
        .client-document-file-icon svg { width: 1.45rem; height: 1.45rem; }
        .client-document-file-icon-pdf,
        .client-document-file-badge-pdf { color: #ef3340; background: #fee2e2; }
        .client-document-file-icon-docx,
        .client-document-file-badge-docx { color: #2563eb; background: #dbeafe; }
        .client-document-file-icon-default,
        .client-document-file-badge-default { color: #64748b; background: #e2e8f0; }
        .client-document-preview-file-copy { min-width: 0; }
        .client-document-preview-file-copy h2 {
            overflow: hidden;
            margin: 0;
            color: #1f2937;
            font-size: 1.05rem;
            font-weight: 700;
            line-height: 1.35;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .client-document-preview-file-copy p { margin: 0.15rem 0 0; color: #9ca3af; font-size: 0.85rem; font-weight: 600; }
        .client-document-preview-actions { flex: 0 0 auto; gap: 0.25rem; }
        .client-document-preview-action { width: 2.25rem; height: 2.25rem; border-radius: 0.5rem; }
        .client-document-preview-action svg { width: 1.15rem; height: 1.15rem; }
        .client-document-version-badge {
            display: inline-flex;
            flex: 0 0 auto;
            align-items: center;
            border-radius: 999px;
            background: #f3f4f6;
            padding: 0.3rem 0.55rem;
            color: #6b7280;
            font-size: 0.7rem;
            font-weight: 700;
            line-height: 1;
        }
        .client-document-preview-frame { min-height: 0; flex: 1 1 auto; overflow: auto; background: #f3f4f6; }
        .client-document-preview-frame iframe { display: block; width: 100%; height: 100%; min-height: 38rem; border: 0; }
        .client-document-image-preview { display: flex; min-height: 100%; align-items: center; justify-content: center; overflow: auto; padding: 1rem; }
        .client-document-image-preview img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .client-document-preview-empty { display: flex; min-height: 100%; flex-direction: column; align-items: center; justify-content: center; gap: 0.6rem; padding: 1.5rem; color: #6b7280; text-align: center; }
        .client-document-preview-empty p { margin: 0; font-size: 0.9rem; }
        .client-document-preview-empty a { color: #6366f1; font-size: 0.85rem; font-weight: 700; }
        .client-document-tabs { display: grid; min-height: 4rem; flex: 0 0 4rem; grid-template-columns: repeat(2, minmax(0, 1fr)); border-bottom: 1px solid #d1d5db; background: #fff; }
        .client-document-tabs button { border: 0; border-bottom: 2px solid transparent; background: #fff; color: #737b8c; cursor: pointer; font-size: 0.8rem; font-weight: 700; }
        .client-document-tabs button:hover { background: #f8fafc; color: #4f46e5; }
        .client-document-tabs button.is-active { border-bottom-color: #6366f1; color: #4f46e5; }
        .client-document-tab-content { display: flex; min-height: 0; flex: 1 1 auto; overflow: hidden; }
        .client-document-tab-content > section { width: 100%; min-width: 0; min-height: 0; }
        .client-document-details-content,
        .client-document-attachments-content { overflow-y: auto; }
        .client-document-details-scroll,
        .client-document-attachments-content { padding: 1.25rem; }
        .client-document-detail-list { margin: 0; }
        .client-document-detail-row { display: grid; grid-template-columns: minmax(7.5rem, 0.8fr) minmax(0, 1.5fr); gap: 1rem; padding: 0.6rem 0; }
        .client-document-detail-row dt { color: #8a94a6; font-size: 0.85rem; font-weight: 600; }
        .client-document-detail-row dd { min-width: 0; margin: 0; color: #374151; font-size: 0.85rem; font-weight: 700; overflow-wrap: anywhere; }
        .client-document-badge { display: inline-flex; align-items: center; border: 1px solid transparent; border-radius: 0.4rem; padding: 0.28rem 0.55rem; font-size: 0.75rem; font-weight: 700; line-height: 1.1; }
        .client-document-table-status { color: var(--badge-color); font-size: 0.85rem; font-weight: 700; }
        .is-rejected-label, .is-rejected-value { color: #dc2626 !important; }
        .client-document-file-group + .client-document-file-group { margin-top: 1.75rem; }
        .client-document-file-group h3 { margin: 0 0 0.8rem; color: #4b5563; font-size: 0.95rem; font-weight: 700; }
        .client-document-file-row { min-width: 0; gap: 0.75rem; border-bottom: 1px solid #eef0f3; padding: 0.7rem 0.15rem; }
        .client-document-file-select {
            display: flex;
            min-width: 0;
            flex: 1 1 auto;
            align-items: center;
            gap: 0.75rem;
            border: 0;
            background: transparent;
            padding: 0;
            color: inherit;
            text-align: left;
            cursor: pointer;
        }
        .client-document-file-select:hover { background: #f8fafc; }
        .client-document-file-select:focus-visible { outline: 2px solid #6366f1; outline-offset: 2px; border-radius: 0.35rem; }
        .client-document-file-badge { display: inline-flex; width: 2.35rem; height: 2.35rem; flex: 0 0 auto; align-items: center; justify-content: center; border-radius: 0.4rem; font-size: 0.62rem; font-weight: 800; }
        .client-document-file-name { min-width: 0; flex: 1 1 auto; overflow: hidden; color: #7b8495; font-size: 0.85rem; font-weight: 600; text-overflow: ellipsis; white-space: nowrap; }
        .client-document-file-empty { margin: 0; color: #9ca3af; font-size: 0.85rem; }
        .client-document-file-menu-wrap { position: relative; flex: 0 0 auto; }
        .client-document-file-menu-button { display: inline-flex; width: 2rem; height: 2rem; align-items: center; justify-content: center; border: 0; border-radius: 999px; background: transparent; color: #4b5563; cursor: pointer; }
        .client-document-file-menu-button:hover { background: #f3f4f6; color: #111827; }
        .client-document-file-menu-button svg { width: 1.15rem; height: 1.15rem; }
        .client-document-file-menu { position: absolute; top: calc(100% + 0.2rem); right: 0; z-index: 20; display: flex; width: 6.5rem; flex-direction: column; gap: 0.15rem; border: 1px solid #e5e7eb; border-radius: 0.45rem; background: #fff; padding: 0.25rem; box-shadow: 0 0.5rem 1rem rgb(15 23 42 / 15%); }
        .client-document-file-menu a,
        .client-document-file-menu button { display: block; width: 100%; border: 0; border-radius: 0.35rem; background: transparent; padding: 0.45rem 0.6rem; color: #374151; font-size: 0.75rem; text-align: left; text-decoration: none; cursor: pointer; }
        .client-document-file-menu a:hover,
        .client-document-file-menu button:hover { background: #f3f4f6; }

        .dark .client-document-viewer-layout,
        .dark .client-document-preview-panel,
        .dark .client-document-info-panel,
        .dark .client-document-preview-heading,
        .dark .client-document-tabs,
        .dark .client-document-tabs button { background: #1f2937; }
        .dark .client-document-info-panel,
        .dark .client-document-preview-heading,
        .dark .client-document-tabs,
        .dark .client-document-file-row { border-color: #374151; }
        .dark .client-document-preview-file-copy h2,
        .dark .client-document-detail-row dd,
        .dark .client-document-file-group h3 { color: #f3f4f6; }
        .dark .client-document-detail-row dt,
        .dark .client-document-file-name { color: #9ca3af; }
        .dark .client-document-back-button,
        .dark .client-document-preview-action,
        .dark .client-document-file-menu-button { color: #d1d5db; }
        .dark .client-document-back-button:hover,
        .dark .client-document-preview-action:hover,
        .dark .client-document-tabs button:hover { background: #374151; color: #fff; }
        .dark .client-document-file-select:hover { background: #374151; }
        .dark .client-document-preview-frame { background: #111827; }
        .dark .client-document-file-menu { border-color: #374151; background: #1f2937; }
        .dark .client-document-file-menu a,
        .dark .client-document-file-menu button { color: #e5e7eb; }
        .dark .client-document-file-menu a:hover,
        .dark .client-document-file-menu button:hover,
        .dark .client-document-file-menu-button:hover { background: #374151; color: #fff; }

        @media (max-width: 1023px) {
            .client-document-viewer-layout { height: auto; min-height: 0; grid-template-columns: 1fr; overflow: visible; }
            .client-document-preview-panel { min-height: 38rem; }
            .client-document-info-panel { min-height: 30rem; border-top: 1px solid #d1d5db; border-left: 0; }
        }
        @media (max-width: 640px) {
            .client-document-preview-heading { align-items: flex-start; flex-direction: column; }
            .client-document-preview-actions { align-self: flex-end; }
            .client-document-detail-row { grid-template-columns: 1fr; gap: 0.25rem; }
            .client-document-detail-row dd { padding-bottom: 0.45rem; }
        }
    </style>
</x-filament-panels::page>
