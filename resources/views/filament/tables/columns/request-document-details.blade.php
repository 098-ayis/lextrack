@if ($record->document)
    <div class="min-w-0 space-y-1.5 text-xs leading-5">
        <div class="flex min-w-0 items-baseline gap-2">
            <span class="min-w-0 break-words font-semibold text-gray-900 dark:text-gray-100">
                {{ $record->document->lao_number ?: 'Not assigned' }}
            </span>
        </div>

        <div class="flex min-w-0 items-baseline gap-2">
            <span class="shrink-0 text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Office/Unit:
            </span>
            <span class="min-w-0 break-words font-medium text-gray-800 dark:text-gray-200">
                {{ $record->document->office_unit ?: 'Not specified' }}
            </span>
        </div>

        <div class="flex min-w-0 items-baseline gap-2">
            <span class="shrink-0 text-[11px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Particulars:
            </span>
            <span class="min-w-0 break-words font-medium text-gray-800 dark:text-gray-200">
                {{ $record->document->particulars ?: 'No particulars' }}
            </span>
        </div>
    </div>
@else
    <span class="text-xs italic text-gray-500 dark:text-gray-400">
        Document unavailable
    </span>
@endif
