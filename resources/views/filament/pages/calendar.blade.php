<x-filament-panels::page>

    <style>
        .calendar-event-strip { display:block; width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; text-align:left; padding:2px 5px; border-radius:5px; background:color-mix(in srgb,var(--event-color) 28%,white); color:#334155; border-left:3px solid color-mix(in srgb,var(--event-color) 62%,white); font-size:11px; font-weight:600; line-height:1.25; }
        .calendar-event-more { display:block; width:100%; text-align:left; padding:2px 5px; border-radius:5px; border-left:3px solid #004b80; background:#edf5fc; color:#004b80; font-size:9px; font-weight:600; line-height:1.25; }
        .calendar-event-strip:focus-visible,.calendar-event-more:focus-visible { outline:2px solid #a78bfa; outline-offset:2px; }
        .dark .calendar-event-more { background:#24364a; color:#bfdbfe; }
        .calendar-month-picker [x-cloak] { display:none !important; }

        .calendar-layout {
            display:grid;
            grid-template-columns:minmax(0,1fr);
            gap:1rem;
        }

        .calendar-month-day-cell { aspect-ratio:1 / 1; }

        @media (min-width:1024px) {
            .calendar-layout {
                grid-template-columns:minmax(0,1fr) clamp(280px,28vw,400px);
                align-items:stretch;
            }

            .calendar-panel { height:calc(100dvh - 8rem); display:flex; flex-direction:column; }
            .calendar-month-grid { flex:1; min-height:0; grid-auto-rows:minmax(0,1fr); }
            .calendar-month-day-cell { aspect-ratio:auto; min-height:0; }
        }

        @media (max-width:1023px) {
            .calendar-panel { height:auto; }
        }

        @media (max-width:639px) {
            .calendar-month-picker [role='dialog'] {
                max-width:calc(100vw - 2rem);
            }
        }

        .calendar-category-legend { display:flex; flex-wrap:wrap; align-items:center; gap:10px 16px; padding:12px; border-top:1px solid #e5e7eb; color:#748492; font-size:11px; background:#fff; }
        .calendar-category-legend strong { font-weight:650; }
        .calendar-category-legend span { display:inline-flex; align-items:center; gap:8px; }
        .calendar-category-legend i { width:9px; height:9px; flex-shrink:0; border-radius:50%; }
        .dark .calendar-category-legend { background:#18181b; color:#a8b3c1; border-color:#374151; }

        .theme-indigo-action .fi-btn {
            background-color: #6366f1 !important;
            color: #ffffff !important;
            border-radius: 0.5rem !important;
        }

        .theme-indigo-action .fi-btn:hover {
            background-color: #4f46e5 !important;
        }

        .theme-indigo-action .fi-btn:focus-visible {
            outline: 2px solid #818cf8;
            outline-offset: 2px;
        }

        .calendar-edit-action {
            width:100%;
        }

        .calendar-event-menu-edit.fi-btn {
            display:flex;
            width:100%;
            align-items:center;
            justify-content:flex-start;
            gap:0.5rem;
            border:0 !important;
            box-shadow:none !important;
        }

        .calendar-event-menu-edit.fi-btn svg {
            height:0.875rem !important;
            width:0.875rem !important;
        }
    </style>

    <div class="space-y-6" wire:poll.60s>

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
            class="calendar-layout"
        >


            {{-- ===================================================== --}}
            {{-- LEFT SIDE - CALENDAR --}}
            {{-- ===================================================== --}}

            <div class="min-w-0">


                <div
                    class="
                        calendar-panel

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


                {{-- ================================================= --}}
                {{-- MONTH NAVIGATION --}}
                {{-- ================================================= --}}

                <div
                    class="
                        flex
                        flex-wrap
                        flex-none
                        items-center
                        justify-between
                        gap-3
                        border-b
                        border-gray-200
                        bg-white
                        p-3
                        dark:border-gray-700
                        dark:bg-gray-900
                    "
                >

                    <div class="flex items-center gap-2">

                    <button
                        wire:click="previousCalendarPeriod"
                        type="button"
                        class="
                            flex
                            h-8
                            w-8
                            items-center
                            justify-center

                            rounded-lg
                            bg-transparent
                            text-gray-600
                            text-2xl
                            leading-none

                            transition
                            hover:bg-gray-100

                            dark:bg-gray-800
                            dark:hover:bg-gray-700
                        "
                    >
                        ‹
                    </button>


                    @if($calendarView === 'month')
                        <div
                            class="calendar-month-picker relative"
                            wire:key="calendar-month-picker-{{ $year }}-{{ $month }}"
                            x-data="{ open: false, yearOpen: false, yearPage: Math.min(9988, Math.max(1, Math.floor(({{ $year }} - 1) / 12) * 12 + 1)) }"
                        >
                            <button
                                type="button"
                                x-on:click="open = ! open; yearOpen = false"
                                x-bind:aria-expanded="open.toString()"
                                aria-haspopup="dialog"
                                class="
                                    inline-flex
                                    items-center
                                    gap-1
                                    rounded-md
                                    px-1
                                    py-1
                                    text-lg
                                    font-bold
                                    text-gray-900
                                    transition
                                    hover:bg-gray-100
                                    dark:text-white
                                    dark:hover:bg-gray-800
                                "
                            >
                                <span>{{ \Carbon\Carbon::create($year, $month, 1)->format('F Y') }}</span>
                                <x-heroicon-o-chevron-down class="h-4 w-4 text-gray-500" />
                            </button>

                            <div
                                x-cloak
                                x-show="open"
                                x-on:click.outside="open = false; yearOpen = false"
                                x-transition
                                role="dialog"
                                aria-label="Select calendar month and year"
                                class="
                                    absolute
                                    left-0
                                    top-full
                                    z-30
                                    mt-2
                                    w-80
                                    rounded-xl
                                    border
                                    border-gray-200
                                    bg-white
                                    p-4
                                    shadow-xl
                                    dark:border-gray-700
                                    dark:bg-gray-800
                                "
                            >
                                <div class="mb-3 flex items-center justify-end gap-3">
                                    <button
                                        type="button"
                                        x-on:click="yearOpen = ! yearOpen"
                                        x-bind:aria-expanded="yearOpen.toString()"
                                        aria-haspopup="listbox"
                                        class="
                                            inline-flex
                                            items-center
                                            px-0
                                            py-0
                                            text-sm
                                            font-semibold
                                            text-[#6366F1]
                                            transition
                                            hover:text-[#4F46E5]
                                            dark:text-[#818CF8]
                                            dark:hover:text-[#A5B4FC]
                                        "
                                    >
                                        <span x-text="yearOpen ? 'Month' : 'Year'">Year</span>
                                    </button>
                                </div>

                                <div
                                    x-show="! yearOpen"
                                    class="grid grid-cols-3 gap-2"
                                    role="listbox"
                                    aria-label="Select month"
                                >
                                    @foreach(range(1, 12) as $monthOption)
                                        <button
                                            type="button"
                                            role="option"
                                            aria-selected="{{ $monthOption === $month ? 'true' : 'false' }}"
                                            wire:click="changeMonth('{{ sprintf('%04d-%02d', $year, $monthOption) }}')"
                                            x-on:click="open = false; yearOpen = false"
                                            class="
                                                rounded-lg
                                                px-2
                                                py-2
                                                text-sm
                                                font-medium
                                                transition
                                                {{ $monthOption === $month ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : 'text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 dark:text-gray-200 dark:hover:bg-indigo-950 dark:hover:text-indigo-300' }}
                                            "
                                        >
                                            {{ \Carbon\Carbon::create($year, $monthOption, 1)->format('F') }}
                                        </button>
                                    @endforeach
                                </div>

                                <div
                                    x-cloak
                                    x-show="yearOpen"
                                    x-transition
                                >
                                    <div class="mb-2 flex items-center justify-between">
                                        <button
                                            type="button"
                                            x-on:click.stop="yearPage = Math.max(1, yearPage - 12)"
                                            x-bind:disabled="yearPage <= 1"
                                            aria-label="Previous years"
                                            class="px-1 text-lg leading-none text-[#6366F1] transition hover:text-[#4F46E5] disabled:cursor-not-allowed disabled:opacity-40"
                                        >
                                            ‹
                                        </button>

                                        <span
                                            class="text-xs font-semibold text-gray-500 dark:text-gray-400"
                                            x-text="yearPage + ' – ' + Math.min(yearPage + 11, 9999)"
                                        ></span>

                                        <button
                                            type="button"
                                            x-on:click.stop="yearPage = Math.min(9988, yearPage + 12)"
                                            x-bind:disabled="yearPage >= 9988"
                                            aria-label="Next years"
                                            class="px-1 text-lg leading-none text-[#6366F1] transition hover:text-[#4F46E5] disabled:cursor-not-allowed disabled:opacity-40"
                                        >
                                            ›
                                        </button>
                                    </div>

                                    <div
                                        class="grid grid-cols-3 gap-2"
                                        role="listbox"
                                        aria-label="Select year"
                                    >
                                        <template x-for="yearOffset in 12" :key="yearPage + yearOffset - 1">
                                            <button
                                                type="button"
                                                role="option"
                                                x-bind:aria-selected="(yearPage + yearOffset - 1 === {{ $year }}).toString()"
                                                x-on:click="$wire.changeMonth((yearPage + yearOffset - 1) + '-{{ sprintf('%02d', $month) }}'); open = false; yearOpen = false"
                                                x-bind:class="(yearPage + yearOffset - 1 === {{ $year }}) ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : 'text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 dark:text-gray-200 dark:hover:bg-indigo-950 dark:hover:text-indigo-300'"
                                                class="rounded-lg px-2 py-2 text-sm font-medium transition"
                                            >
                                                <span x-text="yearPage + yearOffset - 1"></span>
                                            </button>
                                        </template>
                                    </div>
                                </div>

                            </div>
                        </div>

                    @else
                        <h2
                            class="
                                text-lg
                                font-bold
                                text-gray-900
                                dark:text-white
                            "
                        >
                            @if($calendarView === 'week')
                            @php
                                $weekTitleStart = \Carbon\Carbon::parse($selectedDate ?? now()->toDateString())->startOfWeek(\Carbon\Carbon::SUNDAY);
                                $weekTitleEnd = $weekTitleStart->copy()->endOfWeek(\Carbon\Carbon::SATURDAY);
                            @endphp
                            {{ $weekTitleStart->format('M j') }} – {{ $weekTitleEnd->format('M j, Y') }}
                            @else
                            {{ \Carbon\Carbon::parse($selectedDate ?? now()->toDateString())->format('F j, Y') }}
                            @endif
                        </h2>
                    @endif


                    <button
                        wire:click="nextCalendarPeriod"
                        type="button"
                        class="
                            flex
                            h-8
                            w-8
                            items-center
                            justify-center

                            rounded-lg
                            bg-transparent
                            text-gray-600
                            text-2xl
                            leading-none

                            transition
                            hover:bg-gray-100

                            dark:bg-gray-800
                            dark:hover:bg-gray-700
                        "
                    >
                        ›
                    </button>

                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            wire:click="goToToday"
                            aria-label="Go to today"
                            title="Go to today"
                            class="px-0 py-0 text-sm font-semibold text-[#6366F1] transition hover:text-[#4F46E5] dark:text-[#818CF8] dark:hover:text-[#A5B4FC]"
                        >
                            Today
                        </button>

                    <div
                        class="
                            inline-flex
                            items-center
                            rounded-lg
                            bg-[#F1F5F9]
                            p-1
                            dark:bg-[#F1F5F9]
                        "
                        role="group"
                        aria-label="Calendar view"
                    >
                        @foreach(['month' => 'Month', 'week' => 'Week', 'day' => 'Day'] as $view => $label)
                            <button
                                type="button"
                                wire:click="setCalendarView('{{ $view }}')"
                                aria-pressed="{{ $calendarView === $view ? 'true' : 'false' }}"
                                class="rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $calendarView === $view ? 'bg-[#0F172A] text-white shadow-sm' : 'text-gray-500 hover:text-[#0F172A] dark:text-gray-500 dark:hover:text-[#0F172A]' }}"
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                    </div>

                </div>


                    @if($calendarView === 'month')

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
                                    p-2
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

                    <div class="calendar-month-grid grid grid-cols-7">

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
                                    aria-label="View events on {{ $dateString }}"
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

                                    calendar-month-day-cell
                                    aspect-square
                                    min-h-0

                                    overflow-hidden

                                    border-b
                                    border-r
                                    border-gray-200

                                    p-1.5

                                    transition

                                    dark:border-gray-700


                                    @if($isOtherMonth)

                                        cursor-default
                                        bg-gray-50
                                        text-gray-300

                                        dark:bg-gray-950


                                    @elseif($isSelected)

                                        cursor-pointer
                                        bg-indigo-50

                                        ring-2
                                        ring-inset
                                        ring-indigo-500

                                        dark:bg-indigo-950


                                    @elseif($isToday)

                                        cursor-pointer
                                        bg-indigo-50

                                        dark:bg-indigo-950


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
                                        mb-1
                                        flex
                                        items-center
                                        justify-between
                                    "
                                >

                                    <div
                                        class="
                                            flex
                                            h-6
                                            w-6
                                            items-center
                                            justify-center

                                            text-xs
                                            font-bold


                                            @if($isToday)

                                                rounded-full

                                                bg-indigo-500

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

                                </div>


                                {{-- ================================= --}}
                                {{-- EVENTS INSIDE DAY --}}
                                {{-- ================================= --}}

                                @if(!$isOtherMonth)

                                    <div class="space-y-0.5">

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
                                                wire:click.stop="selectDate('{{ $dateString }}')"
                                                title="{{ $this->getEventTitle($event) }}{{ $eventTime ? ' · '.$eventTime : '' }}"
                                            >{{ $this->getEventTitle($event) }}</button>

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
                                                wire:click.stop="selectDate('{{ $dateString }}')"
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
                            @php
                                $legendColor = $this->getEventColor((object) ['category' => $category]);
                            @endphp
                            <span><i style="background:color-mix(in srgb, {{ $legendColor }} 28%, white); border:1px solid color-mix(in srgb, {{ $legendColor }} 42%, white);"></i>{{ $label }}</span>
                        @endforeach
                    </div>

                    @elseif($calendarView === 'week')

                        @php
                            $weekStart = \Carbon\Carbon::parse($selectedDate ?? now()->toDateString())->startOfWeek(\Carbon\Carbon::SUNDAY);
                            $calendarHours = range(8, 17);
                            $calendarHourHeight = 64;
                            $timelineStartMinutes = 8 * 60;
                            $timelineHeight = $calendarHourHeight * count($calendarHours);
                            $currentTime = now();
                            $currentTimeMinutes = ($currentTime->hour * 60) + $currentTime->minute;
                        @endphp

                        <div class="min-h-0 flex-1 overflow-auto">
                        <div class="grid min-w-[860px] grid-cols-[4.5rem_repeat(7,minmax(0,1fr))]">
                            <div class="border-r border-gray-200 dark:border-gray-700">
                                <div class="h-12 border-b border-gray-200 dark:border-gray-700"></div>
                                <div>
                                    @foreach($calendarHours as $hour)
                                        <div
                                            class="flex items-start justify-end border-b border-gray-100 pr-2 pt-1 text-[10px] font-medium text-gray-400 dark:border-gray-800 dark:text-gray-500"
                                            style="height:{{ $calendarHourHeight }}px;"
                                        >
                                            {{ \Carbon\Carbon::createFromTime($hour)->format('g A') }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            @foreach(range(0, 6) as $dayOffset)
                                @php
                                    $weekDate = $weekStart->copy()->addDays($dayOffset);
                                    $weekDateString = $weekDate->toDateString();
                                    $weekDayEvents = $this->getEventsForDate($weekDateString);
                                    $weekTimedEvents = $weekDayEvents->filter(fn ($event) => filled($event->time));
                                    $weekAllDayEvents = $weekDayEvents->filter(fn ($event) => blank($event->time));
                                    $isCurrentDay = $weekDateString === $currentTime->toDateString();
                                @endphp

                                <div
                                    wire:key="calendar-week-{{ $weekDateString }}"
                                    class="
                                        flex
                                        min-w-0
                                        min-h-0
                                        flex-col
                                        overflow-hidden
                                        border-b
                                        border-r
                                        border-gray-200
                                        bg-white
                                        dark:border-gray-700
                                        dark:bg-gray-900
                                    "
                                >
                                    <button
                                        type="button"
                                        wire:click="selectDate('{{ $weekDateString }}')"
                                        class="
                                            flex
                                            flex-none
                                            items-center
                                            justify-between
                                            border-b
                                            border-gray-200
                                            bg-gray-50
                                            px-3
                                            py-2
                                            text-left
                                            dark:border-gray-700
                                            dark:bg-gray-800
                                        "
                                    >
                                        <span class="text-xs font-bold uppercase text-gray-500">
                                            {{ $weekDate->format('D') }}
                                        </span>
                                        <span class="{{ $weekDateString === now()->toDateString() ? 'rounded-full bg-indigo-500 text-white' : 'text-gray-900 dark:text-white' }} flex h-6 w-6 items-center justify-center text-xs font-bold">
                                            {{ $weekDate->day }}
                                        </span>
                                    </button>

                                    <div
                                        class="relative border-r border-gray-200 dark:border-gray-700"
                                        style="height:{{ $timelineHeight }}px;"
                                    >
                                        @foreach($calendarHours as $hour)
                                            <div
                                                class="pointer-events-none absolute inset-x-0 border-b border-gray-100 dark:border-gray-800"
                                                style="top:{{ ($hour - 8) * $calendarHourHeight }}px;"
                                            ></div>
                                        @endforeach

                                        @if($isCurrentDay && $currentTimeMinutes >= $timelineStartMinutes && $currentTimeMinutes <= (17 * 60))
                                            <div
                                                class="pointer-events-none absolute inset-x-0 z-20 border-t-2 border-red-400"
                                                style="top:{{ (($currentTimeMinutes - $timelineStartMinutes) / 60) * $calendarHourHeight }}px;"
                                            >
                                                <span class="absolute -top-3 right-1 rounded bg-red-400 px-1 text-[9px] font-semibold text-white">
                                                    {{ $currentTime->format('g:i A') }}
                                                </span>
                                            </div>
                                        @endif

                                        @foreach($weekAllDayEvents as $event)
                                            @php
                                                $eventColor = $this->getEventColor($event);
                                                $eventTop = 4 + ($loop->index * 52);
                                            @endphp

                                            <button
                                                type="button"
                                                wire:key="calendar-week-event-{{ $event->sched_id }}"
                                                wire:click="selectDate('{{ $weekDateString }}')"
                                                class="absolute left-1 right-1 z-10 min-w-0 overflow-visible rounded-lg border border-l-4 p-2 text-left text-xs"
                                                style="top:{{ $eventTop }}px; background-color:color-mix(in srgb, {{ $eventColor }} 10%, white); border-color:color-mix(in srgb, {{ $eventColor }} 28%, white); border-left-color:{{ $eventColor }};"
                                            >
                                                <div class="mb-1 text-[11px] font-semibold text-gray-500">All day</div>
                                                <div class="text-sm font-semibold break-all text-gray-800 dark:text-gray-100">{{ $this->getEventTitle($event) }}</div>
                                                @if($this->getEventDetails($event))
                                                    <div class="break-all whitespace-pre-line text-[11px] text-gray-500">{{ $this->getEventDetails($event) }}</div>
                                                @endif
                                            </button>

                                        @endforeach

                                        @foreach($weekTimedEvents as $event)
                                            @php
                                                $eventColor = $this->getEventColor($event);
                                                $eventCarbonTime = \Carbon\Carbon::parse($event->time);
                                                $eventMinutes = ($eventCarbonTime->hour * 60) + $eventCarbonTime->minute;
                                                $eventTop = max(0, min(
                                                    (($eventMinutes - $timelineStartMinutes) / 60) * $calendarHourHeight,
                                                    $timelineHeight - 52
                                                ));
                                                $eventTime = $eventCarbonTime->format('g:i A');
                                            @endphp

                                            <button
                                                type="button"
                                                wire:key="calendar-week-event-{{ $event->sched_id }}"
                                                wire:click="selectDate('{{ $weekDateString }}')"
                                                title="{{ $this->getEventTitle($event) }} · {{ $eventTime }}"
                                                class="absolute left-1 right-1 z-10 min-w-0 overflow-visible rounded-lg border border-l-4 p-2 text-left text-xs"
                                                style="top:{{ $eventTop }}px; min-height:52px; background-color:color-mix(in srgb, {{ $eventColor }} 10%, white); border-color:color-mix(in srgb, {{ $eventColor }} 28%, white); border-left-color:{{ $eventColor }};"
                                            >
                                                <div class="text-[11px] font-semibold text-gray-500">{{ $eventTime }}</div>
                                                <div class="text-sm font-semibold break-all text-gray-800 dark:text-gray-100">{{ $this->getEventTitle($event) }}</div>
                                                @if($this->getEventDetails($event))
                                                    <div class="break-all whitespace-pre-line text-[11px] text-gray-500">{{ $this->getEventDetails($event) }}</div>
                                                @endif
                                            </button>
                                        @endforeach

                                        @if($weekDayEvents->isEmpty())
                                            <div class="absolute inset-x-0 top-4 text-center text-[10px] text-gray-400">No events</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        </div>

                        <div class="calendar-category-legend">
                            <strong>Legend:</strong>
                            @foreach($this->getEventCategories() as $category => $label)
                                @php
                                    $legendColor = $this->getEventColor((object) ['category' => $category]);
                                @endphp
                                <span><i style="background:color-mix(in srgb, {{ $legendColor }} 28%, white); border:1px solid color-mix(in srgb, {{ $legendColor }} 42%, white);"></i>{{ $label }}</span>
                            @endforeach
                        </div>

                    @else

                        @php
                            $dayViewDate = \Carbon\Carbon::parse($selectedDate ?? now()->toDateString());
                            $dayViewDateString = $dayViewDate->toDateString();
                            $dayViewEvents = $this->getEventsForDate($dayViewDateString);
                            $calendarHours = range(8, 17);
                            $calendarHourHeight = 64;
                            $timelineStartMinutes = 8 * 60;
                            $timelineHeight = $calendarHourHeight * count($calendarHours);
                            $currentTime = now();
                            $currentTimeMinutes = ($currentTime->hour * 60) + $currentTime->minute;
                            $dayTimedEvents = $dayViewEvents->filter(fn ($event) => filled($event->time));
                            $dayAllDayEvents = $dayViewEvents->filter(fn ($event) => blank($event->time));
                        @endphp

                        <div class="min-h-0 flex-1 overflow-y-auto p-4">
                            <div class="mb-4 flex items-center justify-between border-b border-gray-200 pb-3 dark:border-gray-700">
                                <div class="text-sm font-bold uppercase tracking-wide text-gray-500">
                                    {{ $dayViewDate->format('l') }}
                                </div>
                            </div>

                            <div class="grid grid-cols-[4.5rem_minmax(0,1fr)] overflow-hidden">
                                <div class="border-r border-gray-200 dark:border-gray-700">
                                    @foreach($calendarHours as $hour)
                                        <div
                                            class="flex items-start justify-end border-b border-gray-100 pr-2 pt-1 text-[10px] font-medium text-gray-400 dark:border-gray-800 dark:text-gray-500"
                                            style="height:{{ $calendarHourHeight }}px;"
                                        >
                                            {{ \Carbon\Carbon::createFromTime($hour)->format('g A') }}
                                        </div>
                                    @endforeach
                                </div>

                                <div
                                    class="relative"
                                    style="height:{{ $timelineHeight }}px;"
                                >
                                    @foreach($calendarHours as $hour)
                                        <div
                                            class="pointer-events-none absolute inset-x-0 border-b border-gray-100 dark:border-gray-800"
                                            style="top:{{ ($hour - 8) * $calendarHourHeight }}px;"
                                        ></div>
                                    @endforeach

                                    @if($dayViewDateString === $currentTime->toDateString() && $currentTimeMinutes >= $timelineStartMinutes && $currentTimeMinutes <= (17 * 60))
                                        <div
                                            class="pointer-events-none absolute inset-x-0 z-20 border-t-2 border-red-400"
                                            style="top:{{ (($currentTimeMinutes - $timelineStartMinutes) / 60) * $calendarHourHeight }}px;"
                                        >
                                            <span class="absolute -top-3 right-1 rounded bg-red-400 px-1 text-[9px] font-semibold text-white">
                                                {{ $currentTime->format('g:i A') }}
                                            </span>
                                        </div>
                                    @endif

                                    @foreach($dayAllDayEvents as $event)
                                        @php
                                            $eventColor = $this->getEventColor($event);
                                            $eventTop = 4 + ($loop->index * 52);
                                        @endphp

                                        <div
                                            wire:key="calendar-day-event-{{ $event->sched_id }}"
                                            class="absolute left-1 right-1 z-10 overflow-visible rounded-lg border border-l-4 p-3"
                                            style="top:{{ $eventTop }}px; background-color:color-mix(in srgb, {{ $eventColor }} 10%, white); border-color:color-mix(in srgb, {{ $eventColor }} 28%, white); border-left-color:{{ $eventColor }};"
                                        >
                                            <div class="mb-1 text-[10px] font-semibold text-gray-500">All day</div>
                                            <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $this->getEventTitle($event) }}</div>
                                            @if($this->getEventDetails($event))
                                                <div class="whitespace-pre-line text-xs text-gray-500">{{ $this->getEventDetails($event) }}</div>
                                            @endif
                                        </div>
                                    @endforeach

                                    @foreach($dayTimedEvents as $event)
                                        @php
                                            $eventColor = $this->getEventColor($event);
                                            $eventCarbonTime = \Carbon\Carbon::parse($event->time);
                                            $eventMinutes = ($eventCarbonTime->hour * 60) + $eventCarbonTime->minute;
                                            $eventTop = max(0, min(
                                                (($eventMinutes - $timelineStartMinutes) / 60) * $calendarHourHeight,
                                                $timelineHeight - 64
                                            ));
                                            $eventTime = $eventCarbonTime->format('g:i A');
                                        @endphp

                                        <div
                                            wire:key="calendar-day-event-{{ $event->sched_id }}"
                                            class="absolute left-1 right-1 z-10 overflow-visible rounded-lg border border-l-4 p-3"
                                            style="top:{{ $eventTop }}px; min-height:64px; background-color:color-mix(in srgb, {{ $eventColor }} 10%, white); border-color:color-mix(in srgb, {{ $eventColor }} 28%, white); border-left-color:{{ $eventColor }};"
                                        >
                                            <div class="text-xs font-semibold text-gray-500">{{ $eventTime }}</div>
                                            <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $this->getEventTitle($event) }}</div>
                                            @if($this->getEventDetails($event))
                                                <div class="whitespace-pre-line text-xs text-gray-500">{{ $this->getEventDetails($event) }}</div>
                                            @endif
                                        </div>
                                    @endforeach

                                    @if($dayViewEvents->isEmpty())
                                        <div class="absolute inset-x-0 top-4 text-center text-sm text-gray-400">No events on this day.</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="calendar-category-legend">
                            <strong>Legend:</strong>
                            @foreach($this->getEventCategories() as $category => $label)
                                @php
                                    $legendColor = $this->getEventColor((object) ['category' => $category]);
                                @endphp
                                <span><i style="background:color-mix(in srgb, {{ $legendColor }} 28%, white); border:1px solid color-mix(in srgb, {{ $legendColor }} 42%, white);"></i>{{ $label }}</span>
                            @endforeach
                        </div>

                    @endif

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- RIGHT SIDEBAR --}}
            {{-- ===================================================== --}}

            <div class="space-y-4 lg:flex lg:h-[calc(100dvh-8rem)] lg:min-h-0 lg:flex-col lg:self-stretch lg:overflow-hidden">


                {{-- ================================================= --}}
                {{-- SEARCH --}}
                {{-- ================================================= --}}

                <div class="flex flex-wrap items-center gap-3">
                    <div class="relative min-w-0 w-full flex-1 sm:w-auto">
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
                            placeholder="Search event"
                            wire:model.live.debounce.300ms="search"
                            class="block w-full rounded-full border border-gray-300 bg-white py-2.5 pl-11 pr-4 text-sm text-gray-900 placeholder:text-gray-400 focus:border-[#6366F1] focus:outline-none focus:ring-0 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 dark:placeholder:text-gray-400"
                        >
                    </div>

                    <div class="theme-indigo-action shrink-0">
                        {{ $this->createEvent() }}
                    </div>
                </div>

                {{-- ================================================= --}}
                {{-- EVENTS CARD --}}
                {{-- ================================================= --}}

                <div
                    class="
                        flex
                        flex-col
                        min-h-0
                        flex-1
                        lg:flex-1
                        lg:overflow-hidden

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

                            @if($selectedDate && ! $showAllEvents && trim($search) === '')

                                Events ·

                                <span
                                    class="
                                    text-indigo-600
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

                        @if($selectedDate && ! $showAllEvents && trim($search) === '')
                            <button
                                wire:click="clearSelectedDate"
                                type="button"
                                class="
                                    ml-auto
                                    rounded-md
                                    bg-indigo-50
                                    px-2
                                    py-1
                                    text-xs
                                    font-semibold
                                    text-indigo-700
                                    transition
                                    hover:bg-indigo-100
                                "
                            >
                                Show all event
                            </button>
                        @elseif($showAllEvents && trim($search) === '')
                            <button
                                wire:click="goToToday"
                                type="button"
                                aria-label="Close all events"
                                title="Close all events"
                                class="ml-auto inline-flex h-7 w-7 items-center justify-center rounded-md text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800"
                            >
                                <x-heroicon-o-x-mark class="h-4 w-4" />
                            </button>
                        @endif

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
                            $showAllEvents || ! $selectedDate

                                ? $allMonthEvents

                                : $this->getEvents();

                    @endphp


                    <div
                        class="
                            min-h-0
                            max-h-[430px]
                            flex-1
                            lg:flex-1
                            lg:max-h-none
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

                                $isAutomaticHoliday =
                                    (bool) (
                                        $event->is_automatic_holiday
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

                                    mb-3
                                    rounded-lg
                                    border
                                    p-3

                                    dark:border-gray-700
                                    {{ $isDocumentDeadline ? 'cursor-pointer transition hover:bg-gray-50 dark:hover:bg-gray-800' : '' }}
                                "
                                style="background-color:color-mix(in srgb, {{ $eventColor }} 10%, white); border-color:color-mix(in srgb, {{ $eventColor }} 28%, white);"

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

                                    style="background-color:color-mix(in srgb, {{ $eventColor }} 28%, white); border:1px solid color-mix(in srgb, {{ $eventColor }} 42%, white);"

                                    title="{{ $staffName }}"
                                ></span>


                                {{-- ================================= --}}
                                {{-- EVENT INFORMATION --}}
                                {{-- ================================= --}}

                                <div
                                    class="
                                        min-w-0
                                        flex-1
                                        space-y-1
                                    "
                                >


                                    {{-- DATE + TIME --}}
                                    <div
                                        class="
                                            flex
                                            flex-wrap
                                            items-center
                                            gap-x-1.5
                                            text-xs
                                            font-bold
                                            leading-4
                                        "

                                        style="color:color-mix(in srgb, {{ $eventColor }} 72%, #334155);"
                                    >

                                        <span>{{ $formattedDate }}</span>


                                        @if($formattedTime)

                                            <span class="text-gray-400">·</span>
                                            <span>{{ $formattedTime }}</span>

                                        @endif

                                    </div>


                                    {{-- EVENT TITLE --}}
                                    <div
                                        class="
                                            break-words

                                            text-sm
                                            font-semibold
                                            leading-5

                                            text-gray-800

                                            dark:text-gray-200
                                        "
                                    >
                                        {{ $this->getEventTitle($event) }}
                                    </div>


                                    {{-- EVENT DETAILS --}}
                                    @if($this->getEventDetails($event))

                                        <div
                                        class="
                                                !mt-0
                                                whitespace-pre-line
                                                text-xs
                                                leading-4

                                                text-gray-500
                                            "
                                        >
                                            {{ $this->getEventDetails($event) }}
                                        </div>

                                    @endif


                                    @if (! $isAutomaticHoliday)
                                        {{-- ADDED BY --}}
                                        <div
                                        class="
                                                flex
                                                flex-wrap
                                                items-baseline
                                                gap-x-1

                                                text-[11px]
                                                leading-4
                                                text-gray-500
                                            "
                                        >

                                            <span>
                                                {{ $isDocumentDeadline ? 'Source:' : 'Added by:' }}
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
                                    @endif

                                </div>


                                {{-- ================================= --}}
                                {{-- EVENT ACTIONS --}}
                                {{-- ================================= --}}

                                @if(! $isDocumentDeadline && ! ($event->is_automatic_holiday ?? false))

                                <div
                                    class="
                                        relative
                                        shrink-0
                                    "
                                    x-data="{ open: false }"
                                >

                                    <button
                                        type="button"
                                        x-on:click.stop="open = ! open"
                                        x-bind:aria-expanded="open.toString()"
                                        aria-haspopup="menu"
                                        aria-label="Event actions"
                                        class="
                                            flex
                                            h-7
                                            w-7
                                            items-center
                                            justify-center
                                            rounded-md
                                            border-0
                                            bg-transparent
                                            text-gray-500
                                            transition
                                            hover:bg-white/70
                                            hover:text-gray-700
                                        "
                                    >
                                        <x-heroicon-o-ellipsis-vertical class="h-5 w-5" />
                                    </button>

                                    <div
                                        x-cloak
                                        x-show="open"
                                        x-on:click.outside="open = false"
                                        x-transition
                                        role="menu"
                                        class="
                                            absolute
                                            right-0
                                            z-20
                                            mt-1
                                            w-32
                                            rounded-lg
                                            border
                                            border-gray-200
                                            bg-white
                                            p-1
                                            shadow-lg
                                            dark:border-gray-700
                                            dark:bg-gray-800
                                        "
                                    >
                                        <div
                                            class="calendar-edit-action"
                                            x-on:click="open = false"
                                            wire:click.stop
                                        >
                                            {{
                                                ($this->editEventAction)(['eventId' => $event->sched_id])
                                            }}
                                        </div>

                                        <button
                                            type="button"
                                            role="menuitem"
                                            x-on:click="open = false"
                                            wire:click.stop="deleteEvent({{ $event->sched_id }})"
                                            wire:confirm="Delete this event?"
                                            class="
                                                flex
                                                w-full
                                                items-center
                                                gap-2
                                                rounded-md
                                                border-0
                                                bg-transparent
                                                px-3
                                                py-2
                                                text-left
                                                text-xs
                                                font-medium
                                                text-red-600
                                                transition
                                                hover:bg-red-50
                                            "
                                        >
                                            <x-heroicon-o-trash class="h-3.5 w-3.5" />
                                            <span>Delete</span>
                                        </button>
                                    </div>

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


                </div>


                {{-- ================================================= --}}

            </div>

        </div>

    </div>

</x-filament-panels::page>
