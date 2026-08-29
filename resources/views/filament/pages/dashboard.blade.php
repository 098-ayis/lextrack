<x-filament-panels::page>

    @php
        $stats = $this->getStats();
        $documents = $this->getRecentDocuments();
        $calendarCells = $this->getCalendarCells();
        $currentMonthLabel = $this->getCurrentMonthLabel();
        $upcomingDeadlines = $this->getUpcomingDeadlines();
        $upcomingEvents = $this->getUpcomingEvents();
    @endphp

    <div class="space-y-6">

        {{-- ========================================================= --}}
        {{-- STATS --}}
        {{-- ========================================================= --}}

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

            {{-- TOTAL DOCUMENTS --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
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
            </div>


            {{-- PENDING --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center justify-between gap-4">

                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Pending
                        </p>

                        <p class="mt-2 text-3xl font-bold text-gray-950 dark:text-white">
                            {{ number_format($stats['pending']) }}
                        </p>
                    </div>

                    <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-amber-50 dark:bg-amber-500/10">
                        <svg
                            class="h-5 w-5 text-amber-600"
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
            </div>


            {{-- ACTIVE --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
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
                            stroke-width="1.8"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                            />
                        </svg>
                    </div>

                </div>
            </div>


            {{-- COMPLETED --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
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
            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- SEARCH / FILTER --}}
        {{-- ========================================================= --}}

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


        {{-- ========================================================= --}}
        {{-- RECENT DOCUMENTS + CALENDAR --}}
        {{-- ========================================================= --}}

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

            {{-- ===================================================== --}}
            {{-- RECENT DOCUMENTS --}}
            {{-- ===================================================== --}}

            <div class="min-w-0 xl:col-span-2">

                <div class="mb-4">
                    <h2 class="text-lg font-semibold text-gray-950 dark:text-white">
                        Recent Documents
                    </h2>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Recently updated documents.
                    </p>
                </div>


                @if ($documents->isNotEmpty())

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">

                        @foreach ($documents as $document)

                            @php
                                $badgeColor = match ($document->status) {
                                    'pending' => 'warning',
                                    'in_progress' => 'info',
                                    'completed' => 'success',
                                    'outgoing' => 'primary',
                                    default => 'gray',
                                };
                            @endphp


                            <a
                                href="{{ url('/admin/documents/' . $document->document_id) }}"
                                class="group min-w-0 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm
                                       transition hover:border-gray-300 hover:shadow-md
                                       dark:border-gray-700 dark:bg-gray-900"
                            >

                                {{-- DOCUMENT PREVIEW --}}
                                <div class="flex h-40 items-center justify-center overflow-hidden bg-gray-50 dark:bg-gray-800">

                                    @if (
                                        $document->file_path &&
                                        strtolower(pathinfo($document->file_path, PATHINFO_EXTENSION)) === 'pdf'
                                    )

                                        <iframe
                                            src="{{ \Illuminate\Support\Facades\Storage::url($document->file_path) }}#toolbar=0"
                                            class="pointer-events-none h-full w-full border-0"
                                            title="Document preview"
                                        ></iframe>

                                    @else

                                        <div class="flex flex-col items-center justify-center gap-2">

                                            <svg
                                                class="h-10 w-10 text-gray-300 dark:text-gray-600"
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
                                <div class="min-w-0 p-4">

                                    <p class="line-clamp-2 font-semibold text-gray-950 dark:text-white">
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


                                    <div class="mt-4 flex items-center justify-between gap-2">

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

            <div class="min-w-0">

                <div class="mb-4">
                    <h2 class="text-lg font-semibold text-gray-950 dark:text-white">
                        My Calendar
                    </h2>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Scheduled events and reminders.
                    </p>
                </div>


                <div
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white p-5 shadow-sm
                           dark:border-gray-700 dark:bg-gray-900"
                >

                    {{-- MONTH HEADER --}}
                    <div class="mb-5 flex items-center justify-between gap-3">

                        <button
                            wire:click="previousMonth"
                            type="button"
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg
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
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg
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

                            <div
                                @class([
                                    /*
                                     * Fixed height prevents cells
                                     * from overlapping one another.
                                     */
                                    'flex h-10 min-w-0 items-center justify-center rounded-lg text-sm transition',

                                    /*
                                     * TODAY
                                     */
                                    'bg-primary-600 font-semibold text-white'
                                        => $cell['isToday'],

                                    /*
                                     * CURRENT MONTH + EVENT
                                     *
                                     * No dots / labels.
                                     * Day number only.
                                     */
                                    'bg-primary-50 font-semibold text-primary-700 ring-1 ring-inset ring-primary-200'
                                        => !$cell['isToday']
                                            && $cell['hasEvent']
                                            && $cell['isCurrentMonth'],

                                    /*
                                     * NORMAL DAY
                                     */
                                    'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800'
                                        => !$cell['isToday']
                                            && !$cell['hasEvent']
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
                            </div>

                        @endforeach

                    </div>


                    {{-- SIMPLE LEGEND --}}
                    <div class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-gray-100 pt-4 dark:border-gray-800">

                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded bg-primary-600"></span>

                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                Today
                            </span>
                        </div>


                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded bg-primary-100 ring-1 ring-primary-200"></span>

                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                Event
                            </span>
                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- DEADLINES + REMINDERS --}}
        {{-- ========================================================= --}}

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

            {{-- UPCOMING DEADLINES --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">

                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                        Upcoming Deadlines
                    </h2>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Documents due within the next 14 days.
                    </p>
                </div>


                <div class="p-5">

                    <div class="space-y-2">

                        @forelse ($upcomingDeadlines as $document)

                            <div
                                class="flex min-w-0 items-center justify-between gap-4
                                       rounded-lg px-3 py-3 transition hover:bg-gray-50
                                       dark:hover:bg-gray-800"
                            >

                                <div class="min-w-0">

                                    <p class="truncate text-sm font-medium text-gray-950 dark:text-white">
                                        {{ $document->particulars ?: 'Untitled Document' }}
                                    </p>

                                    <p class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400">
                                        {{ $document->office_unit ?: 'No office specified' }}
                                    </p>

                                </div>


                                <div class="shrink-0 text-right">

                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                                        {{ $document->deadline->format('M d') }}
                                    </p>

                                    <p class="mt-1 text-xs text-gray-500">
                                        {{ $document->deadline->diffForHumans() }}
                                    </p>

                                </div>

                            </div>

                        @empty

                            <div class="py-8 text-center">

                                <p class="text-sm text-gray-500">
                                    No upcoming document deadlines.
                                </p>

                            </div>

                        @endforelse

                    </div>

                </div>

            </div>


            {{-- UPCOMING REMINDERS --}}
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">

                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                        Upcoming Reminders
                    </h2>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Upcoming calendar events.
                    </p>
                </div>


                <div class="p-5">

                    <div class="space-y-2">

                        @forelse ($upcomingEvents as $event)

                            <div
                                class="flex min-w-0 items-center gap-4
                                       rounded-lg px-3 py-3 transition hover:bg-gray-50
                                       dark:hover:bg-gray-800"
                            >

                                {{-- DATE --}}
                                <div
                                    class="flex h-11 w-11 shrink-0 flex-col items-center justify-center
                                           rounded-lg bg-gray-100 dark:bg-gray-800"
                                >
                                    <span class="text-[10px] font-semibold uppercase text-gray-500">
                                        {{ $event->date->format('M') }}
                                    </span>

                                    <span class="text-sm font-bold text-gray-950 dark:text-white">
                                        {{ $event->date->format('d') }}
                                    </span>
                                </div>


                                {{-- DETAILS --}}
                                <div class="min-w-0">

                                    <p class="truncate text-sm font-medium text-gray-950 dark:text-white">
                                        {{ $event->event }}
                                    </p>


                                    @if ($event->time)

                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            {{ $event->time->format('g:i A') }}
                                        </p>

                                    @endif

                                </div>

                            </div>

                        @empty

                            <div class="py-8 text-center">

                                <p class="text-sm text-gray-500">
                                    No upcoming reminders.
                                </p>

                            </div>

                        @endforelse

                    </div>

                </div>

            </div>

        </div>

    </div>

</x-filament-panels::page>