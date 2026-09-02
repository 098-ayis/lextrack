<x-filament-panels::page>
    <div class="admin-documents-page">
        @php
            $activeSection = $this->activeSection;
            $statusCounts = $this->getStatusCounts();
        @endphp

        {{-- STATUS HEADER --}}
        <div class="mb-0 w-full overflow-x-auto border border-gray-300 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <nav class="flex min-w-max items-center gap-1 p-2" aria-label="Document status">
                @foreach ([
                    'pending' => 'Pending',
                    'incoming' => 'Incoming',
                    'outgoing' => 'Outgoing',
                    'completed' => 'Completed',
                    'rejected' => 'Rejected',
                ] as $section => $label)
                    <a
                        href="{{ request()->fullUrlWithQuery(['section' => $section]) }}"
                        class="rounded-md px-4 py-2 text-sm font-semibold transition-colors
                            {{ $activeSection === $section
                                ? 'bg-[#0F172A] text-white'
                                : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/10' }}"
                    >
                        {{ $label }}
                        <span
                            class="ml-2 inline-flex min-w-5 justify-center rounded-full px-1.5 py-0.5 text-xs
                                {{ $activeSection === $section
                                    ? 'bg-white/20 text-white'
                                    : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-200' }}"
                        >
                            {{ $statusCounts[$section] ?? 0 }}
                        </span>
                    </a>
                @endforeach
            </nav>
        </div>

        {{-- FILTER PILLS --}}
        <div class="mb-0 flex w-full flex-wrap items-center gap-3 border-x border-gray-300 bg-white px-3 py-7 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:flex-nowrap sm:justify-start">
            <div class="relative w-full sm:w-96">
                <input
                    type="text"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Search Document"
                    class="h-10 w-full rounded-full border border-gray-300 bg-white pl-4 pr-11 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-400"
                >
                <svg class="absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-800 dark:text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m2.1-5.4a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z" />
                </svg>
            </div>

            <div class="relative w-full sm:w-60">
                <select
                    wire:model.live="typeFilter"
                    class="h-10 w-full appearance-none rounded-full border border-gray-300 bg-white pl-4 pr-12 text-sm text-gray-500 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300"
                >
                    <option value="">All Document Types</option>
                    @foreach (\App\Models\DocumentType::orderBy('type_name')->get() as $type)
                        <option value="{{ $type->type_id }}">{{ $type->type_name }}</option>
                    @endforeach
                </select>
                <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500 dark:text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                </svg>
            </div>

            <div class="relative w-full sm:w-60">
                <input
                    type="date"
                    wire:model.live="dateFilter"
                    aria-label="Filter by upload date"
                    class="peer h-10 w-full appearance-none rounded-full border border-gray-300 bg-white pl-10 pr-4 text-sm focus:border-primary-500 focus:text-gray-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                >
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500 dark:text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3.5" y="5" width="17" height="15.5" rx="2" />
                    <path d="M7.5 3.5v3M16.5 3.5v3M3.5 9h17" />
                </svg>
            </div>

            <div class="ml-auto flex items-center gap-2">
                <a
                    href="{{ route('admin.documents.export', [
                        'section' => $activeSection,
                        'search' => $search,
                        'type' => $typeFilter,
                        'date' => $dateFilter,
                    ]) }}"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex h-10 items-center gap-2 rounded-md border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-white/10"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3v12m0 0 4-4m-4 4-4-4M5 18v3h14v-3" />
                    </svg>
                    Export
                </a>
                {{ $this->addDocumentAction }}
            </div>
        </div>

        {{-- FILAMENT DOCUMENT TABLE --}}
        <div class="documents-table-container">
            {{ $this->table }}
        </div>

        <style>
            .fi-page-content {
                gap: 0 !important;
            }

            .admin-documents-page .fi-ta {
                border-radius: 0;
                box-shadow: none;
            }

            .admin-documents-page .fi-ta-ctn {
                border-radius: 0;
                border: 1px solid rgb(209 213 219);
                border-color: rgb(209 213 219);
                box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            }

            .admin-documents-page .fi-ta-cell.fi-align-center > .fi-ta-col {
                justify-content: center;
            }

            .admin-documents-page .fi-ta-cell.fi-align-end > .fi-ta-col {
                justify-content: flex-end;
            }

            .admin-documents-page .fi-ta-cell.fi-align-start > .fi-ta-col,
            .admin-documents-page .fi-ta-cell.fi-align-left > .fi-ta-col {
                justify-content: flex-start;
            }

            .admin-documents-page .fi-ta-content {
                max-height: calc(100vh - 18rem);
                overflow: auto;
            }

            .admin-documents-page .fi-ta-table {
                font-size: 0.75rem;
            }

            .admin-documents-page .fi-ta-table th {
                font-size: 0.75rem;
                font-weight: 700;
                text-transform: uppercase;
            }

            .admin-documents-page .fi-ta-table tbody tr:not(.fi-ta-group-header-row) {
                transition: background-color 150ms ease-in-out;
            }

            .admin-documents-page .fi-ta-table tbody tr:not(.fi-ta-group-header-row):hover {
                background-color: rgb(239 246 255);
            }

            .admin-documents-page .fi-ta-group-heading {
                font-size: 0.6875rem;
                font-weight: 600;
            }

            .dark .admin-documents-page .fi-ta-ctn,
            .dark .admin-documents-page .fi-ta-header,
            .dark .admin-documents-page .fi-ta-content,
            .dark .admin-documents-page .fi-ta-footer {
                border-color: rgb(75 85 99);
                background-color: rgb(17 24 39);
            }

            .dark .admin-documents-page .fi-ta-table tbody tr {
                border-color: rgb(75 85 99);
            }

            .dark .admin-documents-page .fi-ta-table tbody tr:hover {
                background-color: rgb(255 255 255 / 0.05);
            }

            .dark .admin-documents-page .fi-ta-table th,
            .dark .admin-documents-page .fi-ta-table td {
                color: rgb(229 231 235);
            }
        </style>

        @if ($showAcceptedModal)
            <div wire:click="redirectToIncoming" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/45 p-4" role="dialog" aria-modal="true" aria-labelledby="document-accepted-title">
                <div wire:click.stop class="w-full max-w-md rounded-2xl bg-white px-7 py-8 text-center shadow-2xl dark:bg-gray-900">
                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-blue-50 dark:bg-blue-500/10">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full text-white shadow-lg" style="background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%);">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                    <h2 id="document-accepted-title" class="mt-6 text-xl font-bold text-gray-900 dark:text-white">Document Accepted Successfully</h2>
                    <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">
                        The document uploaded by
                        <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $acceptedDocumentUploader ?? 'the user' }}</span>
                        has been accepted and is now available in Incoming documents.
                    </p>
                    <button type="button" wire:click="redirectToIncoming" class="mt-7 w-full rounded-lg bg-blue-50 px-4 py-3 text-sm font-semibold text-gray-600 transition hover:bg-blue-100 dark:bg-blue-500/10 dark:text-gray-200 dark:hover:bg-blue-500/20">Close</button>
                </div>
            </div>
        @endif

        @if ($qrCodeSvg)
            <div wire:click.self="closeQrCode" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/50 p-4" role="dialog" aria-modal="true" aria-labelledby="qr-code-title">
                <div class="w-full max-w-md rounded-2xl bg-white p-6 text-center shadow-2xl dark:bg-gray-900">
                    <div class="flex items-center justify-between">
                        <h2 id="qr-code-title" class="text-lg font-bold text-gray-900 dark:text-white">Document QR Code</h2>
                        <button type="button" wire:click="closeQrCode" class="rounded-md p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-800 dark:text-gray-300 dark:hover:bg-white/10 dark:hover:text-white" aria-label="Close QR code"><span class="text-xl leading-none">&times;</span></button>
                    </div>
                    <div class="mx-auto mt-5 flex h-64 w-64 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-white p-2 dark:border-gray-600">{!! $qrCodeSvg !!}</div>
                    <p class="mt-4 text-sm text-gray-600 dark:text-gray-300">Scan this code to view the document status and details.</p>
                    <button type="button" wire:click="closeQrCode" class="mt-5 w-full rounded-lg bg-[#0F172A] px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-700">Close</button>
                </div>
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('click', (event) => {
            const clickedMenu = event.target.closest('.document-options-menu');

            document.querySelectorAll('.document-options-menu[open]').forEach((menu) => {
                if (menu !== clickedMenu) {
                    menu.removeAttribute('open');
                }
            });
        });
    </script>

    <x-filament-actions::modals />
</x-filament-panels::page>
