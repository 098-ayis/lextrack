<x-filament-panels::page>

    <style>
        .calendar-event-strip { display:block; width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; text-align:left; padding:3px 6px; border-radius:5px; background:var(--event-color); color:#fff; border-left:4px solid color-mix(in srgb,var(--event-color) 40%,white); font-size:11px; font-weight:600; line-height:1.4; }
        .calendar-event-more { display:block; width:100%; text-align:left; padding:2px 6px; border-radius:5px; border-left:3px solid #004b80; background:#edf5fc; color:#004b80; font-size:10px; font-weight:600; line-height:1.4; }
        .calendar-event-strip:focus-visible,.calendar-event-more:focus-visible { outline:2px solid #a78bfa; outline-offset:2px; }
        .dark .calendar-event-more { background:#24364a; color:#bfdbfe; }

        .calendar-category-legend { display:flex; flex-wrap:wrap; align-items:center; gap:14px 24px; padding:18px; border-top:1px solid #e5e7eb; color:#748492; font-size:12px; background:#fff; }
        .calendar-category-legend strong { font-weight:650; }
        .calendar-category-legend span { display:inline-flex; align-items:center; gap:8px; }
        .calendar-category-legend i { width:9px; height:9px; flex-shrink:0; border-radius:50%; }
        .dark .calendar-category-legend { background:#18181b; color:#a8b3c1; border-color:#374151; }

        .theme-violet-action .fi-btn {
            background-color: #7c3aed !important;
            color: #ffffff !important;
        }

        .theme-violet-action .fi-btn:hover {
            background-color: #6d28d9 !important;
        }

        .theme-violet-action .fi-btn:focus-visible {
            outline: 2px solid #a78bfa;
            outline-offset: 2px;
        }
    </style>

    <div class="space-y-6" wire:poll.60s>

        {{-- ========================================================= --}}
        {{-- TOP SEARCH --}}
        {{-- ========================================================= --}}

        <div class="flex items-center justify-between gap-4">

            <div class="relative w-full max-w-md">

                <x-heroicon-o-magnifying-glass
                    class="
                        absolute
                        left-3
                        top-1/2
                        h-4
                        w-4
                        -translate-y-1/2
                        text-gray-400
                    "
                />

                <input
                    type="text"
                    placeholder="Search Anything"
                    class="
                        w-full
                        rounded-md
                        border-gray-300
                        py-3
                        pl-12
                        pr-4
                        text-base
                        shadow-sm

                        focus:border-violet-500
                        focus:ring-violet-500

                        dark:border-gray-700
                        dark:bg-gray-800
                    "
                >

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- LOAD MONTH EVENTS ONCE --}}
        {{-- ========================================================= --}}

        @php

            $allMonthEvents =
                $this->getMonthEvents();


            /*
             * Group all events by their date.
             *
             * Example:
             *
             * 2026-08-31 => [
             *     Event 1,
             *     Event 2
             * ]
             */
            $eventsByDate =
                $allMonthEvents->groupBy(
                    fn ($event) =>
                        \Carbon\Carbon::parse(
                            $event->date
                        )->format('Y-m-d')
                );




        @endphp


        {{-- ========================================================= --}}
        {{-- MAIN LAYOUT --}}
        {{-- ========================================================= --}}


        <div
            class="
                grid
                grid-cols-1
                gap-6
                xl:grid-cols-[minmax(0,1fr)_300px]
            "
        >


            {{-- ===================================================== --}}
            {{-- LEFT SIDE - CALENDAR --}}
            {{-- ===================================================== --}}

            <div class="min-w-0">


                {{-- ================================================= --}}
                {{-- MONTH NAVIGATION --}}
                {{-- ================================================= --}}

                <div class="mb-4 flex items-center gap-3">

                    <button
                        wire:click="previousMonth"
                        type="button"
                        class="
                            flex
                            h-9
                            w-9
                            items-center
                            justify-center

                            rounded-lg
                            border
                            border-gray-300

                            bg-white
                            text-gray-600

                            transition
                            hover:bg-gray-50

                            dark:border-gray-700
                            dark:bg-gray-800
                        "
                    >
                        ‹
                    </button>


                    <h2
                        class="
                            text-xl
                            font-bold
                            text-gray-900
                            dark:text-white
                        "
                    >
                        {{
                            \Carbon\Carbon::create(
                                $year,
                                $month,
                                1
                            )->format('F Y')
                        }}
                    </h2>


                    <button
                        wire:click="nextMonth"
                        type="button"
                        class="
                            flex
                            h-9
                            w-9
                            items-center
                            justify-center

                            rounded-lg
                            border
                            border-gray-300

                            bg-white
                            text-gray-600

                            transition
                            hover:bg-gray-50

                            dark:border-gray-700
                            dark:bg-gray-800
                        "
                    >
                        ›
                    </button>



                </div>


                {{-- ================================================= --}}
                {{-- CALENDAR CONTAINER --}}
                {{-- ================================================= --}}

                <div
                    class="
                        overflow-hidden
                        rounded-xl

                        border
                        border-gray-200

                        bg-white
                        shadow-sm

                        dark:border-gray-700
                        dark:bg-gray-900
                    "
                >


                    {{-- ============================================= --}}
                    {{-- WEEKDAY HEADER --}}
                    {{-- ============================================= --}}

                    <div
                        class="
                            grid
                            grid-cols-7

                            border-b

                            bg-gray-50

                            dark:border-gray-700
                            dark:bg-gray-800
                        "
                    >

                        @foreach(
                            [
                                'Sun',
                                'Mon',
                                'Tue',
                                'Wed',
                                'Thu',
                                'Fri',
                                'Sat'
                            ]
                            as $day
                        )

                            <div
                                class="
                                    p-3
                                    text-center
                                    text-xs
                                    font-bold
                                    uppercase
                                    tracking-wide
                                    text-gray-500
                                "
                            >
                                {{ $day }}
                            </div>

                        @endforeach

                    </div>


                    {{-- ============================================= --}}
                    {{-- CALENDAR CALCULATIONS --}}
                    {{-- ============================================= --}}

                    @php

                        $currentMonth =
                            \Carbon\Carbon::create(
                                $year,
                                $month,
                                1
                            );


                        $firstDay =
                            $currentMonth
                                ->copy()
                                ->startOfMonth()
                                ->dayOfWeek;


                        $daysInMonth =
                            $currentMonth->daysInMonth;


                        $previousMonth =
                            $currentMonth
                                ->copy()
                                ->subMonth();


                        $previousMonthDays =
                            $previousMonth->daysInMonth;


                        $totalCells =
                            ceil(
                                (
                                    $firstDay +
                                    $daysInMonth
                                ) / 7
                            ) * 7;

                    @endphp


                    {{-- ============================================= --}}
                    {{-- DATE CELLS --}}
                    {{-- ============================================= --}}

                    <div class="grid grid-cols-7">

                        @for(
                            $i = 0;
                            $i < $totalCells;
                            $i++
                        )

                            @php

                                /*
                                 * =========================
                                 * PREVIOUS MONTH DAYS
                                 * =========================
                                 */

                                if ($i < $firstDay) {

                                    $dayNumber =
                                        $previousMonthDays
                                        - $firstDay
                                        + $i
                                        + 1;

                                    $isOtherMonth = true;

                                    $dateString = null;
                                }


                                /*
                                 * =========================
                                 * NEXT MONTH DAYS
                                 * =========================
                                 */

                                elseif (
                                    $i >=
                                    $firstDay +
                                    $daysInMonth
                                ) {

                                    $dayNumber =
                                        $i
                                        - (
                                            $firstDay +
                                            $daysInMonth
                                        )
                                        + 1;

                                    $isOtherMonth = true;

                                    $dateString = null;
                                }


                                /*
                                 * =========================
                                 * CURRENT MONTH
                                 * =========================
                                 */

                                else {

                                    $dayNumber =
                                        $i
                                        - $firstDay
                                        + 1;

                                    $isOtherMonth = false;


                                    $dateString =
                                        sprintf(
                                            '%04d-%02d-%02d',
                                            $year,
                                            $month,
                                            $dayNumber
                                        );
                                }


                                /*
                                 * Is today?
                                 */
                                $isToday =
                                    $dateString ===
                                    now()->format(
                                        'Y-m-d'
                                    );


                                /*
                                 * Is currently selected?
                                 */
                                $isSelected =
                                    $dateString ===
                                    $selectedDate;


                                /*
                                 * Events for this calendar day.
                                 *
                                 * Uses already loaded collection,
                                 * therefore no additional DB query.
                                 */
                                $dayEvents =
                                    $dateString
                                        ? $eventsByDate->get(
                                            $dateString,
                                            collect()
                                        )
                                        : collect();

                                $isDayCompleted = $dayEvents->isNotEmpty()
                                    && $dayEvents->every(fn ($event) => $event->is_completed);

                            @endphp


                            {{-- ===================================== --}}
                            {{-- DAY BOX --}}
                            {{-- ===================================== --}}

                            <div

                                wire:key="
                                    calendar-cell-
                                    {{ $year }}-
                                    {{ $month }}-
                                    {{ $i }}
                                "

                                @if(!$isOtherMonth)

                                    role="button"
                                    tabindex="0"
                                    aria-label="View events on {{ $dateString }}{{ $isDayCompleted ? ' (completed)' : '' }}"
                                    aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
                                    wire:keydown.enter.prevent="selectDate('{{ $dateString }}')"
                                    wire:keydown.space.prevent="selectDate('{{ $dateString }}')"
                                    wire:click="
                                        selectDate(
                                            '{{ $dateString }}'
                                        )
                                    "

                                @endif

                                class="
                                    relative

                                    min-h-[140px]

                                    overflow-hidden

                                    border-b
                                    border-r
                                    border-gray-200

                                    p-2

                                    transition

                                    dark:border-gray-700


                                    @if($isOtherMonth)

                                        cursor-default
                                        bg-gray-50
                                        text-gray-300

                                        dark:bg-gray-950


                                    @elseif($isSelected)

                                        cursor-pointer
                                        bg-violet-50

                                        ring-2
                                        ring-inset
                                        ring-violet-500

                                        dark:bg-violet-950


                                    @elseif($isToday)

                                        cursor-pointer
                                        bg-violet-50

                                        dark:bg-violet-950


                                    @else

                                        cursor-pointer
                                        bg-white

                                        hover:bg-gray-50

                                        dark:bg-gray-900
                                        dark:hover:bg-gray-800

                                    @endif
                                "
                            >


                                {{-- ================================= --}}
                                {{-- DAY NUMBER --}}
                                {{-- ================================= --}}

                                <div
                                    class="
                                        mb-2
                                        flex
                                        items-center
                                        justify-between
                                    "
                                >

                                    <div
                                        class="
                                            flex
                                            h-7
                                            w-7
                                            items-center
                                            justify-center

                                            text-sm
                                            font-bold


                                            @if($isToday)

                                                rounded-full

                                                bg-violet-500

                                                text-white

                                                shadow-sm

                                            @elseif($isOtherMonth)

                                                text-gray-300

                                            @else

                                                text-gray-900

                                                dark:text-white

                                            @endif
                                        "
                                    >

                                        {{ $dayNumber }}

                                    </div>

                                    @if ($isDayCompleted)
                                        <span class="inline-flex items-center gap-1 text-green-600 dark:text-green-400" title="All events completed">
                                            <x-heroicon-m-check-circle class="h-5 w-5" />
                                            <span class="sr-only">All events completed</span>
                                        </span>
                                    @endif

                                </div>


                                {{-- ================================= --}}
                                {{-- EVENTS INSIDE DAY --}}
                                {{-- ================================= --}}

                                @if(!$isOtherMonth)

                                    <div class="space-y-1">

                                        @foreach(
                                            $dayEvents->take(2)
                                            as $event
                                        )

                                            @php

                                                $isDocumentDeadline =
                                                    (bool) (
                                                        $event->is_document_deadline
                                                        ?? false
                                                    );

                                                $eventColor = $this->getEventColor($event);


                                                $staffName =
                                                    $isDocumentDeadline
                                                        ? 'Document deadline'
                                                        : (
                                                            $event->user?->name
                                                            ?? 'Unknown Staff'
                                                        );


                                                $eventTime =
                                                    $event->time

                                                        ? \Carbon\Carbon::parse(
                                                            $event->time
                                                        )->format(
                                                            'g:i A'
                                                        )

                                                        : null;

                                            @endphp


                                            {{-- ===================== --}}
                                            {{-- EVENT PREVIEW --}}
                                            {{-- ===================== --}}

                                            <button
                                                type="button"
                                                wire:key="calendar-event-{{ $event->sched_id }}"
                                                class="calendar-event-strip"
                                                style="--event-color:{{ $eventColor }}"
                                                @if($isDocumentDeadline)
                                                    wire:click.stop="openDocumentDeadline({{ $event->document_id }})"
                                                @else
                                                    wire:click.stop="openDayEvents('{{ $dateString }}')"
                                                @endif
                                                title="{{ $event->event }}{{ $eventTime ? ' · '.$eventTime : '' }}"
                                            >{{ $event->event }}</button>

                                        @endforeach


                                        {{-- ========================= --}}
                                        {{-- MORE EVENTS --}}
                                        {{-- ========================= --}}

                                        @if(
                                            $dayEvents->count()
                                            > 2
                                        )

                                            <button
                                                type="button"
                                                wire:click.stop="openDayEvents('{{ $dateString }}')"
                                                aria-label="View all {{ $dayEvents->count() }} events on {{ $dateString }}"
                                                class="calendar-event-more"
                                            >

                                                +{{ $dayEvents->count() - 2 }} more

                                            </button>

                                        @endif

                                    </div>

                                @endif

                            </div>

                        @endfor

                    </div>
                    <div class="calendar-category-legend">
                        <strong>Legend:</strong>
                        @foreach($this->getEventCategories() as $category => $label)
                            <span><i style="background:{{ $this->getEventColor((object) ['category' => $category]) }}"></i>{{ $label }}</span>
                        @endforeach
                    </div>

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- RIGHT SIDEBAR --}}
            {{-- ===================================================== --}}

            <div class="space-y-4 pt-14">


                {{-- ================================================= --}}
                {{-- CLOCK --}}
                {{-- ================================================= --}}

                <div
                    class="
                        rounded-xl

                        border
                        border-gray-200

                        bg-white

                        p-5

                        text-center

                        shadow-sm

                        dark:border-gray-700
                        dark:bg-gray-900
                    "
                >

                    <div
                        x-data="{

                            time:
                                new Date()
                                .toLocaleTimeString(
                                    'en-US',
                                    {
                                        hour:
                                            'numeric',

                                        minute:
                                            '2-digit'
                                    }
                                ),

                            date:
                                new Date()
                                .toLocaleDateString(
                                    'en-US',
                                    {
                                        month:
                                            'long',

                                        day:
                                            'numeric',

                                        year:
                                            'numeric'
                                    }
                                )

                        }"

                        x-init="

                            setInterval(() => {

                                time =
                                    new Date()
                                    .toLocaleTimeString(
                                        'en-US',
                                        {
                                            hour:
                                                'numeric',

                                            minute:
                                                '2-digit'
                                        }
                                    );


                                date =
                                    new Date()
                                    .toLocaleDateString(
                                        'en-US',
                                        {
                                            month:
                                                'long',

                                            day:
                                                'numeric',

                                            year:
                                                'numeric'
                                        }
                                    );

                            }, 30000);

                        "
                    >


                        <div
                            x-text="time"
                            class="
                                text-2xl
                                font-bold
                                text-violet-600
                            "
                        ></div>


                        <div
                            x-text="date"
                            class="
                                mt-1
                                text-xs
                                text-gray-500
                            "
                        ></div>

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- EVENTS CARD --}}
                {{-- ================================================= --}}

                <div
                    class="
                        rounded-xl

                        border
                        border-gray-200

                        bg-white

                        p-4

                        shadow-sm

                        dark:border-gray-700
                        dark:bg-gray-900
                    "
                >


                    {{-- ============================================= --}}
                    {{-- EVENTS CARD HEADER --}}
                    {{-- ============================================= --}}

                    <div
                        class="
                            mb-3
                            flex
                            items-center
                            gap-2
                        "
                    >

                        <h3
                            class="
                                text-sm
                                font-bold

                                text-gray-900

                                dark:text-white
                            "
                        >

                            @if($selectedDate)

                                Events ·

                                <span
                                    class="
                                    text-violet-600
                                    "
                                >
                                    {{
                                        \Carbon\Carbon::parse(
                                            $selectedDate
                                        )->format('M j')
                                    }}
                                </span>

                            @else

                                Events

                            @endif

                        </h3>


                        <div class="theme-violet-action ml-auto">
                            {{ $this->createEvent() }}
                        </div>

                    </div>


                    {{-- ============================================= --}}
                    {{-- SIDEBAR EVENTS --}}
                    {{-- ============================================= --}}

                    @php

                        /*
                         * If selected date:
                         * show all events for that date.
                         *
                         * Otherwise:
                         * show all events this month.
                         */
                        $events =
                            $selectedDate

                                ? $this->getEvents()

                                : $allMonthEvents;

                    @endphp


                    <div
                        class="
                            max-h-[430px]
                            overflow-y-auto
                            pr-1
                        "
                    >

                        @forelse(
                            $events
                            as $event
                        )

                            @php

                                $isDocumentDeadline =
                                    (bool) (
                                        $event->is_document_deadline
                                        ?? false
                                    );

                                $eventColor = $this->getEventColor($event);


                                $staffName =
                                    $isDocumentDeadline
                                        ? 'Document deadline'
                                        : (
                                            $event->user?->name
                                            ?? 'Unknown Staff'
                                        );


                                $formattedDate =
                                    \Carbon\Carbon::parse(
                                        $event->date
                                    )->format(
                                        'M j'
                                    );


                                $formattedTime =
                                    $event->time

                                        ? \Carbon\Carbon::parse(
                                            $event->time
                                        )->format(
                                            'g:i A'
                                        )

                                        : null;

                            @endphp


                            <div
                                wire:key="
                                    sidebar-event-
                                    {{ $event->sched_id }}
                                "

                                class="
                                    group

                                    flex
                                    items-start
                                    gap-3

                                    border-b
                                    border-gray-100

                                    py-3

                                    last:border-0

                                    dark:border-gray-700
                                    {{ $isDocumentDeadline ? 'cursor-pointer transition hover:bg-gray-50 dark:hover:bg-gray-800' : '' }}
                                "

                                @if($isDocumentDeadline)
                                    wire:click="openDocumentDeadline({{ $event->document_id }})"
                                @endif
                            >


                                {{-- ================================= --}}
                                {{-- USER COLOR DOT --}}
                                {{-- ================================= --}}

                                <span
                                    class="
                                        mt-1.5

                                        h-2.5
                                        w-2.5

                                        shrink-0

                                        rounded-full
                                    "

                                    style="
                                        background-color:
                                            {{ $eventColor }};
                                    "

                                    title="{{ $staffName }}"
                                ></span>


                                {{-- ================================= --}}
                                {{-- EVENT INFORMATION --}}
                                {{-- ================================= --}}

                                <div
                                    class="
                                        min-w-0
                                        flex-1
                                    "
                                >


                                    {{-- DATE + TIME --}}
                                    <div
                                        class="
                                            text-[11px]
                                            font-bold
                                        "

                                        style="
                                            color:
                                                {{ $eventColor }};
                                        "
                                    >

                                        {{ $formattedDate }}


                                        @if($formattedTime)

                                            <span
                                                class="
                                                    text-gray-400
                                                "
                                            >
                                                ·
                                            </span>

                                            {{ $formattedTime }}

                                        @endif

                                    </div>


                                    {{-- EVENT TITLE --}}
                                    <div
                                        class="
                                            mt-1

                                            break-words

                                            text-xs
                                            font-semibold

                                            text-gray-800

                                            dark:text-gray-200
                                        "
                                    >
                                        {{ $event->event }}
                                    @if ($event->is_completed)
                                        <span class="inline-flex items-center gap-1 text-green-600 dark:text-green-400" title="Completed">
                                            <x-heroicon-m-check class="h-3.5 w-3.5" />
                                            <span>Completed</span>
                                        </span>
                                    @endif
                                    </div>


                                    {{-- EVENT DETAILS --}}
                                    @if($event->details)

                                        <div
                                            class="
                                                mt-1

                                                line-clamp-2

                                                text-[10px]
                                                leading-relaxed

                                                text-gray-500
                                            "
                                        >
                                            {{ $event->details }}
                                        </div>

                                    @endif


                                    {{-- ADDED BY --}}
                                    <div
                                        class="
                                            mt-1.5

                                            flex
                                            items-center
                                            gap-1

                                            text-[10px]
                                            text-gray-400
                                        "
                                    >

                                        <span>
                                            {{ $isDocumentDeadline ? 'Source' : 'Added by' }}
                                        </span>

                                        <span
                                            class="
                                                font-semibold

                                                text-gray-600

                                                dark:text-gray-300
                                            "
                                        >
                                            {{ $isDocumentDeadline ? 'Documents' : $staffName }}
                                        </span>

                                    </div>

                                </div>


                                {{-- ================================= --}}
                                {{-- EVENT ACTIONS --}}
                                {{-- ================================= --}}

                                @if(! $isDocumentDeadline && ! ($event->is_automatic_holiday ?? false))

                                <div
                                    class="
                                        flex
                                        shrink-0
                                        gap-1

                                        transition
                                    "
                                >


                                    {{-- EDIT --}}
                                    <div
                                        wire:click.stop
                                    >
                                        {{
                                            ($this->editEventAction)(['eventId' => $event->sched_id])
                                        }}
                                    </div>


                                    {{-- DELETE --}}
                                    <button
                                        wire:click.stop="
                                            deleteEvent(
                                                {{ $event->sched_id }}
                                            )
                                        "

                                        wire:confirm="
                                            Delete this event?
                                        "

                                        type="button"

                                        class="
                                            flex

                                            h-7
                                            w-7

                                            items-center
                                            justify-center

                                            rounded-md

                                            bg-red-50

                                            text-red-600

                                            transition

                                            hover:bg-red-100
                                        "

                                        title="Delete"
                                    >

                                        <x-heroicon-o-trash
                                            class="
                                                h-3.5
                                                w-3.5
                                            "
                                        />

                                    </button>

                                </div>

                                @endif

                            </div>


                        @empty


                            {{-- ===================================== --}}
                            {{-- NO EVENTS --}}
                            {{-- ===================================== --}}

                            <div
                                class="
                                    py-6

                                    text-center

                                    text-xs

                                    text-gray-500
                                "
                            >

                                {{
                                    $selectedDate

                                        ? 'No events on this day.'

                                        : 'No events this month.'
                                }}

                            </div>

                        @endforelse

                    </div>


                    {{-- ============================================= --}}
                    {{-- ADD EVENT BUTTON --}}
                    {{-- ============================================= --}}

                    <div
                        class="
                            theme-violet-action

                            mt-3

                            border-t
                            border-gray-100

                            pt-3

                            dark:border-gray-700
                        "
                    >

                        {{-- SHOW ALL BUTTON --}}
                        @if($selectedDate)

                            <button
                                wire:click="
                                    clearSelectedDate
                                "
                                type="button"
                                class="
                                    ml-auto

                                    rounded-md

                                    bg-violet-50

                                    px-2
                                    py-1

                                    text-xs
                                    font-semibold
                                    text-violet-700

                                    transition

                                    hover:bg-violet-100
                                "
                            >
                                Show all
                            </button>

                        @endif

                    </div>

                </div>


                {{-- ================================================= --}}

            </div>

        </div>

    </div>

    @php($modalEntries = $selectedDate ? ($eventsByDate[$selectedDate] ?? collect()) : collect())
    <x-filament::modal id="calendar-day-events" width="xl">
        <x-slot name="heading">All events on {{ $selectedDate ? \Carbon\Carbon::parse($selectedDate)->format('M j, Y') : '' }} ({{ $modalEntries->count() }})</x-slot>
        @include('filament.pages.partials.calendar-event-list', ['entries' => $modalEntries, 'showDetails' => false])
    </x-filament::modal>
</x-filament-panels::page>
