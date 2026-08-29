<x-filament-panels::page>
    @php
        $activeSection = $this->activeSection;
        $statusCounts = $this->getStatusCounts();
        $requests = $this->getDocumentRequests($activeSection);
    @endphp

    {{-- STATUS HEADER --}}
    <div class="mb-0 w-full overflow-x-auto border border-gray-300 bg-white shadow-sm">
        <nav class="flex min-w-max items-center gap-1 p-2" aria-label="Document request status">
            @foreach ([
                'pending' => 'Pending',
                'accepted' => 'Accepted',
                'rejected' => 'Rejected',
            ] as $section => $label)
                <a
                    href="{{ request()->fullUrlWithQuery(['section' => $section]) }}"
                    class="rounded-md px-4 py-2 text-sm font-semibold transition-colors
                        {{ $activeSection === $section
                            ? 'bg-[#0F172A] text-white'
                            : 'text-gray-600 hover:bg-gray-100' }}"
                >
                    {{ $label }}
                    <span
                        class="ml-2 inline-flex min-w-5 justify-center rounded-full px-1.5 py-0.5 text-xs
                            {{ $activeSection === $section
                                ? 'bg-white/20 text-white'
                                : 'bg-gray-200 text-gray-600' }}"
                    >
                        {{ $statusCounts[$section] ?? 0 }}
                    </span>
                </a>
            @endforeach
        </nav>
    </div>

    {{-- SEARCH + FILTER --}}
    <div class="mb-0 flex w-full flex-wrap items-center gap-3 border-x border-gray-300 bg-white px-3 py-7 shadow-sm">
        <div class="relative w-full sm:w-96">
            <input
                type="text"
                wire:model.live.debounce.400ms="search"
                placeholder="Search Document"
                class="h-10 w-full rounded-full border border-gray-300 bg-white pl-4 pr-11 text-sm
                       focus:border-primary-500 focus:ring-primary-500"
            >

            <svg
                class="absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-800"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.75"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <path d="M21 21l-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z"/>
            </svg>
        </div>

        <div class="relative w-full sm:w-60">
            <select
                wire:model.live="typeFilter"
                class="h-10 w-full appearance-none rounded-full border border-gray-300 bg-white pl-4 pr-12
                       text-sm text-gray-500 focus:border-primary-500 focus:ring-primary-500"
            >
                <option value="">All Document Types</option>

                @foreach (\App\Models\DocumentType::orderBy('type_name')->get() as $type)
                    <option value="{{ $type->type_id }}">
                        {{ $type->type_name }}
                    </option>
                @endforeach
            </select>

            <svg
                class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500"
                viewBox="0 0 20 20"
                fill="currentColor"
            >
                <path
                    fill-rule="evenodd"
                    d="M5.23 7.21a.75.75 0 1 1 1.06-1.04L10 10.832l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z"
                    clip-rule="evenodd"
                />
            </svg>
        </div>

        {{-- Request Date Filter --}}
        <div class="relative w-full sm:w-60">
            <input
                type="date"
                wire:model.live="dateFilter"
                aria-label="Filter by request date"
                class="peer h-10 w-full appearance-none rounded-full border border-gray-300 bg-white pl-10 pr-4 text-sm
                       {{ $dateFilter ? 'text-gray-500' : 'text-transparent' }}
                       focus:border-primary-500 focus:text-gray-500 focus:ring-primary-500"
            >

            @if (! $dateFilter)
                <span class="pointer-events-none absolute left-10 top-1/2 -translate-y-1/2 text-sm text-gray-500 peer-focus:hidden">
                    Date
                </span>
            @endif

            <svg
                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-500"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.75"
                stroke-linecap="round"
                stroke-linejoin="round"
            >
                <rect x="3.5" y="5" width="17" height="15.5" rx="2"/>
                <path d="M7.5 3.5v3M16.5 3.5v3M3.5 9h17"/>
            </svg>
        </div>
    </div>

    @if ($requests->count())
        <div class="w-full bg-white border border-gray-300 border-b-0 shadow-sm px-4 pt-4 pb-0 mb-0">
            <div class="flex flex-wrap items-center justify-between gap-4 pb-4">
                <div class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                    <span>Show</span>

                    <select
                        wire:model.live="perPage"
                        class="h-9 w-24 rounded-md border border-gray-300 bg-white px-3 py-1 text-sm"
                    >
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="max-h-[calc(100vh-18rem)] w-full min-h-0 overflow-auto border border-gray-300 bg-white">
            <table class="w-full min-w-[1100px] border-collapse text-xs lg:min-w-0">
                <thead class="sticky top-0 z-10 bg-white">
                    <tr class="border-b border-gray-300 bg-white">
                        <th class="w-20 px-4 py-4 text-left text-sm font-bold">NO.</th>
                        <th class="px-4 py-4 text-left text-sm font-bold">DOCUMENT</th>
                        <th class="px-4 py-4 text-left text-sm font-bold">PURPOSE</th>
                        <th class="px-4 py-4 text-left text-sm font-bold">DOCUMENT TYPE</th>
                        <th class="px-4 py-4 text-left text-sm font-bold">REQUESTED BY</th>
                        <th class="px-4 py-4 text-left text-sm font-bold">DATE OF REQUEST</th>
                        @if ($activeSection !== 'pending')
                            <th class="px-4 py-4 text-left text-sm font-bold">
                                DATE {{ strtoupper($activeSection) }}
                            </th>
                        @endif
                        <th class="w-56 px-4 py-4 text-center text-sm font-bold">ACTION</th>
                    </tr>
                </thead>

                <tbody>
                    @php $currentRequestDate = null; @endphp

                    @foreach ($requests as $request)
                        @php
                            $requestDate = $request->date_of_request?->format('Y-m-d') ?? 'unknown';
                        @endphp

                        @if ($requestDate !== $currentRequestDate)
                            <tr class="border-y border-blue-200 bg-blue-50">
                                <td colspan="{{ $activeSection === 'pending' ? 8 : 9 }}" class="px-4 py-2 text-xs font-bold text-blue-900">
                                    Requested {{ $request->date_of_request?->format('F d, Y') ?? 'Unknown date' }}
                                </td>
                            </tr>
                            @php $currentRequestDate = $requestDate; @endphp
                        @endif

                        <tr
                            data-view-url="{{ \App\Filament\Pages\ViewDocument::getUrl(['document' => $request->document_id]) }}"
                            onclick="if (!event.target.closest('button, a, input, select, textarea')) window.location.href = this.dataset.viewUrl"
                            class="cursor-pointer border-b border-gray-300 {{ $loop->odd ? 'bg-[#F2F2F2]' : 'bg-white' }} hover:bg-blue-50"
                        >
                            <td class="px-4 py-4 align-middle font-semibold">
                                {{ $requests->firstItem() + $loop->index }}
                            </td>

                            <td class="px-4 py-4 align-middle">
                                @if ($request->document)
                                    <div class="space-y-1">
                                        <div>
                                            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                                                LAO No:
                                            </span>
                                            <span class="font-semibold text-gray-900">
                                                {{ $request->document->lao_number }}
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                                                Office/Unit:
                                            </span>
                                            <span class="text-gray-800">
                                                {{ $request->document->office_unit }}
                                            </span>
                                        </div>
                                        <div>
                                            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                                                Particulars:
                                            </span>
                                            <span class="text-gray-800">
                                                {{ $request->document->particulars }}
                                            </span>
                                        </div>
                                    </div>
                                @else
                                    <span class="italic text-gray-500">Document unavailable</span>
                                @endif
                            </td>

                            <td class="px-4 py-4 align-middle">
                                {{ $request->purpose ?? '—' }}
                            </td>

                            <td class="px-4 py-4 align-middle">
                                @if ($request->document?->type)
                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold text-white"
                                        style="background-color: {{ $request->document->type->color ?? '#059669' }};"
                                    >
                                        {{ $request->document->type->type_name }}
                                    </span>
                                @else
                                    <span class="italic text-gray-500">Unknown</span>
                                @endif
                            </td>

                            <td class="px-4 py-4 align-middle">
                                <div class="flex flex-col">
                                    <span class="font-semibold text-gray-900">
                                        {{ $request->user?->name ?? 'Unknown user' }}
                                    </span>
                                    @if ($request->user?->email)
                                        <span class="truncate text-xs text-gray-500">
                                            {{ $request->user->email }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-4 py-4 align-middle">
                                {{ $request->date_of_request?->format('F d, Y') ?? 'Unknown date' }}
                            </td>

                            @if ($activeSection !== 'pending')
                                <td class="px-4 py-4 align-middle">
                                    {{ $request->date_processed?->format('F d, Y') ?? 'Unknown date' }}
                                </td>
                            @endif

                            <td class="px-4 py-4 align-middle">
                                <div class="flex items-center justify-center gap-2">
                                    @if ($activeSection === 'pending')
                                        <button
                                            type="button"
                                            wire:click="acceptRequest({{ $request->request_id }})"
                                            wire:confirm="Are you sure you want to accept this request?"
                                            class="inline-flex h-9 items-center justify-center rounded-md bg-green-600 px-3 text-xs font-semibold text-white transition hover:bg-green-700"
                                        >
                                            Accept
                                        </button>

                                        {{-- Reject --}}
                                        <div>
                                            {{ ($this->rejectRequestAction)([
                                                'request' => $request->request_id,
                                            ]) }}
                                        </div>
                                    @else

                                        {{-- Return --}}
                                        <button
                                            type="button"
                                            wire:click="returnRequest({{ $request->request_id }})"
                                            wire:confirm="Are you sure you want to return this request to pending?"
                                            class="inline-flex h-9 items-center justify-center rounded-md border-0 bg-[#DCFCE7] px-3 text-xs font-semibold text-[#15803D] transition hover:bg-[#BBF7D0]"
                                        >
                                            Return
                                        </button>

                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $requests->links() }}
        </div>
    @else
        <div class="border border-dashed border-gray-300 bg-white py-16 text-center">
            <h3 class="text-base font-semibold text-gray-800">
                No {{ $activeSection }} document requests yet
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                Requests with this status will appear here.
            </p>
        </div>
    @endif

    <style>
        /* Keep the document request sections visually connected without page-level gaps. */
        .fi-page-content {
            gap: 0 !important;
        }
    </style>
</x-filament-panels::page>
