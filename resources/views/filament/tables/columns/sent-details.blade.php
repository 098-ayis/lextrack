<div class="flex w-full flex-col items-center justify-center gap-0.5 text-center text-xs leading-4">
    <span class="max-w-full break-words text-xs font-medium text-gray-800 dark:text-gray-200">
        {{ $record->sent_to ?? 'Not set' }}
    </span>

    @if ($record->sent_date)
        <span class="text-xs text-gray-500 dark:text-gray-400">
            {{ \Carbon\Carbon::parse($record->sent_date)->format('F d, Y') }}
        </span>
    @else
        <span class="text-xs italic text-gray-500 dark:text-gray-400">
            Not sent
        </span>
    @endif
</div>
