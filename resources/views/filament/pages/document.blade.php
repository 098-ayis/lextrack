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

            <symbol id="icon-qr-code" viewBox="0 0 24 24">
                <path fill="none" stroke="currentColor" stroke-width="1.75"
                    stroke-linecap="round" stroke-linejoin="round"
                    d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 14h2v6h-6v-2h4zM14 18h2v2h-2z"/>
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
        $activeSection = $this->activeSection;
        $statusCounts = $this->getStatusCounts();
        $documents = $this->getDocuments($activeSection);
    @endphp

    {{-- STATUS HEADER --}}

    <div class="mb-0 w-full overflow-x-auto border border-gray-300 bg-white shadow-sm">
        <nav class="flex min-w-max items-center gap-1 p-2" aria-label="Document status">
            @foreach ([
                'pending' => 'Pending',
                'incoming' => 'Incoming',
                'outgoing' => 'Outgoing',
                'completed' => 'Completed',
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

    {{-- FILTER PILLS --}}
    <div class="mb-0 flex w-full flex-wrap items-center gap-3 border-x border-gray-300 bg-white px-3 py-7 shadow-sm sm:flex-nowrap sm:justify-start">
        {{-- Search --}}
        <div class="relative w-full sm:w-96">
            <input
                type="text"
                wire:model.live.debounce.400ms="search"
                placeholder="Search Document"
                class="w-full h-10 rounded-full border border-gray-300 bg-white pl-4 pr-11 text-sm
                       focus:border-primary-500 focus:ring-primary-500"
            >

            <svg class="absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-800">
                <use href="#icon-search"/>
            </svg>
        </div>

        {{-- Document Type Filter --}}
        <div class="relative w-full sm:w-60">
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

        {{-- Upload Date Filter --}}
        <div class="relative w-full sm:w-60">
            <input
                type="date"
                wire:model.live="dateFilter"
                aria-label="Filter by upload date"
                class="peer w-full h-10 appearance-none rounded-full border border-gray-300 bg-white pl-10 pr-4 text-sm
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

        {{-- Export and Add Document --}}
        <div class="ml-auto flex items-center gap-2">
            <a
                href="{{ route('admin.documents.export', [
                    'section' => $activeSection,
                    'search' => $search,
                    'type' => $typeFilter,
                    'date' => $dateFilter,
                ]) }}"
                download
                class="inline-flex h-10 items-center gap-2 rounded-md border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <use href="#icon-download"/>
                </svg>
                Export
            </a>
            {{ $this->addDocumentAction }}
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

        </div>
    </div>
    

    @if ($activeSection === 'pending')
        @if ($documents->count())

        {{-- TABLE --}}
        <div class="max-h-[calc(100vh-18rem)] w-full min-h-0 overflow-auto border border-gray-300 bg-white">

            <table class="w-full min-w-[1000px] lg:min-w-0 border-collapse">

                <thead class="sticky top-0 z-10 bg-white">
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

                        <th class="min-w-[180px] px-4 py-4 text-left text-sm font-bold">
                            UPLOADED BY
                        </th>

                        <th class="min-w-[190px] px-4 py-4 text-center text-sm font-bold">
                            ACTION
                        </th>

                    </tr>
                </thead>

                <tbody class="text-xs">
                    @php $currentUploadDate = null; @endphp

                    @foreach ($documents as $document)
                        @php
                            $uploadDate = $document->created_at?->format('Y-m-d');
                        @endphp

                        @if ($uploadDate !== $currentUploadDate)
                            <tr class="border-y border-blue-200 bg-blue-50">
                                <td colspan="5" class="px-4 py-2 text-xs font-bold text-blue-900">
                                    Uploaded {{ $document->created_at?->format('F d, Y') ?? 'Unknown date' }}
                                </td>
                            </tr>
                            @php $currentUploadDate = $uploadDate; @endphp
                        @endif

                        <tr
                            data-view-url="{{ \App\Filament\Pages\ViewDocument::getUrl(['document' => $document->document_id]) }}"
                            onclick="if (!event.target.closest('button, a, input, select, textarea, summary, details')) window.location.href = this.dataset.viewUrl"
                            class="border-b border-gray-300
                                   {{ $loop->odd ? 'bg-[#F2F2F2]' : 'bg-white' }} cursor-pointer hover:bg-blue-50"
                        >

                            {{-- Number --}}
                            <td class="px-4 py-4 text-xs font-semibold align-middle">
                                {{ $documents->firstItem() + $loop->index }}
                            </td>

                            {{-- Document --}}
                            <td class="px-4 py-4 align-middle">

                                <div class="space-y-1 text-xs">

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
                                                <span class="text-xs font-bold text-gray-600">
                                                    {{ strtoupper(substr($document->user->name ?? 'U', 0, 1)) }}
                                                </span>
                                            </div>
                                        @endif

                                        {{-- User Information --}}
                                        <div class="flex flex-col min-w-0">

                                            <span class="text-xs font-semibold text-gray-900">
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

                            {{-- Actions --}}
                            <td class="px-4 py-4 align-middle">

                                <div class="flex items-center justify-center gap-2">

                                    {{-- View and Download menu --}}
                                    <details class="document-options-menu relative order-last shrink-0">
                                        <summary
                                            class="flex h-9 w-7 cursor-pointer list-none items-center justify-center text-gray-700 transition hover:text-gray-900 [&::-webkit-details-marker]:hidden"
                                            title="More options"
                                        >
                                            <span class="sr-only">More options</span>
                                            <span class="flex flex-col items-center gap-0.5">
                                                <span class="h-1 w-1 rounded-full bg-current"></span>
                                                <span class="h-1 w-1 rounded-full bg-current"></span>
                                                <span class="h-1 w-1 rounded-full bg-current"></span>
                                            </span>
                                        </summary>

                                        <div class="absolute bottom-full right-0 z-50 mb-2 w-32 rounded-md border border-gray-200 bg-white p-1 text-left shadow-lg">
                                            <a
                                                href="{{ \App\Filament\Pages\ViewDocument::getUrl(['document' => $document->document_id]) }}"
                                                class="flex items-center gap-2 rounded px-3 py-2 text-xs text-gray-700 hover:bg-gray-100"
                                            >
                                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                    <use href="#icon-eye"/>
                                                </svg>
                                                View
                                            </a>

                                            @if ($document->latestVersion?->file_path)
                                                <a
                                                    href="{{ route('admin.documents.download', ['document' => $document->document_id]) }}"
                                                    download
                                                    class="flex items-center gap-2 rounded px-3 py-2 text-xs text-gray-700 hover:bg-gray-100"
                                                >
                                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                        <use href="#icon-download"/>
                                                    </svg>
                                                    Download
                                                </a>
                                            @endif

                                            <button
                                                type="button"
                                                wire:click="openQrCode({{ $document->document_id }})"
                                                class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-xs text-gray-700 hover:bg-gray-100"
                                            >
                                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                                                    <use href="#icon-qr-code"/>
                                                </svg>
                                                QR Code
                                            </button>
                                        </div>
                                    </details>

                                    {{-- Accept --}}
                                    {{ ($this->acceptDocumentAction)([
                                        'document' => $document->document_id,
                                    ]) }}

                                    {{-- Reject --}}
                                    {{ ($this->rejectDocumentAction)([
                                        'document' => $document->document_id,
                                    ]) }}

                                </div>

                            </td>

                        </tr>

                    @endforeach
                </tbody>

            </table>

        </div>

            <div class="mt-4">
                {{ $documents->links() }}
            </div>

        @else

            <div class="border border-dashed border-gray-300 bg-white py-16 text-center">
                <h3 class="text-base font-semibold text-gray-800">
                    No pending documents yet
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Documents marked as pending will appear here.
                </p>
            </div>
        @endif

    @elseif ($activeSection === 'outgoing')
        @if ($documents->count())

        {{-- TABLE --}}
        <div class="max-h-[calc(100vh-18rem)] w-full min-h-0 overflow-auto border border-gray-300 bg-white">

            <table class="w-full min-w-[1300px] lg:min-w-0 border-collapse text-center">

                <thead class="sticky top-0 z-10 bg-white">
                    <tr class="border-b border-gray-300 bg-white">

                        <th class="w-16 px-4 py-4 text-sm font-bold">
                            NO.
                        </th>

                        <th class="min-w-[280px] px-4 py-4 text-left text-sm font-bold">
                            DOCUMENT
                        </th>

                        <th class="min-w-[160px] px-4 py-4 text-sm font-bold">
                            DOCUMENT TYPE
                        </th>

                        <th class="min-w-[170px] px-4 py-4 text-sm font-bold">
                            OUTGOING DATE
                        </th>

                        <th class="min-w-[220px] px-4 py-4 text-sm font-bold">
                            SENT
                        </th>

                        <th class="min-w-[220px] px-4 py-4 text-sm font-bold">
                            RETURNED
                        </th>

                        <th class="min-w-[190px] px-4 py-4 text-sm font-bold">
                            ACTION
                        </th>

                    </tr>
                </thead>

                <tbody class="text-xs">
                    @php $currentUploadDate = null; @endphp

                    @foreach ($documents as $document)
                        @php
                            $uploadDate = $document->created_at?->format('Y-m-d');
                        @endphp

                        @if ($uploadDate !== $currentUploadDate)
                            <tr class="border-y border-blue-200 bg-blue-50">
                                <td colspan="7" class="px-4 py-2 text-left text-xs font-bold text-blue-900">
                                    Uploaded {{ $document->created_at?->format('F d, Y') ?? 'Unknown date' }}
                                </td>
                            </tr>
                            @php $currentUploadDate = $uploadDate; @endphp
                        @endif

                        <tr
                            data-view-url="{{ \App\Filament\Pages\ViewDocument::getUrl(['document' => $document->document_id]) }}"
                            onclick="if (!event.target.closest('button, a, input, select, textarea, summary, details')) window.location.href = this.dataset.viewUrl"
                            class="border-b border-gray-300
                                   {{ $loop->odd ? 'bg-[#F2F2F2]' : 'bg-white' }} cursor-pointer hover:bg-blue-50"
                        >

                            {{-- Number --}}
                            <td class="px-4 py-4 text-xs font-semibold align-middle">
                                {{ $documents->firstItem() + $loop->index }}
                            </td>

                            {{-- Document --}}
                            <td class="px-4 py-4 text-left align-middle">

                                <div class="space-y-1 text-left text-xs">

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

                            {{-- Outgoing Date --}}
                            <td class="px-4 py-4 align-middle">

                                @if ($document->outgoing_date)

                                    <span class="text-xs font-medium text-gray-800">
                                        {{ \Carbon\Carbon::parse($document->outgoing_date)->format('F d, Y') }}
                                    </span>

                                @else

                                    <span class="text-xs italic text-gray-500">
                                        No outgoing date
                                    </span>

                                @endif

                            </td>

                            {{-- Sent --}}
                            <td class="px-4 py-4 align-middle">
                                <div class="flex flex-col gap-1">
                                    <span class="text-xs font-medium text-gray-800">
                                        {{ $document->sent_to ?? 'Not set' }}
                                    </span>

                                    @if ($document->sent_date)
                                        <span class="text-[11px] text-gray-500">
                                            {{ \Carbon\Carbon::parse($document->sent_date)->format('F d, Y') }}
                                        </span>
                                    @else
                                        <span class="text-[11px] italic text-gray-500">
                                            Not sent
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- Returned --}}
                            <td class="px-4 py-4 align-middle">
                                <div class="flex flex-col gap-1">
                                    <span class="text-xs font-medium text-gray-800">
                                        {{ $document->returned_from ?? 'Not returned' }}
                                    </span>

                                    @if ($document->date_returned)
                                        <span class="text-[11px] text-gray-500">
                                            {{ \Carbon\Carbon::parse($document->date_returned)->format('F d, Y') }}
                                        </span>
                                    @else
                                        <span class="text-[11px] italic text-gray-500">
                                            Not returned
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-4 align-middle">

                                <div class="flex items-center justify-center gap-2">

                                    {{-- View and Download menu --}}
                                    <details class="document-options-menu relative order-last shrink-0">
                                        <summary
                                            class="flex h-9 w-7 cursor-pointer list-none items-center justify-center text-gray-700 transition hover:text-gray-900 [&::-webkit-details-marker]:hidden"
                                            title="More options"
                                        >
                                            <span class="sr-only">More options</span>
                                            <span class="flex flex-col items-center gap-0.5">
                                                <span class="h-1 w-1 rounded-full bg-current"></span>
                                                <span class="h-1 w-1 rounded-full bg-current"></span>
                                                <span class="h-1 w-1 rounded-full bg-current"></span>
                                            </span>
                                        </summary>

                                        <div class="absolute bottom-full right-0 z-50 mb-2 w-32 rounded-md border border-gray-200 bg-white p-1 text-left shadow-lg">
                                            <a
                                                href="{{ \App\Filament\Pages\ViewDocument::getUrl(['document' => $document->document_id]) }}"
                                                class="flex items-center gap-2 rounded px-3 py-2 text-xs text-gray-700 hover:bg-gray-100"
                                            >
                                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                    <use href="#icon-eye"/>
                                                </svg>
                                                View
                                            </a>

                                            @if ($document->latestVersion?->file_path)
                                                <a
                                                    href="{{ route('admin.documents.download', ['document' => $document->document_id]) }}"
                                                    download
                                                    class="flex items-center gap-2 rounded px-3 py-2 text-xs text-gray-700 hover:bg-gray-100"
                                                >
                                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                        <use href="#icon-download"/>
                                                    </svg>
                                                    Download
                                                </a>
                                            @endif

                                            <button
                                                type="button"
                                                wire:click="openQrCode({{ $document->document_id }})"
                                                class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-xs text-gray-700 hover:bg-gray-100"
                                            >
                                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                                                    <use href="#icon-qr-code"/>
                                                </svg>
                                                QR Code
                                            </button>
                                        </div>
                                    </details>

                                    {{-- Edit --}}
                                    {{ ($this->editDocumentAction)([
                                        'document' => $document->document_id,
                                    ]) }}

                                    {{-- Message --}}
                                    <button
                                        type="button"
                                        wire:click="messageDocument({{ $document->document_id }})"
                                        class="inline-flex items-center justify-center
                                               w-9 h-9 rounded-md
                                               border border-[#0F172A]
                                               text-[#0F172A]
                                               bg-white
                                               hover:bg-[#0F172A]
                                               hover:text-white
                                               transition"
                                        title="Message"
                                    >
                                        <svg
                                            class="w-4 h-4"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                            viewBox="0 0 24 24"
                                        >
                                            <path d="M4 5h16v11H8l-4 4V5Z"/>
                                        </svg>
                                    </button>

                                    {{-- Complete --}}
                                    {{ ($this->completeDocumentAction)([
                                        'document' => $document->document_id,
                                    ]) }}

                                </div>

                            </td>

                        </tr>

                    @endforeach
                </tbody>

            </table>

        </div>

            <div class="mt-4">
                {{ $documents->links() }}
            </div>

        @else

            <div class="border border-dashed border-gray-300 bg-white py-16 text-center">
                <h3 class="text-base font-semibold text-gray-800">
                    No outgoing documents yet
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Documents marked as outgoing will appear here.
                </p>
            </div>
        @endif

    @else
        @if ($documents->count())

        {{-- TABLE --}}
        <div class="max-h-[calc(100vh-18rem)] w-full min-h-0 overflow-auto border border-gray-300 bg-white">

            <table class="w-full {{ $activeSection === 'completed' ? 'min-w-[1000px]' : 'min-w-[1300px]' }} lg:min-w-0 border-collapse">

                <thead class="sticky top-0 z-10 bg-white">
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

                        @if ($activeSection === 'completed')
                            <th class="px-4 py-4 text-left text-sm font-bold">
                                LAST UPDATE
                            </th>
                        @endif

                        @if ($activeSection === 'rejected')
                            <th class="px-4 py-4 text-left text-sm font-bold">
                                REJECTION REASON
                            </th>
                        @endif

                        @if ($activeSection !== 'completed')
                            <th class="px-4 py-4 text-left text-sm font-bold">
                                ACTION TAKEN
                            </th>

                            <th class="px-4 py-4 text-left text-sm font-bold">
                                DEADLINE
                            </th>
                        @endif

                        <th class="w-52 px-4 py-4 text-center text-sm font-bold">
                            ACTION
                        </th>
                    </tr>
                </thead>

                <tbody class="text-xs">
                    @php $currentUploadDate = null; @endphp

                    @foreach ($documents as $document)
                        @php
                            $uploadDate = $document->created_at?->format('Y-m-d');
                        @endphp

                        @if ($uploadDate !== $currentUploadDate)
                            <tr class="border-y border-blue-200 bg-blue-50">
                                <td colspan="{{ $activeSection === 'completed' ? 5 : ($activeSection === 'rejected' ? 7 : 6) }}" class="px-4 py-2 text-xs font-bold text-blue-900">
                                    Uploaded {{ $document->created_at?->format('F d, Y') ?? 'Unknown date' }}
                                </td>
                            </tr>
                            @php $currentUploadDate = $uploadDate; @endphp
                        @endif

                    <tr 
                            data-view-url="{{ \App\Filament\Pages\ViewDocument::getUrl(['document' => $document->document_id]) }}"
                            onclick="if (!event.target.closest('button, a, input, select, textarea, summary, details')) window.location.href = this.dataset.viewUrl"
                            class="border-b border-gray-300 
                                {{ $loop->odd ? 'bg-[#F2F2F2]' : 'bg-white' }} cursor-pointer hover:bg-blue-50"
                        >

                            {{-- Number --}}
                            <td class="px-5 py-4 text-xs font-semibold align-middle">
                                {{ $documents->firstItem() + $loop->index }}
                            </td>

                            {{-- Document Details --}}
                            <td class="px-4 py-4 align-middle">

                                <div class="space-y-1.5 text-xs">

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
                                            class="inline-flex items-center px-3 py-1
                                                rounded-full text-xs font-semibold text-white"
                                            style="background-color: {{ $document->type->color ?? '#059669' }};"
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

                            @if ($activeSection === 'completed')
                                {{-- Latest Updated Date --}}
                                <td class="px-4 py-4 align-middle">
                                    <span class="text-xs font-medium text-gray-800">
                                        {{ $document->updated_at?->format('F d, Y') ?? 'Unknown date' }}
                                    </span>
                                </td>
                            @endif

                            @if ($activeSection === 'rejected')
                                <td class="px-4 py-4 align-middle">
                                    @php
                                        $latestRejection = $document->rejections
                                            ->sortByDesc('created_at')
                                            ->first();
                                    @endphp

                                    @if ($latestRejection)
                                        {{ ($this->viewRejectionReasonAction)([
                                            'rejection' => $latestRejection->rejected_id,
                                        ]) }}
                                    @else
                                        <span class="text-xs italic text-gray-500">
                                            No reason recorded
                                        </span>
                                    @endif
                                </td>
                            @endif

                            @if ($activeSection !== 'completed')
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
                                        <span class="text-xs font-semibold text-gray-800">
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
                            @endif
                            
                            {{-- Actions --}}
                            <td class="px-4 py-4 align-middle">

                                <div class="flex items-center justify-center gap-2">

                                    {{-- View and Download menu --}}
                                    <details class="document-options-menu relative order-last shrink-0">
                                        <summary
                                            class="flex h-9 w-7 cursor-pointer list-none items-center justify-center text-gray-700 transition hover:text-gray-900 [&::-webkit-details-marker]:hidden"
                                            title="More options"
                                        >
                                            <span class="sr-only">More options</span>
                                            <span class="flex flex-col items-center gap-0.5">
                                                <span class="h-1 w-1 rounded-full bg-current"></span>
                                                <span class="h-1 w-1 rounded-full bg-current"></span>
                                                <span class="h-1 w-1 rounded-full bg-current"></span>
                                            </span>
                                        </summary>

                                        <div class="absolute bottom-full right-0 z-50 mb-2 w-32 rounded-md border border-gray-200 bg-white p-1 text-left shadow-lg">
                                            <a
                                                href="{{ \App\Filament\Pages\ViewDocument::getUrl(['document' => $document->document_id]) }}"
                                                class="flex items-center gap-2 rounded px-3 py-2 text-xs text-gray-700 hover:bg-gray-100"
                                            >
                                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                    <use href="#icon-eye"/>
                                                </svg>
                                                View
                                            </a>

                                            @if ($document->latestVersion?->file_path)
                                                <a
                                                    href="{{ route('admin.documents.download', [
                                                        'document' => $document->document_id,
                                                    ]) }}"
                                                    download
                                                    class="flex items-center gap-2 rounded px-3 py-2 text-xs text-gray-700 hover:bg-gray-100"
                                                >
                                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                                        <use href="#icon-download"/>
                                                    </svg>
                                                    Download
                                                </a>
                                            @endif

                                            <button
                                                type="button"
                                                wire:click="openQrCode({{ $document->document_id }})"
                                                class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-xs text-gray-700 hover:bg-gray-100"
                                            >
                                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                                                    <use href="#icon-qr-code"/>
                                                </svg>
                                                QR Code
                                            </button>
                                        </div>
                                    </details>

                                    @if (in_array($activeSection, ['completed', 'rejected'], true))

                                        {{-- Return --}}
                                        {{ ($this->returnDocumentAction)([
                                            'document' => $document->document_id,
                                        ]) }}

                                    @else

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
                                        {{ ($this->markAsOutgoingAction)([
                                            'document' => $document->document_id,
                                        ]) }}

                                    @endif

                                </div>

                            </td>

                        </tr>

                    @endforeach
                </tbody>

            </table>

        </div>

            <div class="mt-4">
                {{ $documents->links() }}
            </div>

        @else

            <div class="border border-dashed border-gray-300 bg-white py-16 text-center">
                <h3 class="text-base font-semibold text-gray-800">
                    No incoming documents yet
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Add a new document to get started.
                </p>
            </div>
        @endif
    @endif
    

    <style>
        /* Keep the document sections visually connected without page-level gaps. */
        .fi-page-content {
            gap: 0 !important;
        }
    </style>

    @if ($showAcceptedModal)
        <div
            wire:click="redirectToIncoming"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/45 p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="document-accepted-title"
        >
            <div
                wire:click.stop
                class="w-full max-w-md rounded-2xl bg-white px-7 py-8 text-center shadow-2xl"
            >
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-blue-50">
                    <div
                        class="flex h-14 w-14 items-center justify-center rounded-full text-white shadow-lg"
                        style="background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%); box-shadow: 0 4px 10px rgba(37, 99, 235, 0.3);"
                    >
                        <svg
                            class="h-8 w-8"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2.5"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m5 12 4 4L19 7"
                            />
                        </svg>
                    </div>
                </div>

                <h2 id="document-accepted-title" class="mt-6 text-xl font-bold text-gray-900">
                    Document Accepted Successfully
                </h2>

                <p class="mt-2 text-sm leading-6 text-gray-500">
                    The document uploaded by
                    <span class="font-semibold text-gray-700">
                        {{ $acceptedDocumentUploader ?? 'the user' }}
                    </span>
                    has been accepted and is now available in Incoming documents.
                </p>

                <button
                    type="button"
                    wire:click="redirectToIncoming"
                    class="mt-7 w-full rounded-lg bg-blue-50 px-4 py-3 text-sm font-semibold text-gray-600 transition hover:bg-blue-100"
                >
                    Close
                </button>
            </div>
        </div>
    @endif

    @if ($qrCodeSvg)
        <div
            wire:click.self="closeQrCode"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/50 p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="qr-code-title"
        >
            <div class="w-full max-w-md rounded-2xl bg-white p-6 text-center shadow-2xl">
                <div class="flex items-center justify-between">
                    <h2 id="qr-code-title" class="text-lg font-bold text-gray-900">
                        Document QR Code
                    </h2>
                    <button
                        type="button"
                        wire:click="closeQrCode"
                        class="rounded-md p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-800"
                        aria-label="Close QR code"
                    >
                        <span class="text-xl leading-none">&times;</span>
                    </button>
                </div>

                <div class="mx-auto mt-5 flex h-64 w-64 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-white p-2">
                    {!! $qrCodeSvg !!}
                </div>

                <p class="mt-4 text-sm text-gray-600">
                    Scan this code to view the document status and details.
                </p>

                <button
                    type="button"
                    wire:click="closeQrCode"
                    class="mt-5 w-full rounded-lg bg-[#0F172A] px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-700"
                >
                    Close
                </button>
            </div>
        </div>
    @endif

    <script>
        document.addEventListener('click', (event) => {
            const clickedMenu = event.target.closest('.document-options-menu');

            document.querySelectorAll('.document-options-menu[open]').forEach((menu) => {
                if (menu !== clickedMenu) {
                    menu.removeAttribute('open');
                }
            });
        });
    </script>

    <x-filament-actions::modals />

</x-filament-panels::page>
