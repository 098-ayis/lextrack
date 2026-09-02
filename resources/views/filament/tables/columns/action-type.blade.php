@if ($record->actionType)
    <span
        class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold text-white"
        style="background-color: {{ $record->actionType->color ?? '#64748B' }};"
    >
        {{ $record->actionType->action_name }}
    </span>
@else
    <span class="text-xs italic text-gray-500 dark:text-gray-400">
        No action assigned
    </span>
@endif
