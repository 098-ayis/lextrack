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

            {{-- Custom dropdown arrow --}}
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
    
    {{-- TABLE CONTROLS CONTAINER --}}
    <div class="w-full bg-white border border-gray-300 border-b-0 shadow-sm px-4 pt-4 pb-0 mb-0">
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
        <div class="w-full overflow-x-auto border border-gray-300 bg-white">

            <table class="w-full min-w-[1300px] border-collapse">

                <thead>
                    <tr class="border-b border-gray-300 bg-white">
                        <th class="w-20 px-4 py-4 text-left text-sm font-bold">
                            NO
                        </th>

                        <th class="px-4 py-4 text-left text-sm font-bold">
                            DOCUMENTS
                        </th>

                        <th class="px-4 py-4 text-left text-sm font-bold">
                            DOCUMENT TYPE
                        </th>

                        <th class="px-4 py-4 text-left text-sm font-bold">
                            ACTION TAKEN
                        </th>

                        <th class="px-4 py-4 text-left text-sm font-bold">
                            DEADLINE
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

                                <div class="space-y-1.5 text-sm">

                                    <div class="flex gap-1.5">
                                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide shrink-0">
                                            LAO No:
                                        </span>
                                        <span class="font-semibold text-gray-900">
                                            {{ $document->lao_number }}
                                        </span>
                                    </div>

                                    <div class="flex gap-1.5">
                                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide shrink-0">
                                            Office/Unit:
                                        </span>
                                        <span class="font-semibold text-gray-900">
                                            {{ $document->office_unit }}
                                        </span>
                                    </div>

                                    <div class="flex gap-1.5">
                                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wide shrink-0">
                                            Particulars:
                                        </span>
                                        <span class="font-semibold text-gray-900 leading-snug">
                                            {{ $document->particulars }}
                                        </span>
                                    </div>

                                </div>

                            </td>

                            {{-- DOCUMENT TYPE --}}
                            <td class="px-4 py-4 align-middle text-center">

                                {{-- BADGES --}}
                                <div class="flex flex-wrap items-center gap-2 mb-3">

                                    @if ($document->type)

                                        <span
                                            class="inline-flex items-center px-4 py-1
                                                rounded-full text-xs font-bold text-white"
                                            style="background-color: {{ $document->type->color }};"
                                        >
                                            {{ $document->type->type_name }}
                                        </span>

                                    @else

                                        <span class="text-xs italic text-gray-500">
                                            Unknown Document Type
                                        </span>

                                    @endif

                                </div>


                            </td>

                            {{-- Action Taken --}}
                            <td class="px-4 py-4 align-middle">

                                @if ($document->actionType)

                                    <span
                                        class="inline-flex items-center gap-1 px-3 py-1
                                            text-xs font-semibold rounded-full text-white"
                                        style="background-color: {{ $document->actionType->color }};"
                                    >
                                        {{ $document->actionType->action_name }}
                                    </span>

                                @else

                                    <span class="text-xs italic text-gray-500">
                                        No action assigned
                                    </span>

                                @endif

                            </td>

                            {{-- Deadline --}}
                            <td class="px-4 py-4 align-middle">

                                @if ($document->deadline)

                                    @php
                                        $deadline = \Carbon\Carbon::parse($document->deadline)->startOfDay();
                                        $today = now()->startOfDay();

                                        $daysRemaining = $today->diffInDays($deadline, false);

                                        if ($daysRemaining < 0) {
                                            // Deadline already passed
                                            $urgencyColor = 'bg-red-500';
                                            $urgencyLabel = 'Overdue';
                                        } elseif ($daysRemaining <= 1) {
                                            // Today or tomorrow
                                            $urgencyColor = 'bg-orange-500';
                                            $urgencyLabel = $daysRemaining === 0
                                                ? 'Due today'
                                                : 'Due tomorrow';
                                        } else {
                                            // More than 1 day remaining
                                            $urgencyColor = 'bg-emerald-500';
                                            $urgencyLabel = 'On track';
                                        }
                                    @endphp

                                    <div class="flex items-center gap-2">

                                        {{-- Urgency dot --}}
                                        <span
                                            class="w-2.5 h-2.5 rounded-full shrink-0 {{ $urgencyColor }}"
                                            title="{{ $urgencyLabel }}"
                                        ></span>

                                        {{-- Deadline --}}
                                        <span class="text-sm font-semibold text-gray-800">
                                            {{ \Carbon\Carbon::parse($document->deadline)->format('F d, Y') }}
                                        </span>

                                    </div>

                                    {{-- Urgency text --}}
                                    <div class="mt-1 ml-[18px] text-xs text-gray-500">
                                        {{ $urgencyLabel }}
                                    </div>

                                @else

                                    <span class="text-xs italic text-gray-500">
                                        No deadline set
                                    </span>

                                @endif

                            </td>                                               
                            
                            {{-- File --}}
                            <td class="px-4 py-4 align-middle">

                                

                                    <div class="flex items-center justify-center gap-2">

                                            <a
                                            href="{{ \App\Filament\Pages\ViewDocument::getUrl(['document' => $document->document_id]) }}"
                                            class="inline-flex items-center gap-1.5
                                                bg-white hover:bg-blue-50
                                                border border-blue-600
                                                text-blue-600 hover:text-blue-700
                                                text-xs font-semibold
                                                px-3 py-1.5 rounded-md
                                                transition-colors duration-150"
                                        >
                                            <svg
                                                class="w-3.5 h-3.5"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <use href="#icon-eye"/>
                                            </svg>

                                            View
                                        </a>


                                        
                                        @if ($document->file_path)

                                        {{-- Download button --}}
                                        <a
                                            href="{{ route('admin.documents.file', [
                                                'document' => $document->document_id
                                            ]) }}"
                                            download
                                            class="inline-flex items-center gap-1.5
                                                bg-slate-800 hover:bg-slate-900
                                                text-white
                                                text-xs font-semibold
                                                px-3 py-1.5 rounded-md
                                                shadow-sm
                                                transition-colors duration-150"
                                        >
                                            <svg
                                                class="w-3.5 h-3.5"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                            >
                                                <use href="#icon-download"/>
                                            </svg>

                                            Download
                                        </a>

                                    </div>

                                @else

                                    <div class="flex justify-center">
                                        <span class="text-xs italic text-gray-400">
                                            No file
                                        </span>
                                    </div>
                            
                                @endif

                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-4 align-middle">

                                <div class="flex items-center justify-center gap-2">

                                    {{-- Edit: icon only --}}
                                    {{ ($this->editDocumentAction)([
                                        'document' => $document->document_id,
                                    ]) }}

                                    {{-- Message: icon only --}}
                                    <button
                                        type="button"
                                        wire:click="messageDocument({{ $document->document_id }})"
                                        class="inline-flex items-center justify-center
                                            w-9 h-9 rounded-md
                                            border border-[#0F172A]
                                            text-[#0F172A]
                                            hover:bg-[#0F172A]
                                            hover:text-white
                                            transition-colors duration-150"
                                        title="Message"
                                    >
                                        <svg
                                            class="w-4 h-4"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <use href="#icon-chat"/>
                                        </svg>
                                    </button>

                                    {{-- Outgoing --}}
                                    <button
                                        type="button"
                                        wire:click="markAsOutgoing({{ $document->document_id }})"
                                        class="inline-flex items-center gap-1.5
                                            h-9 px-3 rounded-md
                                            bg-[#0F172A]
                                            hover:bg-[#334155]
                                            text-white
                                            text-xs font-semibold
                                            transition-colors duration-150"
                                        title="Mark as Outgoing"
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
                                                d="M5 12h14m-6-6 6 6-6 6"
                                            />
                                        </svg>

                                        Outgoing
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