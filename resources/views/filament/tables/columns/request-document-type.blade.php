@php
    $documentType = $record->document?->document_type;
    $color = $documentType
        ? \App\Models\DocumentType::query()->where('type_name', $documentType)->value('color')
        : null;
@endphp

@if ($documentType)
    <span
        class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold leading-4"
        style="--badge-color: {{ $color ?? '#059669' }}; background-color: color-mix(in srgb, var(--badge-color) 14%, white); color: var(--badge-color); border: 1px solid color-mix(in srgb, var(--badge-color) 18%, transparent);"
    >
        {{ $documentType }}
    </span>
@else
    <span class="text-xs italic text-gray-500 dark:text-gray-400">
        Unknown
    </span>
@endif
