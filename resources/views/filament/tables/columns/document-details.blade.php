<div class="space-y-1 text-xs">
    <div>
        <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
            LAO No:
        </span>
        <span class="font-semibold text-gray-900 dark:text-gray-100">
            {{ $record->lao_number ?: 'Not assigned' }}
        </span>
    </div>

    <div>
        <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
            Office/Unit:
        </span>
        <span class="font-medium text-gray-800 dark:text-gray-200">
            {{ $record->office_unit ?: 'Not specified' }}
        </span>
    </div>

    <div>
        <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
            Particulars:
        </span>
        <span class="font-medium text-gray-800 dark:text-gray-200">
            {{ $record->particulars ?: 'No particulars' }}
        </span>
    </div>
</div>
