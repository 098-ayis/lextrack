@php
    $document = $record->document;
    $documentName = $document?->document_name
        ?: ($document?->latestVersion?->file_path
            ? basename($document->latestVersion->file_path)
            : null);
    $displayDocumentName = $documentName
        ? \Illuminate\Support\Str::limit($documentName, 25)
        : 'Unnamed document';
@endphp

@if ($document)
    <div class="flex items-start gap-3 text-xs">
        <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-400/10 dark:text-primary-400">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 2.25H6.75A2.25 2.25 0 0 0 4.5 4.5v15A2.25 2.25 0 0 0 6.75 21.75h10.5a2.25 2.25 0 0 0 2.25-2.25V7.5l-5.25-5.25Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 2.25V7.5h5.25M8.25 12h7.5M8.25 15.75h7.5" />
            </svg>
        </div>

        <div class="min-w-0 space-y-1">
            <div>
                <span class="font-semibold text-gray-900 dark:text-gray-100">
                    {{ $document->lao_number ?: 'Not assigned' }}
                </span>
            </div>

            <div
                class="max-w-[25ch] truncate"
                title="{{ $documentName ?: 'Unnamed document' }}"
            >
                <span class="font-medium text-gray-800 dark:text-gray-200">
                    {{ $displayDocumentName }}
                </span>
            </div>
        </div>
    </div>
@else
    <span class="text-xs italic text-gray-500 dark:text-gray-400">
        Document unavailable
    </span>
@endif
