<x-filament-panels::page>
    <div class="mx-auto w-full max-w-4xl overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
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
                <p class="truncate text-sm text-gray-500 dark:text-gray-400">
                    {{ $documentRecord->description ?: $documentRecord->particulars ?: 'Document status history' }}
                </p>
            </div>
        </div>

        <div class="p-5 md:p-8">
            @if ($statusTimeline !== [])
                <div class="space-y-0">
                    @foreach ($statusTimeline as $update)
                        @php
                            $timelineMarker = $loop->last
                                ? 'bg-emerald-700 text-white'
                                : 'bg-emerald-100 text-emerald-700';
                        @endphp

                        <article
                            class="relative grid grid-cols-[4.5rem_2.75rem_minmax(0,1fr)] gap-3 pb-6 last:pb-0 md:grid-cols-[6rem_3rem_minmax(0,1fr)]"
                            wire:key="client-document-status-{{ $loop->index }}"
                        >
                            <time class="pt-1 text-right text-sm font-semibold leading-5 text-gray-700 dark:text-gray-200">
                                <span class="block text-sm font-semibold leading-5">{{ $update['date'] }}</span>
                                <span class="mt-1 block font-normal text-gray-400 dark:text-gray-500">{{ $update['time'] }}</span>
                            </time>

                            @unless ($loop->last)
                                <span
                                    class="absolute bottom-0 left-[calc(4.5rem+0.75rem+1.375rem)] top-7 w-px -translate-x-1/2 bg-gray-300 dark:bg-gray-600 md:left-[calc(6rem+0.75rem+1.5rem)]"
                                    aria-hidden="true"
                                ></span>
                            @endunless

                            <span class="relative z-10 inline-flex h-7 w-7 items-center justify-center justify-self-center self-start rounded-full {{ $timelineMarker }} ring-4 ring-white dark:ring-gray-800" aria-hidden="true">
                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4.5 4.5L19 7" />
                                </svg>
                            </span>

                            <div class="min-w-0 pt-1">
                                <h2 class="text-sm font-semibold leading-5 text-gray-900 dark:text-gray-100">{{ $update['title'] }}</h2>
                                <p class="mt-1 text-sm leading-5 text-gray-500 dark:text-gray-400">{{ $update['description'] }}</p>
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
