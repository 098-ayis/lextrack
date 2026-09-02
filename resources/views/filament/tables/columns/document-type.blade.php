@if ($record->type)
    <span
        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold text-white"
        style="background-color: {{ $record->type->color ?? '#059669' }};"
    >
        {{ $record->type->type_name }}
    </span>
@else
    <span class="text-xs italic text-gray-500 dark:text-gray-400">
        Unknown
    </span>
@endif
