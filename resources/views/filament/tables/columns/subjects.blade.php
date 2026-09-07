<div class="min-w-0 space-y-2 text-xs">
    <div class="break-words">
        <span class="font-semibold text-gray-500 dark:text-gray-400">
            Particulars:
        </span>
        <span class="font-medium text-gray-800 dark:text-gray-200">
            {{ $record->particulars ?: 'No particulars' }}
        </span>
    </div>

    <div class="break-words">
        <span class="font-semibold text-gray-500 dark:text-gray-400">
            Office/Unit:
        </span>
        <span class="font-medium text-gray-800 dark:text-gray-200">
            {{ $record->office_unit ?: 'Office not specified' }}
        </span>
    </div>
</div>
