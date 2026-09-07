@php
    $documentType = $record->document_type;
    $color = $documentType
        ? \App\Models\DocumentType::query()->where('type_name', $documentType)->value('color')
        : null;
@endphp

@if ($documentType)
    <span
        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold text-white"
        style="background-color: {{ $color ?? '#059669' }};"
    >
        {{ $documentType }}
    </span>
@else
    <span class="text-xs italic text-gray-500 dark:text-gray-400">
        Unknown
    </span>
@endif
