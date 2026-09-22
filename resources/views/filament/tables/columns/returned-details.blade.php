<div class="flex w-full flex-col items-center justify-center gap-0.5 text-center text-xs leading-4">
    <span class="max-w-full break-words text-xs font-medium text-gray-800 dark:text-gray-200">
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
