<x-filament-panels::page>

    {{-- Icon set (stroke-based, Figma-style) --}}
    <svg width="0" height="0" style="position:absolute">
        <defs>
            <symbol id="icon-folder" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/></symbol>
            <symbol id="icon-eye" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="2.75" fill="none" stroke="currentColor" stroke-width="1.75"/></symbol>
            <symbol id="icon-pencil" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" d="m16.5 4.5 3 3L8 19l-4 1 1-4L16.5 4.5Z"/></symbol>
            <symbol id="icon-chat" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" d="M4 5h16v11H8l-4 4V5Z"/></symbol>
            <symbol id="icon-search" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z"/></symbol>
            <symbol id="icon-filter" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" d="M3 5h18M6 12h12M10 19h4"/></symbol>
            <symbol id="icon-plus" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/></symbol>
        </defs>
    </svg>

    @php $stats = $this->getStats(); @endphp

    {{-- Stat cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg border-t-4 border-primary-600 p-4 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 tracking-wide">TOTAL DOCUMENTS</p>
            <p class="text-3xl font-bold mt-1 text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border-t-4 border-amber-400 p-4 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 tracking-wide">PENDING</p>
            <p class="text-3xl font-bold mt-1 text-gray-900 dark:text-white">{{ $stats['pending'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border-t-4 border-indigo-500 p-4 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 tracking-wide">ACTIVE</p>
            <p class="text-3xl font-bold mt-1 text-gray-900 dark:text-white">{{ $stats['active'] }}</p>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border-t-4 border-green-500 p-4 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 tracking-wide">COMPLETED</p>
            <p class="text-3xl font-bold mt-1 text-gray-900 dark:text-white">{{ $stats['completed'] }}</p>
        </div>
    </div>

    {{-- Search / Filter --}}
    <div class="flex items-center gap-3 mb-6">
        <div class="relative w-72">
            <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><use href="#icon-search"/></svg>
            <input
                type="text"
                wire:model.live.debounce.400ms="search"
                placeholder="Search Document"
                class="w-full pl-9 pr-3 py-2 rounded-md border border-gray-200 dark:border-gray-700 dark:bg-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-primary-400"
            >
        </div>
        <button class="flex items-center gap-1 px-3 py-2 rounded-md border border-gray-200 dark:border-gray-700 text-sm text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-800">
            <svg class="w-4 h-4"><use href="#icon-filter"/></svg>
            Filter
        </button>
    </div>

    {{-- Date groups --}}
    @forelse ($this->getGroupedDocuments() as $date => $documents)
        <div class="mb-6 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-900/40 px-4 py-3">
                <span class="font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                    <svg class="w-4 h-4 text-primary-500"><use href="#icon-folder"/></svg>
                    {{ $date }}
                </span>
                <button
                    wire:click="mountAction('addDocument')"
                    class="flex items-center gap-1 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium px-3 py-1.5 rounded-md"
                >
                    <svg class="w-4 h-4"><use href="#icon-plus"/></svg>
                    Add Documents
                </button>
            </div>

            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-500 uppercase border-b dark:border-gray-700">
                        <th class="px-4 py-2">LAO/E/C/LO No.</th>
                        <th class="px-4 py-2">Type</th>
                        <th class="px-4 py-2">Client</th>
                        <th class="px-4 py-2">Office/Unit</th>
                        <th class="px-4 py-2">Particulars</th>
                        <th class="px-4 py-2">Status</th>
                        <th class="px-4 py-2">Deadline</th>
                        <th class="px-4 py-2">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($documents as $document)
                        <tr class="border-b last:border-0 dark:border-gray-700">
                            <td class="px-4 py-3">{{ $document->lao_number ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $document->type->name ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $document->client->name ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $document->office_unit ?? '—' }}</td>
                            <td class="px-4 py-3">{{ \Illuminate\Support\Str::limit($document->particulars, 40) }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 rounded-full text-xs font-medium {{ $document->statusClasses() }}">
                                    {{ $document->statusLabel() }}
                                </span>
                            </td>
                            <td class="px-4 py-3">{{ $document->deadline?->format('m/d/Y') ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <button class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center">
                                        <svg class="w-4 h-4"><use href="#icon-eye"/></svg>
                                    </button>
                                    <button class="w-8 h-8 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center">
                                        <svg class="w-4 h-4"><use href="#icon-pencil"/></svg>
                                    </button>
                                    <button class="w-8 h-8 rounded-full bg-green-100 text-green-600 flex items-center justify-center">
                                        <svg class="w-4 h-4"><use href="#icon-chat"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <div class="text-center text-gray-400 py-16 border border-dashed rounded-lg dark:border-gray-700">
            No incoming documents yet.
        </div>
    @endforelse

    <x-filament-actions::modals />
</x-filament-panels::page>