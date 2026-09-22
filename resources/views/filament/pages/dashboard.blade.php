<x-filament-panels::page>

    @php
        $stats = $this->getStats();
        $trend = $this->getProcessingTrend();
        $documents = $this->getRecentDocuments();
        $calendarCells = $this->getCalendarCells();
        $calendarLegend = $this->getCalendarEventLegend();
        $currentMonthLabel = $this->getCurrentMonthLabel();
        $upcomingDeadlines = $this->getUpcomingDeadlines();
        $upcomingEvents = $this->getUpcomingEvents();
        $calendarPage = new \App\Filament\Pages\Calendar;
        $reminders = $upcomingDeadlines->map(fn ($document) => [
            'title' => $document->particulars ?: 'Untitled Document',
            'date' => $document->deadline,
            'detail' => 'Deadline',
            'color' => '#6366f1',
            'sort' => $document->deadline->format('Y-m-d').' 23:59:59',
        ])->concat($upcomingEvents->map(fn ($event) => [
            'title' => $event->event,
            'date' => $event->date,
            'detail' => $event->time ? $event->time->format('g:i A') : null,
            'color' => $calendarPage->getEventColor($event),
            'sort' => $event->date->format('Y-m-d').' '.($event->time?->format('H:i:s') ?? '00:00:00'),
        ]))->sortBy('sort')->values();
    @endphp

    <div class="office-dashboard space-y-6" wire:poll.60s>

        {{-- ========================================================= --}}
        {{-- STATS --}}
        {{-- ========================================================= --}}

        <div class="dashboard-status-grid grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

            {{-- TOTAL DOCUMENTS --}}
            <a
                href="{{ \App\Filament\Pages\Document::getUrl() }}"
                wire:navigate
                class="dashboard-stat-card min-h-[176px] overflow-hidden rounded-xl border shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary-500"
            >
                <div class="dashboard-stat-main">
                    <div>
                        <p class="dashboard-stat-label text-[15px] font-medium text-gray-500 dark:text-gray-400">Total Documents</p>
                        <p class="dashboard-stat-value mt-2 text-4xl font-bold text-gray-950 dark:text-white">{{ number_format($stats['total']) }}</p>
                    </div>
                    @include('filament.pages.partials.stat-bars')
                </div>
                @include('filament.pages.partials.stat-trend', ['trend' => $stats['trends']['total']])
            </a>


            {{-- PENDING --}}
            <a
                href="{{ \App\Filament\Pages\Document::getUrl(['section' => 'pending']) }}"
                wire:navigate
                class="dashboard-stat-card min-h-[176px] overflow-hidden rounded-xl border shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary-500"
            >
                <div class="dashboard-stat-main">
                    <div>
                        <p class="dashboard-stat-label text-[15px] font-medium text-gray-500 dark:text-gray-400">Pending</p>
                        <p class="dashboard-stat-value mt-2 text-4xl font-bold text-gray-950 dark:text-white">{{ number_format($stats['pending']) }}</p>
                    </div>
                    @include('filament.pages.partials.stat-bars')
                </div>
                @include('filament.pages.partials.stat-trend', ['trend' => $stats['trends']['pending']])
            </a>


            {{-- ACTIVE --}}
            <a
                href="{{ \App\Filament\Pages\Document::getUrl(['section' => 'incoming']) }}"
                wire:navigate
                class="dashboard-stat-card min-h-[176px] overflow-hidden rounded-xl border shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary-500"
            >
                <div class="dashboard-stat-main">
                    <div>
                        <p class="dashboard-stat-label text-[15px] font-medium text-gray-500 dark:text-gray-400">Active</p>
                        <p class="dashboard-stat-value mt-2 text-4xl font-bold text-gray-950 dark:text-white">{{ number_format($stats['active']) }}</p>
                    </div>
                    @include('filament.pages.partials.stat-bars')
                </div>
                @include('filament.pages.partials.stat-trend', ['trend' => $stats['trends']['active']])
            </a>


            {{-- COMPLETED --}}
            <a
                href="{{ \App\Filament\Pages\Document::getUrl(['section' => 'completed']) }}"
                wire:navigate
                class="dashboard-stat-card min-h-[176px] overflow-hidden rounded-xl border shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary-500"
            >
                <div class="dashboard-stat-main">
                    <div>
                        <p class="dashboard-stat-label text-[15px] font-medium text-gray-500 dark:text-gray-400">Completed</p>
                        <p class="dashboard-stat-value mt-2 text-4xl font-bold text-gray-950 dark:text-white">{{ number_format($stats['completed']) }}</p>
                    </div>
                    @include('filament.pages.partials.stat-bars')
                </div>
                @include('filament.pages.partials.stat-trend', ['trend' => $stats['trends']['completed']])
            </a>

        </div>


        <div class="dashboard-main-grid grid grid-cols-1 gap-6 xl:grid-cols-4 xl:gap-4">
            <div class="dashboard-top-row contents">
            <div class="dashboard-graph-panel min-w-0 xl:col-span-2">
                @include('filament.pages.partials.processing-trend', ['trend' => $trend])
            </div>
            <section class="dashboard-reminders-panel dashboard-work-panel min-w-0 w-full rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900" aria-labelledby="dashboard-reminders-title">
                <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <h2 id="dashboard-reminders-title" class="text-base font-semibold text-[#0F172A] dark:text-white">Reminders</h2>
                    <a
                        href="{{ \App\Filament\Pages\Calendar::getUrl(['date' => now()->toDateString()]) }}"
                        wire:navigate
                        class="shrink-0 text-xs font-semibold text-[#6366F1] transition hover:text-[#4F46E5] hover:underline dark:text-[#6366F1] dark:hover:text-[#4F46E5]"
                    >
                        View All
                    </a>
                </div>
                <div class="space-y-2 p-5">
                    @forelse ($reminders as $reminder)
                        <div class="flex min-w-0 items-center gap-4 rounded-lg px-3 py-3 transition hover:bg-gray-50 dark:hover:bg-gray-800">
                            <div class="flex h-11 w-11 shrink-0 flex-col items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-800">
                                <span class="text-[10px] font-semibold uppercase text-gray-500">{{ $reminder['date']->format('M') }}</span>
                                <span class="text-sm font-bold text-gray-950 dark:text-white">{{ $reminder['date']->format('d') }}</span>
                            </div>
                            <div class="min-w-0">
                                <div class="flex min-w-0 items-center gap-2">
                                    <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $reminder['color'] }}"></span>
                                    <p class="truncate text-sm font-medium text-gray-950 dark:text-white" title="{{ $reminder['title'] }}">{{ $reminder['title'] }}</p>
                                </div>
                                @if ($reminder['detail'])
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $reminder['detail'] }}</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="py-8 text-center text-sm text-gray-500">No upcoming reminders.</p>
                    @endforelse
                </div>
            </section>
            </div>

        {{-- ========================================================= --}}
        {{-- SEARCH / FILTER --}}
        {{-- ========================================================= --}}

        {{-- ========================================================= --}}
        {{-- RECENT DOCUMENTS + CALENDAR --}}
        {{-- ========================================================= --}}

        <div class="dashboard-bottom-row contents">

            {{-- ===================================================== --}}
            {{-- RECENT DOCUMENTS --}}
            {{-- ===================================================== --}}

            <div class="dashboard-recent-panel dashboard-work-panel min-w-0 xl:col-span-2">

                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold text-[#0F172A] dark:text-white">
                        Recent Documents
                    </h2>
                    <a
                        href="{{ \App\Filament\Pages\Document::getUrl() }}"
                        wire:navigate
                        class="shrink-0 text-xs font-semibold text-[#6366F1] transition hover:text-[#4F46E5] hover:underline dark:text-[#6366F1] dark:hover:text-[#4F46E5]"
                    >
                        View All
                    </a>
                </div>


        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">

            {{-- SEARCH --}}
            <div class="relative w-full sm:max-w-md sm:flex-none">
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
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search for documents..."
                    class="block w-full rounded-full border border-gray-300 bg-white py-2.5 pl-11 pr-4 text-sm
                           text-gray-900 placeholder:text-gray-400 focus:border-transparent focus:outline-none
                           focus:ring-2 focus:ring-[#6366F1] dark:border-gray-600 dark:bg-gray-800
                           dark:text-gray-100 dark:placeholder:text-gray-400"
                >
            </div>


            {{-- DOCUMENT TYPE FILTER --}}
            <div class="flex w-full items-center gap-1.5 sm:w-auto">
                <div class="relative w-full sm:w-48">
                    <select
                        wire:model.live="documentTypeFilter"
                        class="h-10 w-full appearance-none rounded-full border border-gray-300 bg-white py-2 pl-3 pr-9 text-xs font-semibold text-gray-900 focus:outline-none focus:ring-0 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                    >
                        <option value="">Document Type</option>
                        @foreach (\App\Models\DocumentType::orderBy('type_name')->get() as $type)
                            <option value="{{ $type->type_name }}">{{ $type->type_name }}</option>
                        @endforeach
                    </select>
                    <svg
                        class="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-500 dark:text-gray-400"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </div>
                @if ($documentTypeFilter)
                    <button
                        type="button"
                        wire:click="clearDocumentTypeFilter"
                        class="flex h-[34px] w-[34px] shrink-0 items-center justify-center rounded-full border border-gray-300 bg-white text-gray-500 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700"
                        title="Clear document type"
                        aria-label="Clear document type filter"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                @endif
            </div>

            {{-- STATUS FILTER --}}
            <div class="flex w-full items-center gap-1.5 sm:w-auto">
                <div class="relative w-full sm:w-48">
                    <select
                        wire:model.live="statusFilter"
                        class="h-10 w-full appearance-none rounded-full border border-gray-300 bg-white py-2 pl-3 pr-9 text-xs font-semibold text-gray-900 focus:outline-none focus:ring-0 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"
                    >
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="in_progress">Active</option>
                        <option value="completed">Completed</option>
                        <option value="returned">Returned</option>
                        <option value="archived">Archived</option>
                        <option value="outgoing">Outgoing</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    <svg
                        class="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-500 dark:text-gray-400"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </div>
                @if ($statusFilter)
                    <button
                        type="button"
                        wire:click="clearStatusFilter"
                        class="flex h-[34px] w-[34px] shrink-0 items-center justify-center rounded-full border border-gray-300 bg-white text-gray-500 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700"
                        title="Clear status"
                        aria-label="Clear status filter"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                @endif
            </div>

        </div>


                @if ($documents->isNotEmpty())

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">

                        @foreach ($documents as $document)

                            @php
                                $filePath = (string) $document->latestVersion?->file_path;
                                $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                                $documentRouteKey = $document->getPublicRouteKey();
                                $thumbnailUrl = $filePath && in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'pdf'], true)
                                    ? route('admin.documents.thumbnail', ['document' => $documentRouteKey])
                                    : null;
                                $documentPreviewUrl = $filePath && in_array($extension, ['doc', 'docx'], true)
                                    ? route('admin.documents.preview', ['document' => $documentRouteKey])
                                    : null;

                                $statusLabel = match ($document->status) {
                                    'in_progress' => 'Incoming',
                                    'outgoing' => 'Outgoing',
                                    default => ucwords(str_replace('_', ' ', (string) $document->status)),
                                };

                                $statusClasses = match ($document->status) {
                                    'pending' => 'border-yellow-200 bg-yellow-50 text-yellow-700 dark:border-yellow-800 dark:bg-yellow-950 dark:text-yellow-300',
                                    'in_progress' => 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-300',
                                    'outgoing' => 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-800 dark:bg-violet-950 dark:text-violet-300',
                                    'completed' => 'border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-950 dark:text-green-300',
                                    'returned' => 'border-orange-200 bg-orange-50 text-orange-700 dark:border-orange-800 dark:bg-orange-950 dark:text-orange-300',
                                    'archived' => 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
                                    'rejected' => 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300',
                                    default => 'border-gray-200 bg-gray-50 text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                };
                            @endphp


                            <a
                                href="{{ \App\Filament\Pages\ViewDocument::getUrl(['document' => $document->public_id, 'return_to' => \App\Filament\Pages\Dashboard::getUrl()]) }}"
                                class="group relative flex aspect-square min-w-0 flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:border-[#6366F1] hover:shadow-lg dark:border-gray-700 dark:bg-[#17181c] dark:hover:border-indigo-400"
                            >


                                {{-- DOCUMENT PREVIEW --}}

                                <div class="dashboard-document-preview relative aspect-video shrink-0 overflow-hidden border-b border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-900">

                                    @if (auth()->user()->hasRole('Super Admin'))

                                        {{-- Super Admin: Preview restricted --}}
                                        <div class="flex h-full w-full flex-col items-center justify-center gap-2 p-4 text-center">

                                            <x-heroicon-o-lock-closed
                                                class="h-8 w-8 text-gray-400"
                                            />

                                            <p class="text-sm font-semibold text-gray-600 dark:text-gray-300">
                                                Preview Restricted
                                            </p>

                                        </div>

                                    @elseif (
                                        $document->latestVersion?->file_path &&
                                        in_array(
                                            strtolower(pathinfo($document->latestVersion->file_path, PATHINFO_EXTENSION)),
                                            ['pdf', 'doc', 'docx'],
                                            true
                                        ) &&
                                        !empty($thumbnailUrl)
                                    )

                                        {{-- Thumbnail preview --}}
                                        <img
                                            src="{{ $thumbnailUrl }}"
                                            alt="{{ $document->particulars ?: 'Document preview' }}"
                                            class="h-full w-full object-cover object-top"
                                            loading="lazy"
                                            draggable="false"
                                        >

                                    @elseif (
                                        $document->latestVersion?->file_path &&
                                        in_array(
                                            strtolower(pathinfo($document->latestVersion->file_path, PATHINFO_EXTENSION)),
                                            ['pdf', 'doc', 'docx'],
                                            true
                                        ) &&
                                        !empty($documentPreviewUrl)
                                    )

                                        {{-- Iframe preview --}}
                                        <iframe
                                            src="{{ $documentPreviewUrl }}"
                                            title="{{ $document->particulars ?: 'Document preview' }}"
                                            class="pointer-events-none h-full w-full border-0"
                                            loading="lazy"
                                        ></iframe>

                                    @else

                                        {{-- Fallback document icon --}}
                                        <div class="flex h-full items-center justify-center text-gray-400 dark:text-gray-500">

                                            <svg
                                                class="h-16 w-16"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                                stroke-width="1.25"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-1.125 1.125-1.125V9.75M10.5 2.25V7.125c0 .621.504 1.125 1.125 1.125h4.875"
                                                />
                                            </svg>

                                        </div>

                                    @endif

                                </div>


                                {{-- DOCUMENT DETAILS --}}
                                <div class="flex min-h-0 flex-1 flex-col p-3">
                                    <h3 class="line-clamp-2 text-sm font-bold text-gray-900 dark:text-gray-100">
                                        {{ $document->particulars ?: $document->description ?: 'Untitled document' }}
                                    </h3>

                                    <p class="mt-1 text-xs font-medium text-gray-500 dark:text-gray-400">
                                        {{ $document->lao_number ?: '—' }}
                                    </p>

                                    <p class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400">
                                        {{ $document->office_unit ?: 'No office specified' }}
                                    </p>

                                    <div class="mt-auto flex items-center justify-between gap-2 pt-3">
                                        <span class="inline-flex w-fit shrink-0 items-center whitespace-nowrap rounded-lg border px-3 py-1 text-xs font-semibold {{ $statusClasses }}">
                                            {{ $statusLabel }}
                                        </span>
                                        <span class="whitespace-nowrap text-right text-xs text-gray-400 dark:text-gray-500">
                                            {{ $document->updated_at?->diffForHumans() }}
                                        </span>
                                    </div>
                                </div>

                            </a>

                        @endforeach

                    </div>


                @else

                    <div
                        class="rounded-2xl border border-gray-200 bg-white px-6 py-12 text-center shadow-sm
                               dark:border-gray-700 dark:bg-[#17181c]"
                    >

                        <svg
                            class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.5"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V9.75M10.5 2.25V7.125c0 .621.504 1.125 1.125 1.125h4.875"
                            />
                        </svg>

                        <p class="mt-3 text-sm font-medium text-gray-500 dark:text-gray-400">
                            No documents found
                        </p>

                    </div>

                @endif

            </div>


            {{-- ===================================================== --}}
            {{-- CALENDAR --}}
            {{-- ===================================================== --}}

            <div class="dashboard-calendar-panel min-w-0 w-full">

                <div
                    class="dashboard-work-panel dashboard-calendar-card w-full overflow-hidden rounded-xl border border-gray-200 bg-white p-4 shadow-sm
                           dark:border-gray-700 dark:bg-gray-900"
                >

                    {{-- MONTH HEADER --}}
                    <div class="mb-3 flex items-center justify-between gap-3">

                        <button
                            wire:click="previousMonth"
                            type="button"
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg
                                   text-gray-500 transition hover:bg-gray-100 hover:text-gray-950
                                   dark:hover:bg-gray-800 dark:hover:text-white"
                            title="Previous month"
                        >
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m15 18-6-6 6-6"
                                />
                            </svg>
                        </button>


                        <p class="min-w-0 truncate text-sm font-semibold text-gray-950 dark:text-white">
                            {{ $currentMonthLabel }}
                        </p>


                        <button
                            wire:click="nextMonth"
                            type="button"
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg
                                   text-gray-500 transition hover:bg-gray-100 hover:text-gray-950
                                   dark:hover:bg-gray-800 dark:hover:text-white"
                            title="Next month"
                        >
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="m9 18 6-6-6-6"
                                />
                            </svg>
                        </button>

                    </div>


                    {{-- WEEKDAY HEADER --}}
                    <div class="grid grid-cols-7 gap-1">

                        @foreach (['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'] as $weekday)

                            <div
                                class="flex h-7 min-w-0 items-center justify-center
                                       text-[11px] font-medium text-gray-400"
                            >
                                {{ $weekday }}
                            </div>

                        @endforeach

                    </div>


                    {{-- CALENDAR DAYS --}}
                    <div class="mt-1 grid grid-cols-7 gap-1">

                        @foreach ($calendarCells as $cell)

                            <a
                                href="{{ \App\Filament\Pages\Calendar::getUrl(['date' => $cell['date']]) }}"
                                aria-label="{{ $cell['eventCount'] > 0 ? 'View '.$cell['eventCount'].' events on '.$cell['date'] : 'View calendar on '.$cell['date'] }}"
                                @class([
                                    /*
                                     * Fixed height prevents cells
                                     * from overlapping one another.
                                     */
                                    'relative flex h-10 min-w-0 flex-col items-center justify-center gap-1 rounded-lg text-sm transition',

                                    /*
                                     * TODAY
                                     */
                                    'bg-indigo-50 font-semibold text-indigo-800 ring-1 ring-inset ring-indigo-200 dark:bg-indigo-400/20 dark:text-indigo-200 dark:ring-indigo-300/30'
                                        => $cell['isToday'],

                                    /*
                                     * CURRENT MONTH + EVENT
                                     */
                                    'font-semibold'
                                        => !$cell['isToday'] && $cell['hasEvent'] && $cell['isCurrentMonth'],

                                    /*
                                     * NORMAL DAY
                                     */
                                    'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800'
                                        => !$cell['isToday']
                                            && $cell['isCurrentMonth'],

                                    /*
                                     * PREVIOUS / NEXT MONTH
                                     */
                                    'text-gray-300 dark:text-gray-700'
                                        => !$cell['isCurrentMonth']
                                            && !$cell['isToday'],
                                ])
                            >
                                <span class="leading-none">{{ $cell['day'] }}</span>

                                <span class="flex h-1.5 items-center justify-center gap-0.5" aria-hidden="true">
                                    @foreach ($cell['eventColors'] as $eventColor)
                                        <span
                                            @class([
                                                'h-1.5 w-1.5 rounded-full',
                                                'ring-1 ring-white dark:ring-gray-900' => $cell['isToday'],
                                            ])
                                            style="background-color: {{ $eventColor }}"
                                        ></span>
                                    @endforeach

                                    @if ($cell['eventCount'] > 2)
                                        <span class="text-[7px] font-semibold leading-none">+{{ $cell['eventCount'] - 2 }}</span>
                                    @endif
                                </span>
                            </a>

                        @endforeach

                    </div>


                    {{-- EVENT CATEGORY LEGEND --}}
                    <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-2 border-t border-gray-100 pt-3 dark:border-gray-800">

                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded bg-indigo-200 dark:bg-indigo-400/40"></span>

                            <span class="text-[10px] text-gray-500 dark:text-gray-400">
                                Today
                            </span>
                        </div>

                        @foreach ($calendarLegend as $legendItem)
                            <div class="flex items-center gap-1.5">
                                <span
                                    class="h-2 w-2 rounded-full"
                                    style="background-color: {{ $legendItem['color'] }}"
                                ></span>

                                <span class="text-[10px] text-gray-500 dark:text-gray-400">
                                    {{ $legendItem['label'] }}
                                </span>
                            </div>
                        @endforeach

                    </div>

                </div>

            </div>

        </div>
        </div>

    </div>

</x-filament-panels::page>
