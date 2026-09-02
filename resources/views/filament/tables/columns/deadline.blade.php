@if ($record->deadline)
    @php
        $deadline = $record->deadline->copy()->startOfDay();
        $today = now()->startOfDay();
        $daysRemaining = $today->diffInDays($deadline, false);

        if ($daysRemaining < 0) {
            $urgencyColor = 'bg-red-500';
            $urgencyLabel = 'Overdue';
        } elseif ($daysRemaining <= 1) {
            $urgencyColor = 'bg-orange-500';
            $urgencyLabel = $daysRemaining === 0 ? 'Due today' : 'Due tomorrow';
        } else {
            $urgencyColor = 'bg-emerald-500';
            $urgencyLabel = 'On track';
        }
    @endphp

    <div class="flex flex-col items-center gap-1">
        <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">
            {{ $deadline->format('F d, Y') }}
        </span>

        <span class="inline-flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
            <span
                class="h-2.5 w-2.5 shrink-0 rounded-full {{ $urgencyColor }}"
                title="{{ $urgencyLabel }}"
            ></span>
            {{ $urgencyLabel }}
        </span>
    </div>
@else
    <span class="text-xs italic text-gray-500 dark:text-gray-400">
        No deadline set
    </span>
@endif
