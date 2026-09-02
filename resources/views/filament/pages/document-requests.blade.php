<x-filament-panels::page>
    @php
        $activeSection = $this->activeSection;
        $statusCounts = $this->getStatusCounts();
    @endphp

    <div class="admin-document-requests-page">
        {{-- STATUS HEADER --}}
        <div class="mb-0 w-full overflow-x-auto border border-gray-300 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <nav class="flex min-w-max items-center gap-1 p-2" aria-label="Document request status">
                @foreach ([
                    'pending' => 'Pending',
                    'accepted' => 'Accepted',
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

        {{-- SEARCH + FILTERS --}}
        <div class="mb-0 flex w-full flex-wrap items-center gap-3 border-x border-gray-300 bg-white px-3 py-7 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:flex-nowrap sm:justify-start">
            <div class="relative w-full sm:w-96">
                <input
                    type="text"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Search Document"
                    class="h-10 w-full rounded-full border border-gray-300 bg-white pl-4 pr-11 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-400"
                >
                <svg class="absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-800 dark:text-gray-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 21l-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z" />
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
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 1 1 1.06-1.04L10 10.832l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                </svg>
            </div>

            <div class="relative w-full sm:w-60">
                <input
                    type="date"
                    wire:model.live="dateFilter"
                    aria-label="Filter by request date"
                    class="h-10 w-full appearance-none rounded-full border border-gray-300 bg-white pl-10 pr-4 text-sm text-gray-500 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                >
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500 dark:text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3.5" y="5" width="17" height="15.5" rx="2" />
                    <path d="M7.5 3.5v3M16.5 3.5v3M3.5 9h17" />
                </svg>
            </div>
        </div>

        {{-- FILAMENT REQUEST TABLE --}}
        {{ $this->table }}

        <style>
            .fi-page-content {
                gap: 0 !important;
            }

            .admin-document-requests-page .fi-ta {
                border-radius: 0;
                box-shadow: none;
            }

            .admin-document-requests-page .fi-ta-ctn {
                border: 1px solid rgb(209 213 219);
                border-radius: 0;
                box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            }

            .admin-document-requests-page .fi-ta-content {
                max-height: calc(100vh - 18rem);
                overflow: auto;
            }

            .admin-document-requests-page .fi-ta-table {
                font-size: 0.75rem;
            }

            .admin-document-requests-page .fi-ta-table th {
                font-size: 0.75rem;
                font-weight: 700;
                text-transform: uppercase;
            }

            .admin-document-requests-page .fi-ta-cell.fi-align-center > .fi-ta-col {
                justify-content: center;
            }

            .admin-document-requests-page .fi-ta-cell.fi-align-end > .fi-ta-col {
                justify-content: flex-end;
            }

            .admin-document-requests-page .fi-ta-cell.fi-align-start > .fi-ta-col,
            .admin-document-requests-page .fi-ta-cell.fi-align-left > .fi-ta-col {
                justify-content: flex-start;
            }

            .admin-document-requests-page .fi-ta-table tbody tr:not(.fi-ta-group-header-row) {
                transition: background-color 150ms ease-in-out;
            }

            .admin-document-requests-page .fi-ta-table tbody tr:not(.fi-ta-group-header-row):hover {
                background-color: rgb(239 246 255);
            }

            .admin-document-requests-page .fi-ta-group-heading {
                font-size: 0.6875rem;
                font-weight: 600;
            }

            .dark .admin-document-requests-page .fi-ta-ctn,
            .dark .admin-document-requests-page .fi-ta-header,
            .dark .admin-document-requests-page .fi-ta-content,
            .dark .admin-document-requests-page .fi-ta-footer {
                border-color: rgb(75 85 99);
                background-color: rgb(17 24 39);
            }

            .dark .admin-document-requests-page .fi-ta-table tbody tr {
                border-color: rgb(75 85 99);
            }

            .dark .admin-document-requests-page .fi-ta-table tbody tr:hover {
                background-color: rgb(255 255 255 / 0.05);
            }

            .dark .admin-document-requests-page .fi-ta-table th,
            .dark .admin-document-requests-page .fi-ta-table td {
                color: rgb(229 231 235);
            }
        </style>
    </div>
</x-filament-panels::page>
