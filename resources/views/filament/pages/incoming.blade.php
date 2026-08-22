<x-filament-panels::page>

    {{-- Icons --}}
    <svg width="0" height="0" style="position:absolute">
        <defs>
            <symbol id="icon-search" viewBox="0 0 24 24">
                <path fill="none" stroke="currentColor" stroke-width="1.75"
                    stroke-linecap="round" stroke-linejoin="round"
                    d="M21 21l-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z"/>
            </symbol>

            <symbol id="icon-filter" viewBox="0 0 24 24">
                <path fill="none" stroke="currentColor" stroke-width="1.75"
                    stroke-linecap="round" stroke-linejoin="round"
                    d="M3 5h18M6 12h12M10 19h4"/>
            </symbol>

            <symbol id="icon-plus" viewBox="0 0 24 24">
                <path fill="none" stroke="currentColor" stroke-width="1.75"
                    stroke-linecap="round" stroke-linejoin="round"
                    d="M12 5v14M5 12h14"/>
            </symbol>

            <symbol id="icon-download" viewBox="0 0 24 24">
                <path fill="none" stroke="currentColor" stroke-width="1.75"
                    stroke-linecap="round" stroke-linejoin="round"
                    d="M12 3v12m0 0 4-4m-4 4-4-4M5 18v3h14v-3"/>
            </symbol>

            <symbol id="icon-eye" viewBox="0 0 24 24">
                <path fill="none" stroke="currentColor" stroke-width="1.75"
                    stroke-linecap="round" stroke-linejoin="round"
                    d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/>
                <circle cx="12" cy="12" r="2.75"
                    fill="none" stroke="currentColor" stroke-width="1.75"/>
            </symbol>

            <symbol id="icon-pencil" viewBox="0 0 24 24">
                <path fill="none" stroke="currentColor" stroke-width="1.75"
                    stroke-linecap="round" stroke-linejoin="round"
                    d="m16.5 4.5 3 3L8 19l-4 1 1-4L16.5 4.5Z"/>
            </symbol>

            <symbol id="icon-chat" viewBox="0 0 24 24">
                <path fill="none" stroke="currentColor" stroke-width="1.75"
                    stroke-linecap="round" stroke-linejoin="round"
                    d="M4 5h16v11H8l-4 4V5Z"/>
            </symbol>

            <symbol id="icon-folder" viewBox="0 0 24 24">
                <path fill="none" stroke="currentColor" stroke-width="1.75"
                    stroke-linecap="round" stroke-linejoin="round"
                    d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/>
            </symbol>
        </defs>
    </svg>

    @php
        $stats = $this->getStats();
        $documents = $this->getDocuments();
    @endphp


    {{-- STAT CARDS --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-lg border-t-4 border-primary-600 p-4 shadow-sm">
            <p class="text-xs font-semibold text-gray-500">TOTAL DOCUMENTS</p>
            <p class="text-3xl font-bold mt-1">{{ $stats['total'] }}</p>
        </div>

        <div class="bg-white rounded-lg border-t-4 border-amber-400 p-4 shadow-sm">
            <p class="text-xs font-semibold text-gray-500">PENDING</p>
            <p class="text-3xl font-bold mt-1">{{ $stats['pending'] }}</p>
        </div>

        <div class="bg-white rounded-lg border-t-4 border-indigo-500 p-4 shadow-sm">
            <p class="text-xs font-semibold text-gray-500">ACTIVE</p>
            <p class="text-3xl font-bold mt-1">{{ $stats['active'] }}</p>
        </div>

        <div class="bg-white rounded-lg border-t-4 border-green-500 p-4 shadow-sm">
            <p class="text-xs font-semibold text-gray-500">COMPLETED</p>
            <p class="text-3xl font-bold mt-1">{{ $stats['completed'] }}</p>
        </div>
    </div>


    {{-- SEARCH + FILTER --}}
    <div class="flex flex-wrap items-center gap-3 mb-0">

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

            <svg class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-800">
                <use href="#icon-search"/>
            </svg>
        </div>


        {{-- Filter --}}
        <div class="relative w-full sm:w-52">
            <select
                wire:model.live="statusFilter"
                class="w-full h-10 pl-4 pr-12 rounded-full
                    border border-gray-300
                    bg-white text-sm text-gray-500
                    appearance-none
                    focus:border-primary-500 focus:ring-primary-500"
            >
                <option value="">Filter</option>
                <option value="pending">Pending</option>
                <option value="in_progress">In Progress</option>
                <option value="completed">Completed</option>
                <option value="returned">Returned</option>
                <option value="archived">Archived</option>
            </select>

            {{-- Custom dropdown arrow --}}
            <svg
                class="pointer-events-none absolute right-2 top-1/2
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
    
    {{-- TABLE CONTROLS CONTAINER --}}
    <div class="bg-white border border-gray-300 border-b-0 shadow-sm px-4 pt-4 pb-0 mb-0">

        <div class="flex flex-wrap items-center justify-between gap-4 pb-4">

            {{-- Entries --}}
            <div class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                <span>Show</span>

                <div class="relative w-24">
                    <select
                        wire:model.live="perPage"
                        class="w-full h-9 pl-3 pr-10 py-1
                            border border-gray-300 rounded-md
                            text-sm bg-white appearance-none
                            focus:border-[#0F172A] focus:ring-[#0F172A]"
                    >
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>

                    {{-- Custom dropdown arrow --}}
                    <svg
                        class="pointer-events-none absolute right-2 top-1/2
                            -translate-y-1/2 w-4 h-4 text-gray-600"
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

            {{-- Add Document --}}
            {{ $this->addDocumentAction }}

        </div>

    

    @if ($documents->count())

        {{-- TABLE --}}
        <div class="overflow-x-auto border border-gray-300 bg-white">

            <table class="w-full border-collapse">

                <thead>
                    <tr class="border-b border-gray-300 bg-white">
                        <th class="w-20 px-4 py-4 text-left text-sm font-bold">
                            NO
                        </th>

                        <th class="px-4 py-4 text-left text-sm font-bold">
                            DOCUMENTS
                        </th>

                        <th class="px-4 py-4 text-left text-sm font-bold">
                            ^
                        </th>

                        <th class="px-4 py-4 text-left text-sm font-bold">
                            ACTION TAKEN
                        </th>

                        <th class="w-64 px-4 py-4 text-center text-sm font-bold">
                            FILE
                        </th>

                        <th class="w-52 px-4 py-4 text-center text-sm font-bold">
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
                            <td class="px-5 py-4 text-sm font-semibold align-middle">
                                {{ $documents->firstItem() + $loop->index }}
                            </td>

                            {{-- Document Details --}}
                            <td class="px-4 py-4 align-middle">

                                {{-- BADGES --}}
                                <div class="flex flex-wrap items-center gap-2 mb-3">

                                    {{-- Upload type --}}
                                    @php
                                        $uploaderRole = strtolower($document->user?->role_name ?? '');

                                        $uploadStyle = match ($uploaderRole) {
                                            'client' => 'bg-emerald-200 text-emerald-900',
                                            'admin' => 'bg-emerald-600 text-white',
                                            default => 'bg-emerald-100 text-emerald-800',
                                        };

                                        $uploadLabel = match ($uploaderRole) {
                                            'client' => 'Client Upload',
                                            'admin' => 'Staff Upload',
                                            default => 'Unknown Uploader',
                                        };
                                    @endphp

                                    <span
                                        class="inline-flex items-center px-3 py-1
                                            rounded-full text-[11px] font-semibold
                                            tracking-wide {{ $uploadStyle }}"
                                    >
                                        {{ $uploadLabel }}
                                    </span>


                                    {{-- Document type --}}
                                    @php
                                        $documentType = $document->type?->type_name ?? 'Unknown';

                                        $documentTypeColor = match ($document->type_id) {
                                            1 => 'bg-emerald-100 text-emerald-800',
                                            2 => 'bg-emerald-200 text-emerald-900',
                                            3 => 'bg-emerald-300 text-emerald-950',
                                            4 => 'bg-emerald-400 text-emerald-950',
                                            5 => 'bg-emerald-500 text-white',
                                            6 => 'bg-emerald-600 text-white',
                                            7 => 'bg-emerald-700 text-white',
                                            8 => 'bg-emerald-800 text-white',
                                            default => 'bg-emerald-600 text-white',
                                        };
                                    @endphp

                                    <span
                                        class="inline-flex items-center px-4 py-1
                                            rounded-full text-xs font-bold
                                            {{ $documentTypeColor }}"
                                    >
                                        {{ $documentType }}
                                    </span>

                                </div>


                                <div class="text-sm leading-tight font-semibold">

                                    <div>
                                        LAO NO:
                                        <span class="font-bold">
                                            {{ $document->lao_number }}
                                        </span>
                                    </div>

                                    <div>
                                        Office/Unit:
                                        <span class="font-bold">
                                            {{ $document->office_unit }}
                                        </span>
                                    </div>

                                    <div>
                                        Particulars:
                                        <span class="font-bold">
                                            {{ $document->particulars }}
                                        </span>
                                    </div>

                                </div>

                            </td>

                            {{-- Upload Details --}}
                            <td class="px-4 py-4 align-middle text-center">

                                {{-- Uploaded Date --}}
                                <div class="text-sm italic font-medium text-gray-500">
                                    uploaded at
                                    {{ optional($document->created_at)->format('d, F Y') }}
                                </div>

                                {{-- File Name --}}
                                <div class="mt-2">
                                    <div class="text-[#6366F1] font-semibold text-sm">
                                        {{ $document->file_path
                                            ? basename($document->file_path)
                                            : 'No file'
                                        }}
                                    </div>
                                </div>

                            </td>

                            {{-- Action Taken --}}
                            <td class="px-4 py-4 align-middle">
                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full {{ $document->statusClasses() }}">
                                    {{ $document->actionType?->action_name ?? 'No action assigned' }}
                                </span>
                            </td>

                            {{-- File --}}
                            <td class="px-4 py-4 align-middle">

                                @if ($document->file_path)

                                    <div class="flex flex-col items-center gap-2">

                                        <a
                                            href="{{ Storage::url($document->file_path) }}"
                                            download
                                            class="inline-flex items-center gap-1
                                                    bg-[#0F172A] hover:bg-[#1E293B]
                                                    px-2 py-2 text-white
                                                    text-xs font-medium transition"
                                        >
                                            <svg class="w-4 h-4">
                                                <use href="#icon-download"/>
                                            </svg>

                                            Download File
                                        </a>


                                        {{-- Only show View if PDF --}}
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
                                                class="inline-flex items-center gap-1
                                                        bg-[#0F172A] hover:bg-[#1E293B]
                                                        text-white text-xs
                                                        px-3 py-1 transition"
                                            >
                                                <svg class="w-4 h-4">
                                                    <use href="#icon-eye"/>
                                                </svg>

                                                View
                                            </a>

                                        @else

                                            <span class="text-[10px] text-red-500">
                                                File cannot be shown because it's not PDF
                                            </span>

                                        @endif

                                    </div>

                                @else

                                    <span class="text-xs text-gray-400">
                                        No file
                                    </span>

                                @endif

                            </td>


                            {{-- Actions --}}
                            <td class="px-4 py-4 align-middle">

                                <div class="flex justify-center gap-2">

                                    {{ ($this->editDocumentAction)([
                                        'document' => $document->document_id,
                                    ]) }}


                                    <button
                                        type="button"
                                        wire:click="messageDocument({{ $document->id }})"
                                        class="inline-flex items-center gap-1
                                                rounded-full
                                                bg-[#0F172A] hover:bg-[#1E293B]
                                                text-white
                                                px-3 py-2 text-xs font-medium"
                                    >
                                        <svg class="w-4 h-4">
                                            <use href="#icon-chat"/>
                                        </svg>

                                        Message
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
        <div
            class="border border-dashed border-gray-300
                   bg-white rounded-lg
                   py-16 text-center"
        >

            <svg class="w-12 h-12 mx-auto text-gray-300">
                <use href="#icon-folder"/>
            </svg>

            <h3 class="mt-4 text-base font-semibold text-gray-800">
                No incoming documents yet
            </h3>

            <p class="mt-1 text-sm text-gray-500">
                Add a new document to get started.
            </p>


        </div>

    @endif
    </div>

    <x-filament-actions::modals />

</x-filament-panels::page>