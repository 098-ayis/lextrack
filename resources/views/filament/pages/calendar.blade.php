<x-filament-panels::page>

    <style>
        .theme-indigo-action .fi-btn {
            background-color: #6366f1 !important;
            color: #ffffff !important;
        }

        .theme-indigo-action .fi-btn:hover {
            background-color: #4f46e5 !important;
        }

        .theme-indigo-action .fi-btn:focus-visible {
            outline: 2px solid #818cf8;
            outline-offset: 2px;
        }
    </style>

    <div class="space-y-6">

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

                        focus:border-indigo-500
                        focus:ring-indigo-500

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


            $staffLegend =
                $this->getStaffLegend();

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

                                    <div class="space-y-1">

                                        @foreach(
                                            $dayEvents->take(3)
                                            as $event
                                        )

                                            @php

                                                $eventColor =
                                                    $this->getUserColor(
                                                        $event->user_id
                                                    );


                                                $staffName =
                                                    $event->user?->name
                                                    ?? 'Unknown Staff';


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

                                            <div

                                                wire:key="
                                                    calendar-event-
                                                    {{ $event->sched_id }}
                                                "

                                                class="
                                                    overflow-hidden
                                                    rounded-md

                                                    px-2
                                                    py-1.5

                                                    text-left

                                                    transition

                                                    hover:brightness-95
                                                "

                                                style="
                                                    background-color:
                                                        {{ $eventColor }}18;

                                                    border-left:
                                                        3px solid
                                                        {{ $eventColor }};
                                                "

                                                title="{{
                                                    $eventTime
                                                }} - {{
                                                    $event->event
                                                }} - {{
                                                    $staffName
                                                }}"
                                            >


                                                {{-- TIME --}}
                                                @if($eventTime)

                                                    <div
                                                        class="
                                                            flex
                                                            items-center
                                                            gap-1.5

                                                            truncate

                                                            text-[9px]
                                                            font-semibold
                                                            text-gray-500
                                                        "
                                                    >

                                                        <span
                                                            class="
                                                                h-1.5
                                                                w-1.5
                                                                shrink-0
                                                                rounded-full
                                                            "
                                                            style="
                                                                background-color:
                                                                    {{ $eventColor }};
                                                            "
                                                        ></span>

                                                        {{ $eventTime }}

                                                    </div>

                                                @endif


                                                {{-- EVENT TITLE --}}
                                                <div
                                                    class="
                                                        truncate

                                                        text-[11px]
                                                        font-semibold

                                                        text-gray-800

                                                        dark:text-gray-100
                                                    "
                                                >
                                                    {{ $event->event }}
                                                </div>


                                                {{-- STAFF NAME --}}
                                                <div
                                                    class="
                                                        truncate

                                                        text-[9px]

                                                        text-gray-500
                                                    "
                                                >
                                                    {{ $staffName }}
                                                </div>

                                            </div>

                                        @endforeach


                                        {{-- ========================= --}}
                                        {{-- MORE EVENTS --}}
                                        {{-- ========================= --}}

                                        @if(
                                            $dayEvents->count()
                                            > 3
                                        )

                                            <div
                                                class="
                                                    px-1
                                                    pt-0.5

                                                    text-[10px]
                                                    font-semibold

                                                    text-indigo-600
                                                "
                                            >

                                                +{{
                                                    $dayEvents->count()
                                                    - 3
                                                }}
                                                more

                                            </div>

                                        @endif

                                    </div>

                                @endif

                            </div>

                        @endfor

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
                                text-indigo-600
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
                                Show all
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
                         * show first 6 events this month.
                         */
                        $events =
                            $selectedDate

                                ? $this->getEvents()

                                : $allMonthEvents
                                    ->take(6);

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

                                $eventColor =
                                    $this->getUserColor(
                                        $event->user_id
                                    );


                                $staffName =
                                    $event->user?->name
                                    ?? 'Unknown Staff';


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
                                "
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
                                            Added by
                                        </span>

                                        <span
                                            class="
                                                font-semibold

                                                text-gray-600

                                                dark:text-gray-300
                                            "
                                        >
                                            {{ $staffName }}
                                        </span>

                                    </div>

                                </div>


                                {{-- ================================= --}}
                                {{-- EVENT ACTIONS --}}
                                {{-- ================================= --}}

                                <div
                                    class="
                                        flex
                                        shrink-0
                                        gap-1

                                        opacity-0

                                        transition

                                        group-hover:opacity-100
                                    "
                                >


                                    {{-- EDIT --}}
                                    <div
                                        wire:click.stop
                                    >
                                        {{
                                            $this->editEvent(
                                                $event->sched_id
                                            )
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
                            theme-indigo-action

                            mt-3

                            border-t
                            border-gray-100

                            pt-3

                            dark:border-gray-700
                        "
                    >

                        {{ $this->createEvent() }}

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- STAFF COLOR LEGEND --}}
                {{-- ================================================= --}}

                @if(
                    $staffLegend->isNotEmpty()
                )

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

                        <h3
                            class="
                                mb-3

                                text-sm
                                font-bold

                                text-gray-900

                                dark:text-white
                            "
                        >
                            Staff
                        </h3>


                        <div class="space-y-2">

                            @foreach(
                                $staffLegend
                                as $staff
                            )

                                <div
                                    class="
                                        flex
                                        items-center
                                        gap-2.5
                                    "
                                >

                                    {{-- COLOR --}}
                                    <span
                                        class="
                                            h-2.5
                                            w-2.5

                                            shrink-0

                                            rounded-full
                                        "

                                        style="
                                            background-color:
                                                {{ $staff['color'] }};
                                        "
                                    ></span>


                                    {{-- NAME --}}
                                    <span
                                        class="
                                            truncate

                                            text-xs

                                            text-gray-600

                                            dark:text-gray-300
                                        "
                                    >
                                        {{ $staff['name'] }}
                                    </span>

                                </div>

                            @endforeach

                        </div>

                    </div>

                @endif

            </div>

        </div>

    </div>

</x-filament-panels::page>
