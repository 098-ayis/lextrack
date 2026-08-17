<x-filament-panels::page>

    <div class="space-y-6">

        {{-- TOP BAR --}}
        <div class="flex items-center justify-between gap-4">

            <div class="relative w-full max-w-md">

                <x-heroicon-o-magnifying-glass
                    class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                />

                <input
                    type="text"
                    placeholder="Search Anything"
                    class="w-full rounded-md border-gray-300 pl-12 pr-4 py-3 text-base shadow-sm
                        focus:border-primary-500 focus:ring-primary-500
                        dark:border-gray-700 dark:bg-gray-800"
                >

            </div>

        </div>


        {{-- CALENDAR + EVENTS --}}
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1fr_280px]">

            {{-- CALENDAR --}}
            <div>

                {{-- MONTH NAVIGATION --}}
                <div class="mb-4 flex items-center gap-3">

                    <button
                        wire:click="previousMonth"
                        type="button"
                        class="flex h-9 w-9 items-center justify-center rounded-lg
                               border border-gray-300 bg-white text-gray-600
                               hover:bg-gray-50
                               dark:border-gray-700 dark:bg-gray-800"
                    >
                        ‹
                    </button>

                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ \Carbon\Carbon::create($year, $month, 1)->format('F Y') }}
                    </h2>

                    <button
                        wire:click="nextMonth"
                        type="button"
                        class="flex h-9 w-9 items-center justify-center rounded-lg
                               border border-gray-300 bg-white text-gray-600
                               hover:bg-gray-50
                               dark:border-gray-700 dark:bg-gray-800"
                    >
                        ›
                    </button>

                </div>


                {{-- CALENDAR --}}
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm
                            dark:border-gray-700 dark:bg-gray-900">

                    <div class="grid grid-cols-7 border-b bg-gray-50
                                dark:border-gray-700 dark:bg-gray-800">

                        @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)

                            <div class="p-3 text-center text-xs font-bold uppercase tracking-wide text-gray-500">
                                {{ $day }}
                            </div>

                        @endforeach

                    </div>


                    @php

                        $currentMonth = \Carbon\Carbon::create($year, $month, 1);

                        $firstDay = $currentMonth->copy()->startOfMonth()->dayOfWeek;

                        $daysInMonth = $currentMonth->daysInMonth;

                        $previousMonth = $currentMonth->copy()->subMonth();

                        $previousMonthDays = $previousMonth->daysInMonth;

                        $totalCells = ceil(($firstDay + $daysInMonth) / 7) * 7;

                    @endphp


                    <div class="grid grid-cols-7">

                        @for($i = 0; $i < $totalCells; $i++)

                            @php

                                if ($i < $firstDay) {

                                    $dayNumber = $previousMonthDays - $firstDay + $i + 1;

                                    $isOtherMonth = true;

                                    $dateString = null;

                                } elseif ($i >= $firstDay + $daysInMonth) {

                                    $dayNumber = $i - ($firstDay + $daysInMonth) + 1;

                                    $isOtherMonth = true;

                                    $dateString = null;

                                } else {

                                    $dayNumber = $i - $firstDay + 1;

                                    $isOtherMonth = false;

                                    $dateString = sprintf(
                                        '%04d-%02d-%02d',
                                        $year,
                                        $month,
                                        $dayNumber
                                    );

                                }

                                $isToday =
                                    $dateString === now()->format('Y-m-d');

                                $isSelected =
                                    $dateString === $selectedDate;

                                $dayEvents =
                                    $dateString
                                        ? $this->getEventsForDate($dateString)
                                        : collect();

                            @endphp


                            <div
                                @if(!$isOtherMonth)
                                    wire:click="selectDate('{{ $dateString }}')"
                                @endif

                                class="min-h-[105px] border-r border-b border-gray-200 p-2
                                    transition
                                    dark:border-gray-700

                                    @if($isOtherMonth)
                                        bg-gray-50 text-gray-300 dark:bg-gray-950
                                    @elseif($isSelected)
                                        bg-primary-50 ring-2 ring-inset ring-primary-600
                                        dark:bg-primary-950
                                    @elseif($isToday)
                                        bg-primary-50 dark:bg-primary-950
                                    @else
                                        bg-white hover:bg-gray-50 dark:bg-gray-900 dark:hover:bg-gray-800
                                    @endif
                                "
                            >

                                <div
                                    class="mb-1 flex h-6 w-6 items-center justify-center text-sm font-bold
                                        @if($isToday)
                                            rounded-full bg-primary-600 text-white
                                        @endif"
                                >
                                    {{ $dayNumber }}
                                </div>


                                {{-- EVENTS --}}
                                @foreach($dayEvents->take(2) as $event)

                                    @php

                                        $staffColors = [
                                            "Ma'am Dha" => 'bg-blue-100 text-blue-700',
                                            'Maam Chin' => 'bg-orange-100 text-orange-700',
                                            'Maam Shin' => 'bg-green-100 text-green-700',
                                            'Sir Tom' => 'bg-purple-100 text-purple-700',
                                            "Ma'am Liza" => 'bg-pink-100 text-pink-700',
                                        ];

                                        $eventColor =
                                            $staffColors[$event->staff]
                                            ?? 'bg-blue-100 text-blue-700';

                                    @endphp


                                    <div
                                        class="mb-1 truncate rounded px-1.5 py-0.5 text-[10px] font-medium
                                               {{ $eventColor }}"
                                        title="{{ $event->staff }}"
                                    >
                                        {{ $event->title }}
                                    </div>

                                @endforeach


                                @if($dayEvents->count() > 2)

                                    <div class="text-[10px] font-medium text-primary-600">
                                        +{{ $dayEvents->count() - 2 }} more
                                    </div>

                                @endif

                            </div>

                        @endfor

                    </div>

                </div>

            </div>


            {{-- RIGHT SIDE --}}
            <div class="space-y-4">

                {{-- CLOCK --}}
                <div
                    class="rounded-xl border border-gray-200 bg-white p-5 text-center shadow-sm
                           dark:border-gray-700 dark:bg-gray-900"
                >

                    <div
                        x-data="{
                            time: new Date().toLocaleTimeString('en-US', {
                                hour: 'numeric',
                                minute: '2-digit'
                            }),
                            date: new Date().toLocaleDateString('en-US', {
                                month: 'long',
                                day: 'numeric',
                                year: 'numeric'
                            })
                        }"
                        x-init="
                            setInterval(() => {
                                time = new Date().toLocaleTimeString('en-US', {
                                    hour: 'numeric',
                                    minute: '2-digit'
                                });

                                date = new Date().toLocaleDateString('en-US', {
                                    month: 'long',
                                    day: 'numeric',
                                    year: 'numeric'
                                });
                            }, 30000);
                        "
                    >

                        <div
                            x-text="time"
                            class="text-2xl font-bold text-primary-700"
                        ></div>

                        <div
                            x-text="date"
                            class="mt-1 text-xs text-gray-500"
                        ></div>

                    </div>

                </div>


                {{-- EVENTS CARD --}}
                <div
                    class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm
                           dark:border-gray-700 dark:bg-gray-900"
                >

                    <div class="mb-3 flex items-center gap-2">

                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">

                            @if($selectedDate)

                                Events ·
                                <span class="text-primary-600">
                                    {{ \Carbon\Carbon::parse($selectedDate)->format('M j') }}
                                </span>

                            @else

                                Events

                            @endif

                        </h3>


                        @if($selectedDate)

                            <button
                                wire:click="clearSelectedDate"
                                type="button"
                                class="ml-auto rounded-md bg-primary-50 px-2 py-1
                                       text-xs font-semibold text-primary-700
                                       hover:bg-primary-100"
                            >
                                Show all
                            </button>

                        @endif

                    </div>


                    @php

                        $events = $selectedDate
                            ? $this->getEvents()
                            : $this->getMonthEvents()->take(6);

                    @endphp


                    @forelse($events as $event)

                        <div
                            class="group flex items-start gap-2 border-b border-gray-100
                                   py-3 last:border-0 dark:border-gray-700"
                        >

                            @php

                                $dotColors = [
                                    "Ma'am Dha" => 'bg-blue-600',
                                    'Maam Chin' => 'bg-orange-600',
                                    'Maam Shin' => 'bg-green-600',
                                ];

                                $dotColor =
                                    $dotColors[$event->staff]
                                    ?? 'bg-blue-600';

                            @endphp


                            <span
                                class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $dotColor }}"
                                title="{{ $event->staff }}"
                            ></span>


                            <div class="min-w-0 flex-1">

                                <div class="text-[11px] font-bold text-primary-700">

                                    {{ \Carbon\Carbon::parse($event->date)->format('M j') }}

                                    @if($event->time)
                                        <br>
                                        {{ \Carbon\Carbon::parse($event->time)->format('g:i A') }}
                                    @endif

                                </div>

                                <div class="mt-1 text-xs text-gray-700 dark:text-gray-300">
                                    {{ $event->event }}
                                </div>

                                <div class="mt-1 text-[10px] text-gray-400">
                                    {{ $event->name }}
                                </div>

                            </div>


                            <div
                                class="flex gap-1 opacity-0 transition group-hover:opacity-100"
                            >

                                {{ ($this->editEvent($event->id)) }}

                                <button
                                    wire:click="deleteEvent({{ $event->sched_id }})"
                                    wire:confirm="Delete this event?"
                                    type="button"
                                    class="flex h-6 w-6 items-center justify-center rounded
                                           bg-red-50 text-red-600 hover:bg-red-100"
                                    title="Delete"
                                >
                                    <x-heroicon-o-trash class="h-3.5 w-3.5"/>
                                </button>

                            </div>

                        </div>

                    @empty

                        <div class="py-4 text-center text-xs text-gray-500">
                            {{ $selectedDate ? 'No events on this day.' : 'No upcoming events.' }}
                        </div>

                    @endforelse


                    {{-- ADD EVENT --}}
                    <div class="mt-3">
                        {{ ($this->createEvent()) }}
                    </div>


                    {{-- STAFF LEGEND --}}
                    @php
                        $staffUsed = $events
                            ->pluck('staff')
                            ->unique()
                            ->values();
                    @endphp


                    @if($staffUsed->isNotEmpty())

                        <div class="mt-4 flex flex-wrap gap-3 border-t pt-3 dark:border-gray-700">

                            @foreach($staffUsed as $staff)

                                <div class="flex items-center gap-1.5 text-[11px] text-gray-500">

                                    <span
                                        class="h-2 w-2 rounded-full
                                        {{ $dotColors[$staff] ?? 'bg-blue-600' }}"
                                    ></span>

                                    {{ $staff }}

                                </div>

                            @endforeach

                        </div>

                    @endif

                </div>

            </div>

        </div>

    </div>

</x-filament-panels::page>