@php
    $documentType = $record->document_type;
@endphp

@if ($documentType)
    <span class="text-sm font-medium text-gray-800 dark:text-gray-200">
        {{ $documentType }}
    </span>
@else
    <span class="text-xs italic text-gray-500 dark:text-gray-400">
        Unknown
    </span>
@endif
