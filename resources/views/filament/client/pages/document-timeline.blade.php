<x-filament-panels::page>
    <div class="client-document-timeline mx-auto w-full max-w-4xl overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center gap-3 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
            <a
                href="{{ \App\Filament\Client\Pages\Documents::getUrl(['tab' => $returnTab]) }}"
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-gray-700 transition hover:bg-gray-100 dark:text-gray-100 dark:hover:bg-gray-700"
                title="Back to Documents"
                aria-label="Back to Documents"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
            </a>

            <div class="min-w-0">
                <h1 class="text-lg font-semibold text-gray-900 dark:text-white">Status Timeline</h1>
            </div>
        </div>

        @php($displayTimeline = $statusTimeline)

        <div class="p-5 md:p-8">
            @if ($documentRecord->status === 'rejected')
                <div class="mb-6 flex flex-col gap-3 rounded-lg border border-red-200 bg-red-50 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-red-900/60 dark:bg-red-950/30">
                    <div>
                        <h2 class="text-sm font-semibold text-red-800 dark:text-red-200">Document rejected</h2>
                        <p class="mt-1 text-sm text-red-700 dark:text-red-300">You may submit the document again for review.</p>
                    </div>

                    <a
                        href="{{ \App\Filament\Client\Pages\Upload::getUrl() }}"
                        class="inline-flex shrink-0 items-center justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:bg-red-500 dark:hover:bg-red-400 dark:focus:ring-offset-gray-800"
                    >
                        Resubmit Document
                    </a>
                </div>
            @endif

            @if ($displayTimeline !== [])
                <div class="space-y-0">
                    @foreach ($displayTimeline as $update)
                        @php($isLatest = $loop->first)
                        <article
                            class="relative grid grid-cols-[4.5rem_2.75rem_minmax(0,1fr)] gap-3 pb-6 last:pb-0 md:grid-cols-[6rem_3rem_minmax(0,1fr)]"
                            wire:key="client-document-status-{{ $loop->index }}"
                        >
                            <time class="pt-1 text-right text-sm leading-5 {{ $isLatest ? 'font-bold text-gray-900 dark:text-gray-100' : 'font-semibold text-gray-600 dark:text-gray-300' }}">
                                <span class="block leading-5">{{ $update['date'] }}</span>
                                <span class="mt-1 block font-normal {{ $isLatest ? 'text-gray-500 dark:text-gray-400' : 'text-gray-500 dark:text-gray-400' }}">{{ $update['time'] }}</span>
                            </time>

                            @unless ($loop->last)
                                <span
                                    class="absolute bottom-0 left-[calc(4.5rem+0.75rem+1.375rem)] top-7 w-px -translate-x-1/2 bg-gray-300 dark:bg-gray-600 md:left-[calc(6rem+0.75rem+1.5rem)]"
                                    aria-hidden="true"
                                ></span>
                            @endunless

                            <span
                                class="client-status-timeline-dot relative z-10 inline-flex h-7 w-7 items-center justify-center justify-self-center self-start rounded-full ring-4 ring-white dark:ring-gray-800"
                                style="--timeline-color: {{ $update['color'] ?? '#64748b' }};"
                                aria-hidden="true"
                            >
                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4.5 4.5L19 7" />
                                </svg>
                            </span>

                            <div class="min-w-0 pt-1">
                                <h2 class="text-sm leading-5 {{ $isLatest ? 'font-bold text-gray-900 dark:text-gray-100' : 'font-semibold text-gray-600 dark:text-gray-300' }}">{{ $update['title'] }}</h2>
                                <p class="mt-1 text-sm leading-5 {{ $isLatest ? 'font-medium text-gray-600 dark:text-gray-300' : 'font-medium text-gray-500 dark:text-gray-400' }}">{{ $update['description'] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">No status timeline available.</p>
            @endif
        </div>
    </div>
</x-filament-panels::page>
