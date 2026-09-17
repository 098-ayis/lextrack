<x-filament-panels::page>

    @php
        $stats = $this->getStats();
        $trend = $this->getProcessingTrend();
        $documents = $this->getRecentDocuments();
        $calendarCells = $this->getCalendarCells();
        $currentMonthLabel = $this->getCurrentMonthLabel();
        $upcomingDeadlines = $this->getUpcomingDeadlines();
        $upcomingEvents = $this->getUpcomingEvents();
        $reminders = $upcomingDeadlines->map(fn ($document) => [
            'title' => $document->particulars ?: 'Untitled Document',
            'date' => $document->deadline,
            'detail' => 'Deadline',
            'sort' => $document->deadline->format('Y-m-d').' 23:59:59',
        ])->concat($upcomingEvents->map(fn ($event) => [
            'title' => $event->event,
            'date' => $event->date,
            'detail' => $event->time ? $event->time->format('g:i A') : null,
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
                class="block rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-gray-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
            >
                <div class="flex items-center justify-between gap-4">

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Total Documents
                        </p>

                        <p class="mt-2 text-3xl font-bold text-gray-950 dark:text-white">
                            {{ number_format($stats['total']) }}
                        </p>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-800">
                        <svg
                            class="h-5 w-5 text-gray-600 dark:text-gray-300"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5V5.25A2.25 2.25 0 0 0 12.375 3h-6.75A2.25 2.25 0 0 0 3.375 5.25v13.5A2.25 2.25 0 0 0 5.625 21h12.75a2.25 2.25 0 0 0 2.25-2.25v-4.5Z"
                            />
                        </svg>
                    </div>

                </div>
            </a>


            {{-- PENDING --}}
            <a
                href="{{ \App\Filament\Pages\Document::getUrl(['section' => 'pending']) }}"
                wire:navigate
                class="block rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-gray-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
            >
                <div class="flex items-center justify-between gap-4">

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Pending
                        </p>

                        <p class="mt-2 text-3xl font-bold text-gray-950 dark:text-white">
                            {{ number_format($stats['pending']) }}
                        </p>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-violet-50 dark:bg-violet-500/10">
                        <svg
                            class="h-5 w-5 text-violet-500"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                            />
                        </svg>
                    </div>

                </div>
            </a>


            {{-- ACTIVE --}}
            <a
                href="{{ \App\Filament\Pages\Document::getUrl(['section' => 'incoming']) }}"
                wire:navigate
                class="block rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-gray-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
            >
                <div class="flex items-center justify-between gap-4">

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Active
                        </p>

                        <p class="mt-2 text-3xl font-bold text-gray-950 dark:text-white">
                            {{ number_format($stats['active']) }}
                        </p>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-500/10">
                        <svg
                            class="h-5 w-5 text-blue-600"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2.8"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M3 17 9 11l4 4 8-8M15 7h6v6"
                            />
                        </svg>
                    </div>

                </div>
            </a>


            {{-- COMPLETED --}}
            <a
                href="{{ \App\Filament\Pages\Document::getUrl(['section' => 'completed']) }}"
                wire:navigate
                class="block rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-gray-300 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
            >
                <div class="flex items-center justify-between gap-4">

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Completed
                        </p>

                        <p class="mt-2 text-3xl font-bold text-gray-950 dark:text-white">
                            {{ number_format($stats['completed']) }}
                        </p>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-500/10">
                        <svg
                            class="h-5 w-5 text-emerald-600"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m4.5 12.75 6 6 9-13.5"
                            />
                        </svg>
                    </div>

                </div>
            </a>

        </div>


        <div class="dashboard-main-grid grid grid-cols-1 gap-6 xl:grid-cols-3">

        <div class="dashboard-top-row grid grid-cols-1 items-stretch gap-6 xl:grid-cols-3">
            <div class="dashboard-graph-panel min-w-0 xl:col-span-2">
                <div class="dashboard-section-heading mb-4">
                    <h2 class="text-lg font-semibold text-violet-800 dark:text-violet-300">
                        Document Activity
                    </h2>
                </div>
                @include('filament.pages.partials.processing-trend', ['trend' => $trend])
            </div>
            <section class="dashboard-reminders-panel dashboard-work-panel min-w-0 rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900" aria-labelledby="dashboard-reminders-title">
                <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <h2 id="dashboard-reminders-title" class="text-base font-semibold text-violet-800 dark:text-violet-300">Reminders</h2>
                    <a
                        href="{{ \App\Filament\Pages\Calendar::getUrl(['date' => now()->toDateString()]) }}"
                        wire:navigate
                        class="shrink-0 text-xs font-semibold text-violet-700 transition hover:text-violet-900 hover:underline dark:text-violet-300 dark:hover:text-violet-200"
                    >
                        Show All
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
                                <p class="truncate text-sm font-medium text-gray-950 dark:text-white" title="{{ $reminder['title'] }}">{{ $reminder['title'] }}</p>
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

        <div class="dashboard-bottom-row grid grid-cols-1 gap-6 xl:grid-cols-3">

            {{-- ===================================================== --}}
            {{-- RECENT DOCUMENTS --}}
            {{-- ===================================================== --}}

            <div class="dashboard-recent-panel dashboard-work-panel min-w-0 xl:col-span-2">

                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold text-violet-800 dark:text-violet-300">
                        Recent Documents
                    </h2>
                    <a
                        href="{{ \App\Filament\Pages\Document::getUrl() }}"
                        wire:navigate
                        class="shrink-0 text-xs font-semibold text-violet-700 transition hover:text-violet-900 hover:underline dark:text-violet-300 dark:hover:text-violet-200"
                    >
                        View All
                    </a>
                </div>


        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">

            {{-- SEARCH --}}
            <div class="relative w-full sm:max-w-md">

                <svg
                    class="pointer-events-none absolute left-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="m21 21-4.35-4.35m2.1-5.4a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z"
                    />
                </svg>

                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search documents..."
                    class="block w-full rounded-lg border-gray-300 bg-white py-2.5 pl-11 pr-4 text-sm text-gray-950 shadow-sm
                           focus:border-primary-500 focus:ring-primary-500
                           dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                >

            </div>


            {{-- FILTER --}}
            <div class="w-full sm:w-48">

                <select
                    wire:model.live="statusFilter"
                    class="block w-full rounded-lg border-gray-300 bg-white py-2.5
                        text-sm text-gray-950 shadow-sm
                        focus:border-primary-500 focus:ring-primary-500
                        dark:border-gray-700 dark:bg-gray-900 dark:text-white"
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

            </div>

        </div>


                @if ($documents->isNotEmpty())

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">

                        @foreach ($documents as $document)

                            @php
                                $badgeColor = match ($document->status) {
                                    'pending' => 'primary',
                                    'in_progress' => 'info',
                                    'completed' => 'success',
                                    'outgoing' => 'primary',
                                    default => 'gray',
                                };
                            @endphp


                            <a
                                href="{{ \App\Filament\Pages\ViewDocument::getUrl(['document' => $document->document_id]) }}"
                                wire:navigate
                                class="group min-w-0 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm
                                       transition hover:border-gray-300 hover:shadow-md
                                       dark:border-gray-700 dark:bg-gray-900"
                            >

                                {{-- DOCUMENT PREVIEW --}}
                                <div class="dashboard-document-preview flex h-32 items-start justify-start overflow-hidden bg-gray-50 dark:bg-gray-800">

                                    @if (
                                        $document->latestVersion?->file_path &&
                                        in_array(strtolower(pathinfo($document->latestVersion->file_path, PATHINFO_EXTENSION)), ['pdf', 'doc', 'docx'], true)
                                    )

                                        <iframe
                                            src="{{ route('admin.documents.preview', ['document' => $document->document_id]) }}#toolbar=0"
                                            scrolling="no"
                                            class="dashboard-document-preview-frame pointer-events-none shrink-0 border-0"
                                            title="Document preview"
                                        ></iframe>

                                    @else

                                        <div class="flex flex-col items-center justify-center gap-2">

                                            <svg
                                                class="h-8 w-8 text-gray-300 dark:text-gray-600"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                                stroke-width="1.5"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5V5.25A2.25 2.25 0 0 0 12.375 3h-6.75A2.25 2.25 0 0 0 3.375 5.25v13.5A2.25 2.25 0 0 0 5.625 21h12.75a2.25 2.25 0 0 0 2.25-2.25v-4.5Z"
                                                />
                                            </svg>

                                            <span class="text-xs text-gray-400">
                                                Document
                                            </span>

                                        </div>

                                    @endif

                                </div>


                                {{-- DOCUMENT DETAILS --}}
                                <div class="min-w-0 p-3">

                                    <p class="line-clamp-2 text-sm font-semibold text-gray-950 dark:text-white">
                                        {{ $document->particulars ?: 'Untitled Document' }}
                                    </p>


                                    @if ($document->lao_number)

                                        <p class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400">
                                            {{ $document->lao_number }}
                                        </p>

                                    @endif


                                    <p class="mt-2 truncate text-xs text-gray-500 dark:text-gray-400">
                                        {{ $document->office_unit ?: 'No office specified' }}
                                    </p>


                                    <div class="mt-3 flex items-center justify-between gap-2">

                                        <x-filament::badge :color="$badgeColor">
                                            {{ ucwords(str_replace('_', ' ', $document->status ?? '')) }}
                                        </x-filament::badge>


                                        @if ($document->updated_at)

                                            <span class="truncate text-xs text-gray-400">
                                                {{ $document->updated_at->diffForHumans() }}
                                            </span>

                                        @endif

                                    </div>

                                </div>

                            </a>

                        @endforeach

                    </div>


                @else

                    <div
                        class="flex min-h-64 flex-col items-center justify-center rounded-xl
                               border border-dashed border-gray-300 bg-white px-6 py-10 text-center
                               dark:border-gray-700 dark:bg-gray-900"
                    >

                        <svg
                            class="mb-3 h-8 w-8 text-gray-300 dark:text-gray-600"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.5"
                                d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5V5.25A2.25 2.25 0 0 0 12.375 3h-6.75A2.25 2.25 0 0 0 3.375 5.25v13.5A2.25 2.25 0 0 0 5.625 21h12.75a2.25 2.25 0 0 0 2.25-2.25v-4.5Z"
                            />
                        </svg>

                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            No documents found
                        </p>

                        <p class="mt-1 text-xs text-gray-500">
                            Documents will appear here when available.
                        </p>

                    </div>

                @endif

            </div>


            {{-- ===================================================== --}}
            {{-- CALENDAR --}}
            {{-- ===================================================== --}}

            <div class="dashboard-calendar-panel min-w-0">

                <div class="mb-4">
                    <h2 class="text-lg font-semibold text-violet-800 dark:text-violet-300">
                        <a href="{{ \App\Filament\Pages\Calendar::getUrl(['date' => sprintf('%04d-%02d-01', $year, $month)]) }}" wire:navigate class="hover:underline">My Calendar</a>
                    </h2>

                </div>


                <div
                    class="dashboard-work-panel dashboard-calendar-card overflow-hidden rounded-xl border border-gray-200 bg-white p-4 shadow-sm
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
                                aria-label="View events on {{ $cell['date'] }}"
                                @class([
                                    /*
                                     * Fixed height prevents cells
                                     * from overlapping one another.
                                     */
                                    'relative flex h-8 min-w-0 items-center justify-center rounded-lg text-sm transition',

                                    /*
                                     * TODAY
                                     */
                                    'bg-violet-500 font-semibold text-white'
                                        => $cell['isToday'],

                                    /*
                                     * CURRENT MONTH + EVENT
                                     *
                                     * No dots / labels.
                                     * Day number only.
                                     */
                                    'bg-violet-50 font-semibold text-violet-700 ring-1 ring-inset ring-violet-200 dark:bg-violet-500/10 dark:text-violet-300 dark:ring-violet-500/30'
                                        => !$cell['isToday']
                                            && $cell['hasEvent']
                                            && $cell['date'] >= now()->toDateString()
                                            && $cell['isCurrentMonth'],

                                    /*
                                     * NORMAL DAY
                                     */
                                    'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800'
                                        => !$cell['isToday']
                                            && (!$cell['hasEvent'] || $cell['date'] < now()->toDateString())
                                            && $cell['isCurrentMonth'],

                                    /*
                                     * PREVIOUS / NEXT MONTH
                                     */
                                    'text-gray-300 dark:text-gray-700'
                                        => !$cell['isCurrentMonth']
                                            && !$cell['isToday'],
                                ])
                            >
                                {{ $cell['day'] }}
                            </a>

                        @endforeach

                    </div>


                    {{-- SIMPLE LEGEND --}}
                    <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-gray-100 pt-3 dark:border-gray-800">

                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded bg-violet-500"></span>

                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                Today
                            </span>
                        </div>


                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded bg-violet-100 ring-1 ring-violet-200 dark:bg-violet-500/20 dark:ring-violet-500/40"></span>

                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                Event
                            </span>
                        </div>

                    </div>

                </div>

            </div>

        </div>

        </div>



    </div>

</x-filament-panels::page>
