<x-filament-panels::page>
    @php
        $latestVersion = $documentRecord->latestVersion;
        $latestFilePath = $latestVersion?->file_path;
        $documentSubject = $documentRecord->particulars ?: $documentRecord->description;
        $displayedFileName = filled($latestFilePath)
            ? basename((string) $latestFilePath)
            : 'Preview unavailable';
        $versionNumber = trim((string) ($latestVersion?->version_number ?? '1'));
        $versionBadge = $versionNumber !== ''
            ? (str_contains(strtolower($versionNumber), 'version') || str_starts_with(strtolower($versionNumber), 'v')
                ? $versionNumber
                : 'v' . $versionNumber)
            : 'v1';
        $isSoftCopyRequest = $requestRecord?->copy_type === 'soft_copy';
        $statusValue = $documentRecord->status;
        $statusLabel = match ($statusValue) {
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'pending' => 'Pending',
            'rejected' => 'Rejected',
            'outgoing' => 'Outgoing',
            'returned' => 'Returned',
            'archived' => 'Archived',
            default => ucfirst(str_replace('_', ' ', (string) $statusValue)),
        };
        $rejectionReason = $documentRecord->rejections->first()?->reason
            ?? $documentRecord->rejection_reason;
    @endphp

    <div class="client-document-view-page space-y-5">
        <div class="grid min-h-[calc(100dvh-8rem)] grid-cols-1 items-stretch gap-5 xl:grid-cols-2">
            <section class="flex h-full min-h-0 flex-col overflow-hidden rounded-3xl border border-gray-300 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800" aria-label="Document details and status history">
                <div class="flex min-h-[64px] items-center gap-3 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <a
                        href="{{ $returnPage === 'dashboard'
                            ? \App\Filament\Client\Pages\Dashboard::getUrl()
                            : \App\Filament\Client\Pages\Documents::getUrl(['tab' => $returnTab]) }}"
                        class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-gray-700 transition hover:bg-gray-100 dark:text-gray-100 dark:hover:bg-gray-700"
                        title="Back"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                        </svg>
                    </a>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Document Details</h2>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto p-5">
                    @if ($isSoftCopyRequest)
                        <dl class="divide-y divide-gray-100 dark:divide-gray-700">
                            <div class="grid grid-cols-[minmax(120px,0.8fr)_minmax(0,1.5fr)] gap-4 py-3 first:pt-0">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">Purpose</dt>
                                <dd class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $requestRecord->purpose ?: '—' }}</dd>
                            </div>
                            <div class="grid grid-cols-[minmax(120px,0.8fr)_minmax(0,1.5fr)] gap-4 py-3">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">Details</dt>
                                <dd class="whitespace-pre-line text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $requestRecord->purpose_details ?: '—' }}</dd>
                            </div>
                            <div class="grid grid-cols-[minmax(120px,0.8fr)_minmax(0,1.5fr)] gap-4 py-3">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">Type</dt>
                                <dd class="text-sm font-semibold text-gray-900 dark:text-gray-100">Soft copy</dd>
                            </div>
                            <div class="grid grid-cols-[minmax(120px,0.8fr)_minmax(0,1.5fr)] gap-4 py-3">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">Requested By</dt>
                                <dd class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $requestRecord->user?->name ?: '—' }}</dd>
                            </div>
                            <div class="grid grid-cols-[minmax(120px,0.8fr)_minmax(0,1.5fr)] gap-4 py-3">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">Date of Request</dt>
                                <dd class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $requestRecord->date_of_request?->format('F d, Y') ?: '—' }}</dd>
                            </div>
                            <div class="grid grid-cols-[minmax(120px,0.8fr)_minmax(0,1.5fr)] gap-4 py-3 last:pb-0">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">Date Accepted</dt>
                                <dd class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $requestRecord->date_processed?->format('F d, Y') ?: '—' }}</dd>
                            </div>
                        </dl>
                    @else
                        <dl class="divide-y divide-gray-100 dark:divide-gray-700">
                            @if ($statusValue !== 'rejected')
                                <div class="grid grid-cols-[minmax(120px,0.8fr)_minmax(0,1.5fr)] gap-4 py-3 first:pt-0">
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">LAO Number</dt>
                                    <dd class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $documentRecord->lao_number ?: '—' }}</dd>
                                </div>
                            @endif
                            <div class="grid grid-cols-[minmax(120px,0.8fr)_minmax(0,1.5fr)] gap-4 py-3">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">Status</dt>
                                <dd class="text-sm font-semibold {{ $statusValue === 'rejected' ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-gray-100' }}">{{ $statusLabel }}</dd>
                            </div>
                            <div class="grid grid-cols-[minmax(120px,0.8fr)_minmax(0,1.5fr)] gap-4 py-3">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">Document Type</dt>
                                <dd class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $documentRecord->document_type ?: '—' }}</dd>
                            </div>
                            <div class="grid grid-cols-[minmax(120px,0.8fr)_minmax(0,1.5fr)] gap-4 py-3">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">Office / Unit</dt>
                                <dd class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $documentRecord->office_unit ?: '—' }}</dd>
                            </div>
                            <div class="grid grid-cols-[minmax(120px,0.8fr)_minmax(0,1.5fr)] gap-4 py-3">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">Document Subject</dt>
                                <dd class="whitespace-pre-line text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $documentSubject ?: '—' }}</dd>
                            </div>
                            <div class="grid grid-cols-[minmax(120px,0.8fr)_minmax(0,1.5fr)] gap-4 py-3">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">Date Submitted</dt>
                                <dd class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $documentRecord->created_at?->format('F d, Y') ?: '—' }}</dd>
                            </div>
                            @if ($statusValue === 'rejected')
                                <div class="grid grid-cols-[minmax(120px,0.8fr)_minmax(0,1.5fr)] gap-4 py-3 last:pb-0">
                                    <dt class="text-sm text-red-600 dark:text-red-400">Reason for Rejection</dt>
                                    <dd class="whitespace-pre-line text-sm font-semibold text-red-700 dark:text-red-400">{{ $rejectionReason ?: 'No rejection reason provided.' }}</dd>
                                </div>
                            @endif
                        </dl>
                    @endif

                    <section class="mt-6" aria-label="Document status timeline">
                        <div class="mb-4">
                            <h2 class="text-lg font-semibold tracking-tight text-gray-900 dark:text-white">Status timeline</h2>
                        </div>

                        @if ($statusTimeline !== [])
                            <div class="max-h-[22rem] overflow-y-auto overscroll-contain pr-2 md:max-h-[26rem]">
                                <div class="space-y-0">
                                    @foreach ($statusTimeline as $update)
                                        @php
                                            $timelineMarker = $loop->last
                                                ? 'bg-emerald-700 text-white'
                                                : 'bg-emerald-100 text-emerald-700';
                                        @endphp
                                        <article
                                            class="relative grid grid-cols-[4.5rem_2.75rem_minmax(0,1fr)] gap-3 pb-5 last:pb-0 md:grid-cols-[6rem_3rem_minmax(0,1fr)]"
                                            wire:key="client-document-status-{{ $loop->index }}"
                                        >
                                            <time class="pt-1 text-right text-sm font-semibold leading-5 text-gray-700 dark:text-gray-200">
                                                <span class="block text-sm font-semibold leading-5">{{ $update['date'] }}</span>
                                                <span class="mt-1 block font-normal text-gray-400 dark:text-gray-500">{{ $update['time'] }}</span>
                                            </time>

                                            @unless ($loop->last)
                                                <span class="absolute bottom-0 left-[calc(4.5rem+0.75rem+1.375rem)] top-7 w-px -translate-x-1/2 bg-gray-300 dark:bg-gray-600 md:left-[calc(6rem+0.75rem+1.5rem)]" aria-hidden="true"></span>
                                            @endunless

                                            <span class="relative z-10 inline-flex h-7 w-7 items-center justify-center justify-self-center self-start rounded-full {{ $timelineMarker }} ring-4 ring-white dark:ring-gray-800" aria-hidden="true">
                                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4.5 4.5L19 7" />
                                                </svg>
                                            </span>

                                            <div class="min-w-0 pt-1">
                                                <h3 class="text-sm font-semibold leading-5 text-gray-900 dark:text-gray-100">{{ $update['title'] }}</h3>
                                                <p class="mt-1 text-sm leading-5 text-gray-500 dark:text-gray-400">{{ $update['description'] }}</p>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400">No status timeline available.</p>
                        @endif
                    </section>
                </div>
            </section>

            <section class="flex h-full min-h-0 flex-col overflow-hidden rounded-3xl border border-gray-300 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800" aria-label="Document preview">
                <div class="flex min-h-[64px] items-center justify-between gap-3 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Document Preview</h2>
                        <p class="mt-1 truncate text-sm font-medium text-gray-400 dark:text-gray-500" title="{{ $displayedFileName }}">{{ $displayedFileName }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        @if ($downloadUrl)
                            <a
                                href="{{ $downloadUrl }}"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white"
                                title="Download document"
                                aria-label="Download document"
                            >
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M5 21h14a2 2 0 0 0 2-2v-1M3 18v1a2 2 0 0 0 2 2" />
                                </svg>
                            </a>
                        @endif

                        @if ($previewUrl)
                            <a
                                href="{{ $previewUrl }}"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-gray-500 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white"
                                title="Print document"
                                aria-label="Print document"
                            >
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V4h12v5M6 18H4a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2M6 14h12v7H6v-7Z" />
                                    <path stroke-linecap="round" d="M18 12h.01" />
                                </svg>
                            </a>
                        @endif

                        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-500 dark:bg-gray-700 dark:text-gray-300">{{ $versionBadge }}</span>
                    </div>
                </div>

                <div class="min-h-[600px] flex-1 overflow-hidden bg-gray-100 dark:bg-gray-900">
                    @if ($previewUrl)
                        @php
                            $extension = strtolower(pathinfo((string) $latestFilePath, PATHINFO_EXTENSION));
                        @endphp

                        @if (in_array($extension, ['pdf', 'doc', 'docx'], true))
                            <iframe src="{{ $previewUrl }}#toolbar=0" class="h-full min-h-[600px] w-full border-0" title="Document Preview"></iframe>
                        @elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true))
                            <div class="flex h-full min-h-[600px] items-center justify-center overflow-auto p-4">
                                <img src="{{ $previewUrl }}" alt="Document Preview" class="max-h-full max-w-full object-contain">
                            </div>
                        @else
                            <div class="flex h-full min-h-[600px] flex-col items-center justify-center gap-3 p-6 text-center">
                                <p class="font-medium text-gray-700 dark:text-gray-300">Preview is not available for this file type.</p>
                                <a href="{{ $previewUrl }}" target="_blank" class="text-sm font-semibold text-[#6366F1] hover:underline">Open document</a>
                            </div>
                        @endif
                    @else
                        <div class="flex h-full min-h-[600px] items-center justify-center p-6 text-center">
                            <p class="text-sm text-gray-500 dark:text-gray-400">No document file available.</p>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
</x-filament-panels::page>
