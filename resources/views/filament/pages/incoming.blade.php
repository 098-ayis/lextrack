<x-filament-panels::page>

    {{-- Icon definitions --}}
    <svg width="0" height="0" style="position:absolute">
        <defs>
            {{-- Filter --}}
            <symbol id="icon-filter" viewBox="0 0 24 24">
                <path
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.75"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M3 5h18M6 12h12M10 19h4"
                />
            </symbol>
        </defs>
    </svg>

    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                    Incoming Documents
                </h2>

                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Manage and track documents received by the Legal Affairs Office.
                </p>
            </div>

            <div>
                {{ $this->addDocumentAction }}
            </div>
        </div>

        {{-- Statistics --}}
        @php
            $stats = $this->getStats();
        @endphp

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

            {{-- Total --}}
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Total Documents
                        </p>

                        <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">
                            {{ $stats['total'] }}
                        </p>
                    </div>

                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                        <x-heroicon-o-document-text class="h-5 w-5" />
                    </div>
                </div>
            </div>

            {{-- Pending --}}
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Pending
                        </p>

                        <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">
                            {{ $stats['pending'] }}
                        </p>
                    </div>

                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-warning-50 text-warning-600 dark:bg-warning-500/10 dark:text-warning-400">
                        <x-heroicon-o-clock class="h-5 w-5" />
                    </div>
                </div>
            </div>

            {{-- Active --}}
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Active
                        </p>

                        <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">
                            {{ $stats['active'] }}
                        </p>
                    </div>

                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-info-50 text-info-600 dark:bg-info-500/10 dark:text-info-400">
                        <x-heroicon-o-arrow-path class="h-5 w-5" />
                    </div>
                </div>
            </div>

            {{-- Completed --}}
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Completed
                        </p>

                        <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">
                            {{ $stats['completed'] }}
                        </p>
                    </div>

                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-success-50 text-success-600 dark:bg-success-500/10 dark:text-success-400">
                        <x-heroicon-o-check-circle class="h-5 w-5" />
                    </div>
                </div>
            </div>

        </div>

        {{-- Documents Table --}}
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">

            {{-- Table Header / Toolbar --}}
            <div class="border-b border-gray-200 px-4 py-4 dark:border-white/10 sm:px-6">

                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

                    <div>
                        <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                            Document List
                        </h3>

                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            All incoming documents received by the office.
                        </p>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row">

                        {{-- Search --}}
                        <div class="relative">
                            <x-heroicon-o-magnifying-glass
                                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                            />

                            <input
                                type="text"
                                wire:model.live.debounce.400ms="search"
                                placeholder="Search documents..."
                                class="w-full rounded-lg border-0 bg-gray-50 py-2 pl-9 pr-3 text-sm text-gray-950 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/10 dark:placeholder:text-gray-500 sm:w-64"
                            >
                        </div>

                        {{-- Filter --}}
                        <button
                            type="button"
                            class="flex items-center gap-1 rounded-md border border-gray-200 bg-white px-3 py-2 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                        >
                            <svg class="h-4 w-4">
                                <use href="#icon-filter" />
                            </svg>

                            Filter
                        </button>

                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="overflow-x-auto">

                <table class="w-full text-left">

                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr class="border-b border-gray-200 dark:border-white/10">

                            <th class="whitespace-nowrap px-6 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                LAO/E/C/LO No.
                            </th>

                            <th class="whitespace-nowrap px-6 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Type
                            </th>

                            <th class="whitespace-nowrap px-6 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Client
                            </th>

                            <th class="whitespace-nowrap px-6 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Office/Unit
                            </th>

                            <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Particulars
                            </th>

                            <th class="whitespace-nowrap px-6 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Status
                            </th>

                            <th class="whitespace-nowrap px-6 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Deadline
                            </th>

                            <th class="whitespace-nowrap px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Actions
                            </th>

                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">

                        @php
                            $groupedDocuments = $this->getGroupedDocuments();
                        @endphp

                        @forelse ($groupedDocuments as $date => $documents)

                            @foreach ($documents as $document)

                                <tr class="transition hover:bg-gray-50 dark:hover:bg-white/5">

                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-950 dark:text-white">
                                        {{ $document->lao_number ?? '—' }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $document->type?->name ?? '—' }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $document->client?->name ?? '—' }}
                                    </td>

                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $document->office_unit ?? '—' }}
                                    </td>

                                    <td class="max-w-xs px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                        {{ \Illuminate\Support\Str::limit($document->particulars, 50) }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium {{ $document->statusClasses() }}">
                                            {{ $document->statusLabel() }}
                                        </span>
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $document->deadline?->format('m/d/Y') ?? '—' }}
                                    </td>

                                    <td class="whitespace-nowrap px-6 py-4">

                                        <div class="flex justify-end gap-1">

                                            {{-- View --}}
                                            <button
                                                type="button"
                                                title="View Document"
                                                class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-primary-600 dark:text-gray-400 dark:hover:bg-white/10 dark:hover:text-primary-400"
                                            >
                                                <x-heroicon-o-eye class="h-5 w-5" />
                                            </button>

                                            {{-- Edit --}}
                                            <button
                                                type="button"
                                                title="Edit Document"
                                                class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-warning-600 dark:text-gray-400 dark:hover:bg-white/10 dark:hover:text-warning-400"
                                            >
                                                <x-heroicon-o-pencil-square class="h-5 w-5" />
                                            </button>

                                            {{-- Conversation --}}
                                            <button
                                                type="button"
                                                title="Open Conversation"
                                                class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-success-600 dark:text-gray-400 dark:hover:bg-white/10 dark:hover:text-success-400"
                                            >
                                                <x-heroicon-o-chat-bubble-left-right class="h-5 w-5" />
                                            </button>

                                        </div>

                                    </td>

                                </tr>

                            @endforeach

                        @empty

                            {{-- Empty table state --}}
                            <tr>
                                <td colspan="8" class="px-6 py-16">

                                    <div class="flex flex-col items-center justify-center text-center">

                                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-white/5">
                                            <x-heroicon-o-document-text class="h-6 w-6 text-gray-400" />
                                        </div>

                                        <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                                            No documents found
                                        </h3>

                                        <p class="mt-1 max-w-sm text-sm text-gray-500 dark:text-gray-400">
                                            There are currently no incoming documents. Add a document to start tracking it.
                                        </p>

                                        <div class="mt-4">
                                            {{ $this->addDocumentAction }}
                                        </div>

                                    </div>

                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            {{-- Table Footer --}}
            <div class="border-t border-gray-200 px-6 py-3 dark:border-white/10">

                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Showing
                    <span class="font-medium text-gray-900 dark:text-white">
                        {{ $groupedDocuments->flatten()->count() }}
                    </span>

                    {{ \Illuminate\Support\Str::plural(
                        'document',
                        $groupedDocuments->flatten()->count()
                    ) }}
                </p>

            </div>

        </div>

    </div>

    {{-- Filament action modals --}}
    <x-filament-actions::modals />


</x-filament-panels::page>