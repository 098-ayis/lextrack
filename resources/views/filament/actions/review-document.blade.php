<div class="mt-4 space-y-3 text-left">
    <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
            Document name
        </p>
        <p class="mt-1 break-words text-sm font-semibold text-gray-900 dark:text-gray-100">
            {{ $document->lao_number ?: 'Not assigned' }}
        </p>
    </div>

    <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
            Particulars
        </p>
        <p class="mt-1 break-words text-sm text-gray-900 dark:text-gray-100">
            {{ $document->particulars ?: 'No particulars provided' }}
        </p>
    </div>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Office
            </p>
            <p class="mt-1 break-words text-sm font-medium text-gray-900 dark:text-gray-100">
                {{ $document->office_unit ?: 'Not specified' }}
            </p>
        </div>

        <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Document type
            </p>
            <p class="mt-1 break-words text-sm font-medium text-gray-900 dark:text-gray-100">
                {{ $document->document_type ?? 'Unknown' }}
            </p>
        </div>
    </div>
</div>
