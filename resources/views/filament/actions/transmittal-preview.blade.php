@php
    $compact = $compact ?? false;
@endphp

@if (filled($document->transmittal))
    <div
        x-data="{ transmittalPreviewOpen: false }"
        class="{{ $compact ? 'inline-flex' : 'mt-4 border-t border-gray-200 pt-4 dark:border-gray-700' }}"
    >
        @if ($compact)
            <button
                type="button"
                x-on:click.stop.prevent="transmittalPreviewOpen = true"
                class="inline-flex h-7 w-7 items-center justify-center rounded-md text-gray-500 transition hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white"
                aria-label="Preview transmittal/endorsement"
                title="Preview transmittal/endorsement"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z" />
                    <circle cx="12" cy="12" r="2.75" stroke-width="1.75" />
                </svg>
            </button>
        @else
            <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Transmittal/Endorsement
                    </p>
                    <p class="mt-1 truncate text-sm text-gray-900 dark:text-gray-100">
                        Supporting document available for preview
                    </p>
                </div>

                <button
                    type="button"
                    x-on:click.stop.prevent="transmittalPreviewOpen = true"
                    class="shrink-0 rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-indigo-700"
                >
                    Preview
                </button>
            </div>
        @endif

        <div
            x-cloak
            x-show="transmittalPreviewOpen"
            x-on:click.stop
            x-on:keydown.escape.window="transmittalPreviewOpen = false"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/70 p-4"
            role="dialog"
            aria-modal="true"
            aria-label="Transmittal/Endorsement preview"
        >
            <div class="flex h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl dark:bg-gray-900">
                <div class="flex items-center justify-between gap-3 border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                    <h2 class="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">
                        Transmittal/Endorsement Preview
                    </h2>

                    <button
                        type="button"
                        x-on:click.stop.prevent="transmittalPreviewOpen = false"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-gray-500 transition hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-800 dark:hover:text-white"
                        aria-label="Close transmittal/endorsement preview"
                        title="Close preview"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <iframe
                    src="{{ route('admin.documents.transmittal.preview', ['document' => $document->document_id]) }}"
                    title="Transmittal/Endorsement document preview"
                    class="min-h-0 w-full flex-1 border-0"
                ></iframe>
            </div>
        </div>
    </div>
@endif
