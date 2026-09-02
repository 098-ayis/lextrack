<div class="flex flex-col gap-1">
    <span class="text-xs font-medium text-gray-800 dark:text-gray-200">
        {{ $record->returned_from ?? 'Not returned' }}
    </span>

    @if ($record->date_returned)
        <span class="text-[11px] text-gray-500 dark:text-gray-400">
            {{ \Carbon\Carbon::parse($record->date_returned)->format('F d, Y') }}
        </span>
    @else
        <span class="text-[11px] italic text-gray-500 dark:text-gray-400">
            Not returned
        </span>
    @endif
</div>
