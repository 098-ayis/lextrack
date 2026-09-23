<x-filament-panels::page>
    <div class="client-documents-page">

    <!-- Custom Tabs Container (Tailwind Only) -->
    <div class="mb-0 w-full overflow-x-auto border border-gray-300 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <div class="flex min-w-max items-center gap-1 p-2">

        <!-- All Tab (Default) -->
        <button
            wire:click="updateTab('all')"
            class="flex items-center gap-2 rounded-md px-4 py-2 text-sm font-semibold transition-all {{ $activeTab === 'all' ? 'bg-[#0F172A] text-white dark:bg-[#6366F1]' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white' }}"
        >
            <x-filament::icon icon="heroicon-o-document-text" class="h-5 w-5 shrink-0" />
            All
            <span class="flex items-center justify-center rounded-full px-2 py-0.5 text-xs {{ $activeTab === 'all' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                {{ $this->getAllDocumentsCount() }}
            </span>
        </button>

        <!-- Pending Tab -->
        <button 
            wire:click="updateTab('pending')"
            class="flex items-center gap-2 rounded-md px-4 py-2 text-sm font-semibold transition-all {{ $activeTab === 'pending' ? 'bg-[#0F172A] text-white dark:bg-[#6366F1]' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white' }}"
        >
            <x-filament::icon icon="heroicon-o-document-text" class="h-5 w-5 shrink-0" />
            Pending
            <span class="flex items-center justify-center rounded-full px-2 py-0.5 text-xs {{ $activeTab === 'pending' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                {{ \App\Models\Document::where('user_id', auth()->id())->where('status', 'pending')->count() }}
            </span>
        </button>

        <!-- In Progress Tab -->
        <button 
            wire:click="updateTab('in_progress')"
            class="flex items-center gap-2 rounded-md px-4 py-2 text-sm font-semibold transition-all {{ $activeTab === 'in_progress' ? 'bg-[#0F172A] text-white dark:bg-[#6366F1]' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white' }}"
        >
            <x-filament::icon icon="heroicon-o-inbox-arrow-down" class="h-5 w-5 shrink-0" />
            In Progress
            <span class="flex items-center justify-center rounded-full px-2 py-0.5 text-xs {{ $activeTab === 'in_progress' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                {{ \App\Models\Document::where('user_id', auth()->id())->whereIn('status', ['in_progress', 'outgoing'])->whereDoesntHave('documentRequests', fn ($query) => $query->where('user_id', auth()->id()))->count() }}
            </span>
        </button>

        <!-- Completed Tab -->
        <button
            wire:click="updateTab('completed')"
            class="flex items-center gap-2 rounded-md px-4 py-2 text-sm font-semibold transition-all {{ $activeTab === 'completed' ? 'bg-[#0F172A] text-white dark:bg-[#6366F1]' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white' }}"
        >
            <x-filament::icon icon="heroicon-o-check-circle" class="h-5 w-5 shrink-0" />
            Completed
            <span class="flex items-center justify-center rounded-full px-2 py-0.5 text-xs {{ $activeTab === 'completed' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                {{ \App\Models\Document::where('user_id', auth()->id())->whereIn('status', ['completed', 'archived'])->count() }}
            </span>
        </button>

        <!-- Rejected Tab -->
        <button 
            wire:click="updateTab('rejected')"
            class="flex items-center gap-2 rounded-md px-4 py-2 text-sm font-semibold transition-all {{ $activeTab === 'rejected' ? 'bg-[#0F172A] text-white dark:bg-[#6366F1]' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white' }}"
        >
            <x-filament::icon icon="heroicon-o-x-circle" class="h-5 w-5 shrink-0" />
            Rejected
            <span class="flex items-center justify-center rounded-full px-2 py-0.5 text-xs {{ $activeTab === 'rejected' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                {{ \App\Models\Document::where('user_id', auth()->id())->where('status', 'rejected')->count() }}
            </span>
        </button>

        <!-- Requested Tab -->
        <button 
            wire:click="updateTab('requested')"
            class="flex items-center gap-2 rounded-md px-4 py-2 text-sm font-semibold transition-all {{ $activeTab === 'requested' ? 'bg-[#0F172A] text-white dark:bg-[#6366F1]' : 'text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white' }}"
        >
            <x-filament::icon icon="heroicon-o-document-plus" class="h-5 w-5 shrink-0" />
            Requested
            <span class="flex items-center justify-center rounded-full px-2 py-0.5 text-xs {{ $activeTab === 'requested' ? 'bg-white/20 text-white' : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                {{ \App\Models\DocumentRequest::where('user_id', auth()->id())->count() }}
            </span>
        </button>
        
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="mb-0 flex w-full flex-col gap-3 border-x border-gray-300 bg-white px-3 py-7 shadow-sm dark:border-gray-700 dark:bg-gray-900 sm:flex-row sm:items-center">

        <!-- Search -->
        <div class="relative w-full sm:max-w-md sm:flex-1">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                <svg
                    class="h-5 w-5 text-gray-400"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                    />
                </svg>
            </div>

            <input
                type="text"
                wire:model.live.debounce.400ms="documentSearch"
                placeholder="Search for documents..."
                class="block h-10 w-full rounded-full border border-gray-300 bg-white pl-11 pr-4 text-sm text-gray-900 placeholder:text-gray-400 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-[#6366F1] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-400"
            >
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap items-center gap-2">

            <!-- Type -->
            <div class="flex items-center gap-1.5">
                <div class="relative w-44">
                    <select
                        wire:model.live="documentType"
                        class="h-10 w-full appearance-none rounded-full border border-gray-300 bg-white pl-3 pr-9 text-xs font-semibold text-gray-900 focus:outline-none focus:ring-0 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                    >
                        <option value="">Document Type</option>
                        @if ($activeTab === 'requested')
                            <option value="original">Original</option>
                            <option value="soft_copy">Soft copy</option>
                        @else
                            @foreach (\App\Models\DocumentType::orderBy('type_name')->get() as $type)
                                <option value="{{ $type->type_name }}">{{ $type->type_name }}</option>
                            @endforeach
                        @endif
                    </select>

                    <svg
                        class="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-500 dark:text-gray-400"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </div>

                @if ($documentType)
                    <button
                        type="button"
                        wire:click="clearType"
                        class="flex h-[34px] w-[34px] items-center justify-center rounded-full border border-gray-300 bg-white text-gray-500 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700"
                        title="Clear type"
                    >
                        <svg
                            class="h-4 w-4"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2.5"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M6 18 18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                @endif
            </div>

            @if ($activeTab === 'requested')
                <!-- Status -->
                <div class="flex items-center gap-1.5">
                    <div class="relative w-36">
                        <select
                            wire:model.live="documentStatus"
                            class="h-10 w-full appearance-none rounded-full border border-gray-300 bg-white pl-3 pr-9 text-xs font-semibold text-gray-900 focus:outline-none focus:ring-0 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                        >
                            <option value="">Status</option>
                            <option value="pending">Pending</option>
                            <option value="accepted">Accepted</option>
                            <option value="rejected">Rejected</option>
                        </select>

                        <svg
                            class="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-500 dark:text-gray-400"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>

                    @if ($documentStatus)
                        <button
                            type="button"
                            wire:click="clearStatus"
                            class="flex h-[34px] w-[34px] items-center justify-center rounded-full border border-gray-300 bg-white text-gray-500 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700"
                            title="Clear status"
                        >
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2.5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M6 18 18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    @endif
                </div>
            @endif

        </div>
    </div>

    <!-- Render the data table below the custom tabs -->
       <!-- Render the data table below the custom tabs -->
    <div
        class="client-documents-table client-documents-table-{{ $activeTab }}"
        x-data
        x-init="$nextTick(() => {
            const row = $el.querySelector('.document-highlighted');

            if (row) {
                row.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                });
            }
        })"
    >
        {{ $this->table }}
    </div>

    <style>
        .client-documents-page .fi-ta {
            border-radius: 0;
            box-shadow: none;
        }

        .client-documents-page .fi-ta-ctn {
            border-radius: 0;
            border: 1px solid rgb(209 213 219);
            border-color: rgb(209 213 219);
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        }

        .client-documents-page .fi-ta-content {
            max-height: calc(100vh - 18rem);
            overflow: auto;
        }

        .client-documents-page .fi-ta-table {
            font-size: 0.75rem;
        }

        .client-documents-page .fi-ta-table th,
        .client-documents-page .fi-ta-table td {
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }

        .client-documents-page .fi-ta-table th:last-child {
            text-align: center;
            padding-left: 1rem;
            padding-right: 1rem;
        }

        .client-documents-page .fi-ta-table td:has(> .fi-ta-actions) {
            padding-left: 1rem;
            padding-right: 1rem;
            white-space: nowrap;
        }

        .client-documents-page .fi-ta-table td:has(> .fi-ta-actions) > .fi-ta-actions {
            display: flex !important;
            flex-wrap: nowrap !important;
            align-items: center !important;
            width: 100%;
            margin-left: 0;
            justify-content: center !important;
            gap: 0.5rem;
            white-space: nowrap;
        }

        .client-documents-page .fi-ta-table td:has(> .fi-ta-actions) > .fi-ta-actions > * {
            width: auto !important;
            min-width: 0 !important;
            flex: 0 0 auto !important;
            flex-shrink: 0 !important;
            margin: 0 !important;
        }

        .client-documents-page .fi-ta-cell-particulars > .fi-ta-col {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        .client-documents-page .documents-table-track-action {
            background-color: #6366f1 !important;
            color: #ffffff !important;
            box-shadow: 0 1px 2px rgb(0 0 0 / 0.08);
            text-decoration: none !important;
            text-decoration-line: none !important;
            transition: background-color 150ms ease-in-out, box-shadow 150ms ease-in-out;
        }

        .client-documents-page .documents-table-track-action:hover,
        .client-documents-page .documents-table-track-action:focus,
        .client-documents-page .documents-table-track-action:focus-visible,
        .client-documents-page .documents-table-track-action:active {
            background-color: #4f46e5 !important;
            color: #ffffff !important;
            box-shadow: 0 2px 5px rgb(0 0 0 / 0.12);
            text-decoration: none !important;
            text-decoration-line: none !important;
        }

        .client-documents-page .documents-table-track-action .fi-icon {
            color: #ffffff !important;
        }

        .dark .client-documents-page .documents-table-track-action {
            background-color: #6366f1 !important;
            color: #ffffff !important;
        }

        .dark .client-documents-page .documents-table-track-action:hover,
        .dark .client-documents-page .documents-table-track-action:focus,
        .dark .client-documents-page .documents-table-track-action:focus-visible,
        .dark .client-documents-page .documents-table-track-action:active {
            background-color: #818cf8 !important;
            color: #ffffff !important;
        }

        /* Keep descriptions on one line and reveal the full text through the
         * table tooltip when the truncated value is hovered. */
        .client-documents-page .fi-ta-cell-particulars > .fi-ta-col,
        .client-documents-page .fi-ta-cell-particulars .fi-ta-text,
        .client-documents-page .fi-ta-cell-particulars .fi-ta-text-item {
            min-width: 0 !important;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap !important;
        }

        .client-documents-page .client-documents-table-rejected .fi-ta-table th:first-child,
        .client-documents-page .client-documents-table-rejected .fi-ta-table td:first-child {
            padding-left: 2rem;
            padding-right: 2rem;
        }

        .client-documents-page .fi-ta-table th {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .client-documents-page .client-documents-table-all .fi-ta-table,
        .client-documents-page .client-documents-table-pending .fi-ta-table,
        .client-documents-page .client-documents-table-in_progress .fi-ta-table,
        .client-documents-page .client-documents-table-completed .fi-ta-table,
        .client-documents-page .client-documents-table-rejected .fi-ta-table {
            width: 100%;
            min-width: 0;
            table-layout: fixed;
        }

        /* Keep the standard document columns balanced across the table. */
        @media (min-width: 64rem) {
            .client-documents-page .client-documents-table-all .fi-ta-table th:nth-child(1),
            .client-documents-page .client-documents-table-all .fi-ta-table td:nth-child(1),
            .client-documents-page .client-documents-table-pending .fi-ta-table th:nth-child(1),
            .client-documents-page .client-documents-table-pending .fi-ta-table td:nth-child(1),
            .client-documents-page .client-documents-table-in_progress .fi-ta-table th:nth-child(1),
            .client-documents-page .client-documents-table-in_progress .fi-ta-table td:nth-child(1),
            .client-documents-page .client-documents-table-completed .fi-ta-table th:nth-child(1),
            .client-documents-page .client-documents-table-completed .fi-ta-table td:nth-child(1) {
                width: 16% !important;
            }

            .client-documents-page .client-documents-table-all .fi-ta-table th:nth-child(2),
            .client-documents-page .client-documents-table-all .fi-ta-table td:nth-child(2),
            .client-documents-page .client-documents-table-pending .fi-ta-table th:nth-child(2),
            .client-documents-page .client-documents-table-pending .fi-ta-table td:nth-child(2),
            .client-documents-page .client-documents-table-in_progress .fi-ta-table th:nth-child(2),
            .client-documents-page .client-documents-table-in_progress .fi-ta-table td:nth-child(2),
            .client-documents-page .client-documents-table-completed .fi-ta-table th:nth-child(2),
            .client-documents-page .client-documents-table-completed .fi-ta-table td:nth-child(2) {
                width: 15% !important;
            }

            .client-documents-page .client-documents-table-all .fi-ta-table th:nth-child(3),
            .client-documents-page .client-documents-table-all .fi-ta-table td:nth-child(3),
            .client-documents-page .client-documents-table-pending .fi-ta-table th:nth-child(3),
            .client-documents-page .client-documents-table-pending .fi-ta-table td:nth-child(3),
            .client-documents-page .client-documents-table-in_progress .fi-ta-table th:nth-child(3),
            .client-documents-page .client-documents-table-in_progress .fi-ta-table td:nth-child(3),
            .client-documents-page .client-documents-table-completed .fi-ta-table th:nth-child(3),
            .client-documents-page .client-documents-table-completed .fi-ta-table td:nth-child(3) {
                width: 22% !important;
            }

            .client-documents-page .client-documents-table-all .fi-ta-table th:nth-child(4),
            .client-documents-page .client-documents-table-all .fi-ta-table td:nth-child(4),
            .client-documents-page .client-documents-table-pending .fi-ta-table th:nth-child(4),
            .client-documents-page .client-documents-table-pending .fi-ta-table td:nth-child(4),
            .client-documents-page .client-documents-table-in_progress .fi-ta-table th:nth-child(4),
            .client-documents-page .client-documents-table-in_progress .fi-ta-table td:nth-child(4),
            .client-documents-page .client-documents-table-completed .fi-ta-table th:nth-child(4),
            .client-documents-page .client-documents-table-completed .fi-ta-table td:nth-child(4) {
                width: 20% !important;
            }

            .client-documents-page .client-documents-table-all .fi-ta-table th:nth-child(5),
            .client-documents-page .client-documents-table-all .fi-ta-table td:nth-child(5),
            .client-documents-page .client-documents-table-pending .fi-ta-table th:nth-child(5),
            .client-documents-page .client-documents-table-pending .fi-ta-table td:nth-child(5),
            .client-documents-page .client-documents-table-in_progress .fi-ta-table th:nth-child(5),
            .client-documents-page .client-documents-table-in_progress .fi-ta-table td:nth-child(5),
            .client-documents-page .client-documents-table-completed .fi-ta-table th:nth-child(5),
            .client-documents-page .client-documents-table-completed .fi-ta-table td:nth-child(5) {
                width: 15% !important;
            }

            .client-documents-page .client-documents-table-all .fi-ta-table th:nth-child(6),
            .client-documents-page .client-documents-table-all .fi-ta-table td:nth-child(6),
            .client-documents-page .client-documents-table-pending .fi-ta-table th:nth-child(6),
            .client-documents-page .client-documents-table-pending .fi-ta-table td:nth-child(6),
            .client-documents-page .client-documents-table-in_progress .fi-ta-table th:nth-child(6),
            .client-documents-page .client-documents-table-in_progress .fi-ta-table td:nth-child(6),
            .client-documents-page .client-documents-table-completed .fi-ta-table th:nth-child(6),
            .client-documents-page .client-documents-table-completed .fi-ta-table td:nth-child(6) {
                width: 12% !important;
            }

            .client-documents-page .client-documents-table-rejected .fi-ta-table .fi-ta-header-cell-document-type,
            .client-documents-page .client-documents-table-rejected .fi-ta-table .fi-ta-cell-document-type {
                width: 17% !important;
            }

            .client-documents-page .client-documents-table-rejected .fi-ta-table .fi-ta-header-cell-particulars,
            .client-documents-page .client-documents-table-rejected .fi-ta-table .fi-ta-cell-particulars {
                width: 31% !important;
            }

            .client-documents-page .client-documents-table-rejected .fi-ta-table .fi-ta-header-cell-created-at,
            .client-documents-page .client-documents-table-rejected .fi-ta-table .fi-ta-cell-created-at {
                width: 17% !important;
            }

            .client-documents-page .client-documents-table-rejected .fi-ta-table .fi-ta-header-cell-status,
            .client-documents-page .client-documents-table-rejected .fi-ta-table .fi-ta-cell-status {
                width: 14% !important;
            }

            .client-documents-page .client-documents-table-rejected .fi-ta-table .fi-ta-header-cell-rejection-reason,
            .client-documents-page .client-documents-table-rejected .fi-ta-table .fi-ta-cell-rejection-reason {
                width: 11% !important;
            }

            .client-documents-page .client-documents-table-rejected .fi-ta-table > thead > tr:last-child > th:last-child,
            .client-documents-page .client-documents-table-rejected .fi-ta-table > tbody > tr > td:last-child {
                width: 10% !important;
            }
        }

        .client-documents-page .client-documents-table-all .fi-ta-table th:nth-child(1),
        .client-documents-page .client-documents-table-all .fi-ta-table td:nth-child(1),
        .client-documents-page .client-documents-table-pending .fi-ta-table th:nth-child(1),
        .client-documents-page .client-documents-table-pending .fi-ta-table td:nth-child(1),
        .client-documents-page .client-documents-table-in_progress .fi-ta-table th:nth-child(1),
        .client-documents-page .client-documents-table-in_progress .fi-ta-table td:nth-child(1),
        .client-documents-page .client-documents-table-completed .fi-ta-table th:nth-child(1),
        .client-documents-page .client-documents-table-completed .fi-ta-table td:nth-child(1) {
            width: 16%;
        }

        .client-documents-page .client-documents-table-all .fi-ta-table th:nth-child(2),
        .client-documents-page .client-documents-table-all .fi-ta-table td:nth-child(2),
        .client-documents-page .client-documents-table-pending .fi-ta-table th:nth-child(2),
        .client-documents-page .client-documents-table-pending .fi-ta-table td:nth-child(2),
        .client-documents-page .client-documents-table-in_progress .fi-ta-table th:nth-child(2),
        .client-documents-page .client-documents-table-in_progress .fi-ta-table td:nth-child(2),
        .client-documents-page .client-documents-table-completed .fi-ta-table th:nth-child(2),
        .client-documents-page .client-documents-table-completed .fi-ta-table td:nth-child(2) {
            width: 15%;
        }

        .client-documents-page .client-documents-table-all .fi-ta-table th:nth-child(3),
        .client-documents-page .client-documents-table-all .fi-ta-table td:nth-child(3),
        .client-documents-page .client-documents-table-pending .fi-ta-table th:nth-child(3),
        .client-documents-page .client-documents-table-pending .fi-ta-table td:nth-child(3),
        .client-documents-page .client-documents-table-in_progress .fi-ta-table th:nth-child(3),
        .client-documents-page .client-documents-table-in_progress .fi-ta-table td:nth-child(3),
        .client-documents-page .client-documents-table-completed .fi-ta-table th:nth-child(3),
        .client-documents-page .client-documents-table-completed .fi-ta-table td:nth-child(3) {
            width: 22%;
        }

        .client-documents-page .client-documents-table-all .fi-ta-table th:nth-child(4),
        .client-documents-page .client-documents-table-all .fi-ta-table td:nth-child(4),
        .client-documents-page .client-documents-table-pending .fi-ta-table th:nth-child(4),
        .client-documents-page .client-documents-table-pending .fi-ta-table td:nth-child(4),
        .client-documents-page .client-documents-table-in_progress .fi-ta-table th:nth-child(4),
        .client-documents-page .client-documents-table-in_progress .fi-ta-table td:nth-child(4),
        .client-documents-page .client-documents-table-completed .fi-ta-table th:nth-child(4),
        .client-documents-page .client-documents-table-completed .fi-ta-table td:nth-child(4) {
            width: 20%;
        }

        .client-documents-page .client-documents-table-all .fi-ta-table th:nth-child(5),
        .client-documents-page .client-documents-table-all .fi-ta-table td:nth-child(5),
        .client-documents-page .client-documents-table-pending .fi-ta-table th:nth-child(5),
        .client-documents-page .client-documents-table-pending .fi-ta-table td:nth-child(5),
        .client-documents-page .client-documents-table-in_progress .fi-ta-table th:nth-child(5),
        .client-documents-page .client-documents-table-in_progress .fi-ta-table td:nth-child(5),
        .client-documents-page .client-documents-table-completed .fi-ta-table th:nth-child(5),
        .client-documents-page .client-documents-table-completed .fi-ta-table td:nth-child(5) {
            width: 15%;
        }

        .client-documents-page .client-documents-table-all .fi-ta-table th:nth-child(6),
        .client-documents-page .client-documents-table-all .fi-ta-table td:nth-child(6),
        .client-documents-page .client-documents-table-pending .fi-ta-table th:nth-child(6),
        .client-documents-page .client-documents-table-pending .fi-ta-table td:nth-child(6),
        .client-documents-page .client-documents-table-in_progress .fi-ta-table th:nth-child(6),
        .client-documents-page .client-documents-table-in_progress .fi-ta-table td:nth-child(6),
        .client-documents-page .client-documents-table-completed .fi-ta-table th:nth-child(6),
        .client-documents-page .client-documents-table-completed .fi-ta-table td:nth-child(6) {
            width: 12%;
        }

        .client-documents-page .fi-ta-cell.fi-align-center > .fi-ta-col {
            justify-content: center;
        }

        .client-documents-page .fi-ta-cell.fi-align-end > .fi-ta-col {
            justify-content: flex-end;
        }

        .client-documents-page .fi-ta-cell.fi-align-start > .fi-ta-col,
        .client-documents-page .fi-ta-cell.fi-align-left > .fi-ta-col {
            justify-content: flex-start;
        }

        .client-documents-page .fi-ta-table tbody tr {
            transition: background-color 150ms ease-in-out;
        }

        .client-documents-page .fi-ta-table tbody tr:hover {
            background-color: rgb(239 246 255);
        }

        .client-documents-page .client-documents-table-all .fi-ta-empty-state,
        .client-documents-page .client-documents-table-pending .fi-ta-empty-state,
        .client-documents-page .client-documents-table-in_progress .fi-ta-empty-state,
        .client-documents-page .client-documents-table-completed .fi-ta-empty-state {
            box-sizing: border-box;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 7rem;
            padding: 1rem 1.5rem;
        }

        .client-documents-page .client-documents-table-all .fi-ta-empty-state-content,
        .client-documents-page .client-documents-table-pending .fi-ta-empty-state-content,
        .client-documents-page .client-documents-table-in_progress .fi-ta-empty-state-content,
        .client-documents-page .client-documents-table-completed .fi-ta-empty-state-content {
            display: flex;
            max-width: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .client-documents-page .client-documents-table-all .fi-ta-empty-state-icon-bg,
        .client-documents-page .client-documents-table-pending .fi-ta-empty-state-icon-bg,
        .client-documents-page .client-documents-table-in_progress .fi-ta-empty-state-icon-bg,
        .client-documents-page .client-documents-table-completed .fi-ta-empty-state-icon-bg {
            flex-shrink: 0;
            margin-bottom: 0;
            padding: 0.5rem;
        }

        .dark .client-documents-page .fi-ta-ctn,
        .dark .client-documents-page .fi-ta-header,
        .dark .client-documents-page .fi-ta-content,
        .dark .client-documents-page .fi-ta-footer {
            border-color: rgb(75 85 99);
            background-color: transparent;
        }

        .dark .client-documents-page .fi-ta-table,
        .dark .client-documents-page .fi-ta-table thead,
        .dark .client-documents-page .fi-ta-table tbody,
        .dark .client-documents-page .fi-ta-table tfoot,
        .dark .client-documents-page .fi-ta-table tbody tr {
            background-color: transparent;
            border-color: rgb(75 85 99);
        }

        .dark .client-documents-page .fi-ta-table thead tr > th,
        .dark .client-documents-page .fi-ta-table tbody tr > td,
        .dark .client-documents-page .fi-ta-table tfoot tr > td {
            border-color: rgb(75 85 99);
            background-color: transparent;
            color: rgb(229 231 235);
        }

        .dark .client-documents-page .fi-ta-table tbody tr:hover > td {
            background-color: rgb(255 255 255 / 0.05);
        }

        .document-highlighted > td {
            background-color: rgb(243 244 246) !important;
        }

        .dark .document-highlighted > td {
            background-color: rgb(55 65 81) !important;
        }
    </style>

    </div>
</x-filament-panels::page>
