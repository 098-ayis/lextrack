@if ($record->document?->type)
    <span
        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold text-white"
        style="background-color: {{ $record->document->type->color ?? '#059669' }};"
    >
        {{ $record->document->type->type_name }}
    </span>
@else
    <span class="text-xs italic text-gray-500 dark:text-gray-400">
        Unknown
    </span>
@endif
