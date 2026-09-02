<div class="flex flex-col gap-1">
    <span class="text-xs font-medium text-gray-800 dark:text-gray-200">
        {{ $record->sent_to ?? 'Not set' }}
    </span>

    @if ($record->sent_date)
        <span class="text-[11px] text-gray-500 dark:text-gray-400">
            {{ \Carbon\Carbon::parse($record->sent_date)->format('F d, Y') }}
        </span>
    @else
        <span class="text-[11px] italic text-gray-500 dark:text-gray-400">
            Not sent
        </span>
    @endif
</div>
