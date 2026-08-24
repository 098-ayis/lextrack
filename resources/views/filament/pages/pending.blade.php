<x-filament-panels::page>

    @php
        $documents = $this->getDocuments();
    @endphp

    {{-- SEARCH + FILTER --}}
    <div class="flex flex-wrap items-center gap-3 mb-6">

        {{-- Search --}}
        <div class="relative w-full sm:w-80">
            <input
                type="text"
                wire:model.live.debounce.400ms="search"
                placeholder="Search Document"
                class="w-full h-10 pl-4 pr-11 rounded-full
                       border border-gray-300
                       bg-white text-sm
                       focus:border-primary-500 focus:ring-primary-500"
            >

            <svg
                class="absolute right-4 top-1/2 -translate-y-1/2
                       w-5 h-5 text-gray-800"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
            >
                <circle cx="11" cy="11" r="7"/>
                <path d="m20 20-3.5-3.5"/>
            </svg>
        </div>


        {{-- Document Type Filter --}}
        <div class="relative w-full sm:w-52">
            <select
                wire:model.live="typeFilter"
                class="w-full h-10 pl-4 pr-12 rounded-full
                       border border-gray-300
                       bg-white text-sm text-gray-500
                       appearance-none
                       focus:border-primary-500 focus:ring-primary-500"
            >
                <option value="">All Document Types</option>

                @foreach (\App\Models\DocumentType::orderBy('type_name')->get() as $type)
                    <option value="{{ $type->type_id }}">
                        {{ $type->type_name }}
                    </option>
                @endforeach
            </select>

            <svg
                class="pointer-events-none absolute right-3 top-1/2
                       -translate-y-1/2 w-4 h-4 text-gray-500"
                viewBox="0 0 20 20"
                fill="currentColor"
            >
                <path
                    fill-rule="evenodd"
                    d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z"
                    clip-rule="evenodd"
                />
            </svg>
        </div>

    </div>


    {{-- TABLE CONTROLS --}}
    <div class="bg-white border border-gray-300 border-b-0 shadow-sm px-4 py-4">

        <div class="flex items-center justify-between gap-4 mb-6">

            {{-- Entries --}}
            <div class="flex items-center gap-2 text-sm font-semibold text-gray-700">

                <span>Show</span>

                <div class="relative w-24">
                    <select
                        wire:model.live="perPage"
                        class="w-full h-9 pl-3 pr-10
                               appearance-none
                               border border-gray-300
                               rounded-md
                               text-sm bg-white
                               focus:border-[#0F172A]
                               focus:ring-[#0F172A]"
                    >
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>

                    <svg
                        class="pointer-events-none absolute right-3 top-1/2
                               -translate-y-1/2 w-4 h-4 text-gray-500"
                        viewBox="0 0 20 20"
                        fill="currentColor"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </div>

            </div>

        </div>

    


    @if ($documents->count())

        {{-- TABLE --}}
        <div class="w-full overflow-x-auto border border-gray-300 bg-white">

            <table class="w-full min-w-[1500px] border-collapse">

                <thead>
                    <tr class="border-b border-gray-300 bg-white">

                        <th class="w-16 px-4 py-4 text-left text-sm font-bold">
                            NO.
                        </th>

                        <th class="min-w-[280px] px-4 py-4 text-left text-sm font-bold">
                            DOCUMENT
                        </th>

                        <th class="min-w-[160px] px-4 py-4 text-left text-sm font-bold">
                            DOCUMENT TYPE
                        </th>

                        <th class="min-w-[170px] px-4 py-4 text-left text-sm font-bold">
                            DATE RECEIVED
                        </th>

                        <th class="min-w-[180px] px-4 py-4 text-left text-sm font-bold">
                            UPLOADED BY
                        </th>

                        <th class="min-w-[190px] px-4 py-4 text-center text-sm font-bold">
                            FILE
                        </th>

                        <th class="min-w-[150px] px-4 py-4 text-center text-sm font-bold">
                            ACTION
                        </th>

                    </tr>
                </thead>


                <tbody>
                    @foreach ($documents as $document)

                        <tr
                            class="border-b border-gray-300
                                   {{ $loop->odd ? 'bg-[#F2F2F2]' : 'bg-white' }}"
                        >

                            {{-- Number --}}
                            <td class="px-4 py-4 text-sm font-semibold align-middle">
                                {{ $documents->firstItem() + $loop->index }}
                            </td>


                            {{-- Document --}}
                            <td class="px-4 py-4 align-middle">

                                <div class="space-y-1 text-sm">

                                    <div>
                                        <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                                            LAO No:
                                        </span>

                                        <span class="font-semibold text-gray-900">
                                            {{ $document->lao_number }}
                                        </span>
                                    </div>

                                    <div>
                                        <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                                            Office/Unit:
                                        </span>

                                        <span class="font-medium text-gray-800">
                                            {{ $document->office_unit }}
                                        </span>
                                    </div>

                                    <div>
                                        <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                                            Particulars:
                                        </span>

                                        <span class="font-medium text-gray-800">
                                            {{ $document->particulars }}
                                        </span>
                                    </div>

                                </div>

                            </td>


                            {{-- Document Type --}}
                            <td class="px-4 py-4 align-middle">

                                @if ($document->type)

                                    <span
                                        class="inline-flex items-center px-3 py-1
                                               rounded-full text-xs font-semibold text-white"
                                        style="background-color: {{ $document->type->color ?? '#059669' }};"
                                    >
                                        {{ $document->type->type_name }}
                                    </span>

                                @else

                                    <span class="text-xs italic text-gray-500">
                                        Unknown
                                    </span>

                                @endif

                            </td>


                            {{-- Date Received --}}
                            <td class="px-4 py-4 align-middle">

                                @if ($document->created_at)

                                    <span class="text-sm font-medium text-gray-800">
                                        {{ \Carbon\Carbon::parse($document->created_at)->format('F d, Y') }}
                                    </span>

                                @else

                                    <span class="text-xs italic text-gray-500">
                                        Not sent
                                    </span>

                                @endif

                            </td>

                            {{-- Uploaded By --}}
                            <td class="px-4 py-4 align-middle">

                                @if ($document->user)

                                    <div class="flex items-center gap-3">

                                        {{-- Profile Picture --}}
                                        @if ($document->user->profile_photo_url)
                                            <img
                                                src="{{ $document->user->profile_photo_url }}"
                                                alt="{{ $document->user->name }}"
                                                class="w-9 h-9 rounded-full object-cover
                                                    border border-gray-300
                                                    flex-shrink-0"
                                            >
                                        @else
                                            {{-- Fallback Avatar --}}
                                            <div
                                                class="w-9 h-9 rounded-full
                                                    bg-gray-200 border border-gray-300
                                                    flex items-center justify-center
                                                    flex-shrink-0"
                                            >
                                                <span class="text-sm font-bold text-gray-600">
                                                    {{ strtoupper(substr($document->user->name ?? 'U', 0, 1)) }}
                                                </span>
                                            </div>
                                        @endif


                                        {{-- User Information --}}
                                        <div class="flex flex-col min-w-0">

                                            <span class="text-sm font-semibold text-gray-900">
                                                {{ $document->user->name }}
                                            </span>

                                            <span class="text-xs text-gray-500 truncate">
                                                {{ $document->user->email }}
                                            </span>

                                        </div>

                                    </div>

                                @else

                                    <span class="text-xs italic text-gray-500">
                                        Unknown
                                    </span>

                                @endif

                            </td>

                            {{-- File --}}
                            <td class="px-4 py-4 align-middle">

                                @if ($document->file_path)

                                    <div class="flex items-center justify-center gap-2">

                                        {{-- View --}}
                                        @if (
                                            strtolower(
                                                pathinfo(
                                                    $document->file_path,
                                                    PATHINFO_EXTENSION
                                                )
                                            ) === 'pdf'
                                        )

                                            <a
                                                href="{{ Storage::url($document->file_path) }}"
                                                target="_blank"
                                                 class="inline-flex items-center gap-1.5
                                                bg-white hover:bg-blue-50
                                                border border-blue-600
                                                text-blue-600 hover:text-blue-700
                                                text-xs font-semibold
                                                px-3 py-1.5 rounded-md
                                                transition-colors duration-150"
                                                title="View"
                                            >
                                                <svg
                                                    class="w-4 h-4"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/>
                                                    <circle cx="12" cy="12" r="2.75"/>
                                                </svg>
                                                View
                                            </a>

                                        @endif


                                        {{-- Download --}}
                                        <a
                                            href="{{ Storage::url($document->file_path) }}"
                                            download
                                            class="inline-flex items-center gap-1.5
                                                bg-slate-800 hover:bg-slate-900
                                                text-white
                                                text-xs font-semibold
                                                px-3 py-1.5 rounded-md
                                                shadow-sm
                                                transition-colors duration-150"
                                            title="Download"
                                        >
                                            <svg
                                                class="w-4 h-4"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                viewBox="0 0 24 24"
                                            >
                                                <path d="M12 3v12m0 0 4-4m-4 4-4-4M5 18v3h14v-3"/>
                                            </svg>
                                            Download
                                        </a>

                                    </div>

                                @else

                                    <div class="text-center">
                                        <span class="text-xs italic text-gray-400">
                                            No file
                                        </span>
                                    </div>

                                @endif

                            </td>


                            {{-- Actions --}}
                            <td class="px-4 py-4 align-middle">

                                <div class="flex items-center justify-center gap-2">

                                    {{-- Accept --}}
                                    <button
                                        type="button"
                                        wire:click="acceptDocument({{ $document->document_id }})"
                                        wire:confirm="Are you sure you want to accept this document?"
                                        class="inline-flex items-center justify-center
                                            w-9 h-9 rounded-md
                                            bg-green-600
                                            hover:bg-green-700
                                            text-white
                                            transition"
                                        title="Accept"
                                    >
                                        <svg
                                            class="w-4 h-4"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M5 13l4 4L19 7"
                                            />
                                        </svg>
                                    </button>


                                    {{-- Reject --}}
                                    <button
                                        type="button"
                                        wire:click="rejectDocument({{ $document->document_id }})"
                                        wire:confirm="Are you sure you want to reject this document?"
                                        class="inline-flex items-center justify-center
                                            w-9 h-9 rounded-md
                                            bg-red-600
                                            hover:bg-red-700
                                            text-white
                                            transition"
                                        title="Reject"
                                    >
                                        <svg
                                            class="w-4 h-4"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </button>

                                </div>

                            </td>

                        </tr>

                    @endforeach
                </tbody>

            </table>

        </div>


        {{-- Pagination --}}
        <div class="mt-4">
            {{ $documents->links() }}
        </div>

    @else

        {{-- EMPTY STATE --}}
        <div class="border border-dashed border-gray-300 bg-white py-16 text-center">

            <h3 class="text-base font-semibold text-gray-800">
                No pending documents yet
            </h3>

            <p class="mt-1 text-sm text-gray-500">
                Documents marked as pending will appear here.
            </p>

        </div>

    @endif


    <x-filament-actions::modals />
</div>

</x-filament-panels::page>