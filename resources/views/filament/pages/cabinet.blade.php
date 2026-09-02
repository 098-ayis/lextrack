<x-filament-panels::page>

    <style>
        .fi-modal .fi-btn.fi-color-primary {
            background-color: #6366f1 !important;
            color: #ffffff !important;
        }

        .fi-modal .fi-btn.fi-color-primary:hover {
            background-color: #4f46e5 !important;
        }

        .fi-modal .fi-btn.fi-color-primary:focus-visible {
            outline: 2px solid #818cf8;
            outline-offset: 2px;
        }
    </style>

    @php

        $cabinet = $this->cabinet;

        $normalizedSearch = strtolower(trim($search));

        $sizeToBytes = function (?string $size): float {
            if (! $size || ! preg_match('/([\d.]+)\s*(B|KB|MB|GB)/i', $size, $matches)) {
                return 0;
            }

            $multiplier = match (strtoupper($matches[2])) {
                'GB' => 1073741824,
                'MB' => 1048576,
                'KB' => 1024,
                default => 1,
            };

            return (float) $matches[1] * $multiplier;
        };

        $documentMatchesSearch = function (array $document) use ($normalizedSearch): bool {
            if ($normalizedSearch === '') {
                return true;
            }

            $searchableText = strtolower(implode(' ', array_filter([
                $document['name'] ?? null,
                $document['particulars'] ?? null,
                $document['lao_number'] ?? null,
                $document['type'] ?? null,
                $document['other_document_type'] ?? null,
                $document['office_unit'] ?? null,
                $document['status'] ?? null,
            ])));

            return str_contains($searchableText, $normalizedSearch);
        };

        $documentsForType = fn (string $type) => collect($cabinet[$type] ?? [])
            ->flatMap(fn (array $documents) => $documents);

        $documentTypes = collect(array_keys($cabinet))
            ->filter(function (string $type) use ($normalizedSearch, $documentsForType, $documentMatchesSearch): bool {
                return $normalizedSearch === ''
                    || str_contains(strtolower($type), $normalizedSearch)
                    || $documentsForType($type)->contains($documentMatchesSearch);
            });

        $documentTypes = match ($sortBy) {
            'date' => $documentTypes->sortByDesc(
                fn (string $type) => $documentsForType($type)
                    ->max(fn (array $document) => strtotime($document['date'] ?? '') ?: 0) ?? 0
            ),
            'size' => $documentTypes->sortByDesc(
                fn (string $type) => $documentsForType($type)
                    ->sum(fn (array $document) => $sizeToBytes($document['size'] ?? null))
            ),
            default => $documentTypes->sort(
                fn (string $first, string $second) => strcasecmp($first, $second)
            ),
        };

        // Keep Others at the end regardless of the selected sort mode.
        $documentTypes = $documentTypes
            ->reject(fn (string $type) => strcasecmp($type, 'Others') === 0)
            ->concat(
                $documentTypes->filter(fn (string $type) => strcasecmp($type, 'Others') === 0)
            )
            ->values();

        $allOffices = collect($cabinet)
            ->flatMap(fn ($type) => array_keys($type))
            ->unique()
            ->sort()
            ->values();

        $filterOptions = $currentType !== '' && isset($cabinet[$currentType])
            ? collect(array_keys($cabinet[$currentType]))->sort()->values()
            : $allOffices;

        $isRoot = $currentType === '';

        $isTypeView =
            $currentType !== ''
            && $currentOffice === '';

        $isOfficeView =
            $currentType !== ''
            && $currentOffice !== '';

        $currentDocuments = [];

        if (
            $isOfficeView &&
            isset($cabinet[$currentType][$currentOffice])
        ) {
            $currentDocuments =
                $cabinet[$currentType][$currentOffice];
        }

        $currentFolders = collect($cabinet[$currentType] ?? [])
            ->filter(function (array $documents, string $folder) use ($normalizedSearch, $documentMatchesSearch): bool {
                return $normalizedSearch === ''
                    || str_contains(strtolower($folder), $normalizedSearch)
                    || collect($documents)->contains($documentMatchesSearch);
            })
            ->when(
                $sourceFilter !== 'all',
                fn ($folders) => $folders->filter(
                    fn (array $documents, string $folder) => $folder === $sourceFilter
                )
            );

        $currentFolders = match ($sortBy) {
            'date' => $currentFolders->sortByDesc(
                fn (array $documents) => collect($documents)
                    ->max(fn (array $document) => strtotime($document['date'] ?? '') ?: 0) ?? 0
            ),
            'size' => $currentFolders->sortByDesc(
                fn (array $documents) => collect($documents)
                    ->sum(fn (array $document) => $sizeToBytes($document['size'] ?? null))
            ),
            default => $currentFolders->sortKeysUsing('strnatcasecmp'),
        };

        $currentDocuments = collect($currentDocuments)
            ->filter($documentMatchesSearch);

        $currentDocuments = match ($sortBy) {
            'date' => $currentDocuments->sortByDesc(
                fn (array $document) => strtotime($document['date'] ?? '') ?: 0
            ),
            'type' => $currentDocuments->sortBy(
                fn (array $document) => strtolower($document['type'] ?? '')
            ),
            'size' => $currentDocuments->sortByDesc(
                fn (array $document) => $sizeToBytes($document['size'] ?? null)
            ),
            default => $currentDocuments->sortBy(
                fn (array $document) => strtolower($document['name'] ?? '')
            ),
        };

        $currentDocuments = $currentDocuments->values();

    @endphp

        

    {{-- ============================================================= --}}
    {{-- MAIN CABINET --}}
    {{-- ============================================================= --}}

    <div class="space-y-4">


        {{-- ============================================================= --}}
        {{-- BREADCRUMB --}}
        {{-- ============================================================= --}}

        @if(! $isRoot)

            <div class="flex items-center gap-1.5 text-sm">

                <button
                    type="button"
                    wire:click="goToRoot"
                    class="text-gray-500 transition hover:text-indigo-600 dark:text-gray-400"
                >
                    Cabinet
                </button>

                <x-heroicon-m-chevron-right
                    class="h-4 w-4 text-gray-400"
                />

                @if($currentOffice)

                    <button
                        type="button"
                        wire:click="goToType"
                        class="text-gray-500 transition hover:text-indigo-600 dark:text-gray-400"
                    >
                        {{ $currentType }}
                    </button>

                    <x-heroicon-m-chevron-right
                        class="h-4 w-4 text-gray-400"
                    />

                    <span class="font-semibold text-gray-950 dark:text-white">
                        {{ $currentOffice }}
                    </span>

                @else

                    <span class="font-semibold text-gray-950 dark:text-white">
                        {{ $currentType }}
                    </span>

                @endif

            </div>

        @endif


        {{-- ============================================================= --}}
        {{-- HEADER --}}
        {{-- ============================================================= --}}

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">

            <div class="w-full lg:max-w-xl lg:flex-1">

                @if(! $isRoot)

                    <h2 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">

                        @if($isTypeView)
                            {{ $currentType }}
                        @else
                            {{ $currentOffice }}
                        @endif

                    </h2>

                @endif

                <div @class(['relative', 'mt-4' => ! $isRoot])>

                    <x-heroicon-o-magnifying-glass
                        class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
                    />

                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search documents, folders, or offices..."
                        class="w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-10 pr-10 text-sm shadow-sm transition-colors hover:border-gray-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:hover:border-gray-500"
                    />

                    @if($search)

                        <button
                            wire:click="clearSearch"
                            type="button"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                        >

                            <x-heroicon-m-x-mark class="h-5 w-5" />

                        </button>

                    @endif

                </div>

            </div>

            {{-- ========================================================= --}}
            {{-- ACTIONS --}}
            {{-- ========================================================= --}}

            <div class="flex flex-wrap items-center gap-2">

                {{-- SORT --}}
                <div
                    x-data="{ open: false }"
                    class="relative"
                >

                    <button
                        type="button"
                        @click="open = !open"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800"
                    >

                        Sort

                        <x-heroicon-m-chevron-down
                            class="h-4 w-4 text-gray-400"
                        />

                    </button>


                    <div
                        x-show="open"
                        x-transition
                        @click.outside="open = false"
                        class="absolute left-0 z-50 mt-2 w-52 rounded-xl border border-gray-200 bg-white p-1.5 shadow-xl dark:border-gray-700 dark:bg-gray-900"
                    >

                        <button
                            wire:click="setSort('name')"
                            @click="open = false"
                            class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-sm hover:bg-gray-100 dark:hover:bg-gray-800"
                        >

                            <span>Name</span>

                            @if($sortBy === 'name')
                                <x-heroicon-m-check class="h-4 w-4 text-indigo-600" />
                            @endif

                        </button>


                        <button
                            wire:click="setSort('date')"
                            @click="open = false"
                            class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-sm hover:bg-gray-100 dark:hover:bg-gray-800"
                        >

                            <span>Date modified</span>

                            @if($sortBy === 'date')
                                <x-heroicon-m-check class="h-4 w-4 text-indigo-600" />
                            @endif

                        </button>


                        <button
                            wire:click="setSort('type')"
                            @click="open = false"
                            class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-sm hover:bg-gray-100 dark:hover:bg-gray-800"
                        >

                            <span>Type</span>

                            @if($sortBy === 'type')
                                <x-heroicon-m-check class="h-4 w-4 text-indigo-600" />
                            @endif

                        </button>


                        <button
                            wire:click="setSort('size')"
                            @click="open = false"
                            class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-sm hover:bg-gray-100 dark:hover:bg-gray-800"
                        >

                            <span>Size</span>

                            @if($sortBy === 'size')
                                <x-heroicon-m-check class="h-4 w-4 text-indigo-600" />
                            @endif

                        </button>

                    </div>

                </div>


                {{-- VIEW --}}
                <div
                    x-data="{ open: false }"
                    class="relative"
                >

                    <button
                        type="button"
                        @click="open = !open"
                        class="inline-flex items-center gap-2 rounded-lg bg-gray-100 px-3 py-2.5 text-sm font-medium text-gray-800 transition hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-100 dark:hover:bg-gray-700"
                    >

                        <x-heroicon-o-squares-2x2 class="h-5 w-5" />

                        View

                        <x-heroicon-m-chevron-down
                            class="h-4 w-4 text-gray-400"
                        />

                    </button>


                    {{-- WINDOWS-STYLE VIEW MENU --}}
                    <div
                        x-show="open"
                        x-transition
                        @click.outside="open = false"
                        class="absolute right-0 z-50 mt-2 w-64 overflow-visible rounded-xl border border-gray-200 bg-white py-2 shadow-xl dark:border-gray-700 dark:bg-gray-900"
                    >

                        {{-- TILES --}}
                        <button
                            wire:click="setViewMode('tiles')"
                            @click="open = false"
                            class="flex w-full items-center gap-3 px-4 py-2.5 text-sm hover:bg-gray-100 dark:hover:bg-gray-800"
                        >

                            <span class="flex w-4 justify-center">

                                @if($viewMode === 'tiles')
                                    <span class="h-1.5 w-1.5 rounded-full bg-gray-600 dark:bg-gray-300"></span>
                                @endif

                            </span>

                            <x-heroicon-o-squares-2x2
                                class="h-5 w-5 text-gray-500"
                            />

                            <span>Tiles</span>

                        </button>


                        {{-- CONTENT --}}
                        <button
                            wire:click="setViewMode('content')"
                            @click="open = false"
                            class="flex w-full items-center gap-3 px-4 py-2.5 text-sm hover:bg-gray-100 dark:hover:bg-gray-800"
                        >

                            <span class="flex w-4 justify-center">

                                @if($viewMode === 'content')
                                    <span class="h-1.5 w-1.5 rounded-full bg-gray-600 dark:bg-gray-300"></span>
                                @endif

                            </span>

                            <x-heroicon-o-list-bullet
                                class="h-5 w-5 text-gray-500"
                            />

                            <span>Content</span>

                        </button>


                        <div class="my-2 border-t border-gray-200 dark:border-gray-700"></div>


                        {{-- DETAILS PANE --}}
                        <button
                            wire:click="toggleDetailsPane"
                            @click="open = false"
                            class="flex w-full items-center gap-3 px-4 py-2.5 text-sm hover:bg-gray-100 dark:hover:bg-gray-800"
                        >

                            <span class="flex w-4 justify-center">

                                @if($detailsPane)
                                    <span class="h-1.5 w-1.5 rounded-full bg-gray-600 dark:bg-gray-300"></span>
                                @endif

                            </span>

                            <x-heroicon-o-rectangle-group
                                class="h-5 w-5 text-gray-500"
                            />

                            <span>Details pane</span>

                        </button>


                        {{-- PREVIEW PANE --}}
                        <button
                            wire:click="togglePreviewPane"
                            @click="open = false"
                            class="flex w-full items-center gap-3 px-4 py-2.5 text-sm hover:bg-gray-100 dark:hover:bg-gray-800"
                        >

                            <span class="flex w-4 justify-center">

                                @if($previewPane)
                                    <span class="h-1.5 w-1.5 rounded-full bg-gray-600 dark:bg-gray-300"></span>
                                @endif

                            </span>

                            <x-heroicon-o-document-magnifying-glass
                                class="h-5 w-5 text-gray-500"
                            />

                            <span>Preview pane</span>

                        </button>


                        <div class="my-2 border-t border-gray-200 dark:border-gray-700"></div>


                        {{-- SHOW SUBMENU --}}
                        <div
                            x-data="{ show: false }"
                            class="relative"
                        >

                            <button
                                type="button"
                                @mouseenter="show = true"
                                @click="show = !show"
                                class="flex w-full items-center justify-between px-4 py-2.5 text-sm hover:bg-gray-100 dark:hover:bg-gray-800"
                            >

                                <span class="flex items-center gap-7">

                                    <span class="w-4"></span>

                                    <span>Show</span>

                                </span>

                                <x-heroicon-m-chevron-right
                                    class="h-4 w-4 text-gray-400"
                                />

                            </button>


                            {{-- SUBMENU --}}
                            <div
                                x-show="show"
                                @mouseleave="show = false"
                                class="absolute right-full top-0 mr-1 w-56 rounded-xl border border-gray-200 bg-white p-1.5 shadow-xl dark:border-gray-700 dark:bg-gray-900"
                            >

                                <button
                                    wire:click="toggleFileExtensions"
                                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm hover:bg-gray-100 dark:hover:bg-gray-800"
                                >

                                    <span class="w-4">

                                        @if($showFileExtensions)
                                            ✓
                                        @endif

                                    </span>

                                    File name extensions

                                </button>


                                <button
                                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm hover:bg-gray-100 dark:hover:bg-gray-800"
                                >

                                    <span class="w-4"></span>

                                    Hidden items

                                </button>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- FILTER --}}
                <div
                    x-data="{ open: false }"
                    class="relative"
                >

                    <button
                        type="button"
                        @click="open = !open"
                        class="rounded-lg p-2.5 text-gray-500 transition hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800"
                        title="Filter"
                    >

                        <x-heroicon-o-funnel class="h-5 w-5" />

                    </button>


                    <div
                        x-show="open"
                        x-transition
                        @click.outside="open = false"
                        class="absolute right-0 z-50 mt-2 w-64 rounded-xl border border-gray-200 bg-white p-4 shadow-xl dark:border-gray-700 dark:bg-gray-900"
                    >

                        <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                            {{ $currentType === 'Others' ? 'Filter by document type' : 'Filter by source' }}
                        </p>

                        <select
                            wire:model.live="sourceFilter"
                            class="w-full rounded-lg border-gray-300 bg-gray-50 text-sm dark:border-gray-700 dark:bg-gray-800"
                        >

                            <option value="all">
                                {{ $currentType === 'Others' ? 'All Document Types' : 'All Sources' }}
                            </option>

                            @foreach($filterOptions as $office)

                                <option value="{{ $office }}">
                                    {{ $office }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                </div>


                {{-- ADD DOCUMENT --}}
                <button
                    type="button"
                    wire:click="mountAction('addDocument')"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500"
                >

                    <x-heroicon-o-document-plus class="h-5 w-5" />

                    Add Document

                </button>

            </div>

        </div>
        {{-- ============================================================= --}}
        {{-- CONTENT AREA --}}
        {{-- ============================================================= --}}

        <div class="flex gap-4">


            {{-- MAIN CONTENT --}}
            <div class="min-w-0 flex-1">


                {{-- ===================================================== --}}
                {{-- ROOT: DOCUMENT TYPES --}}
                {{-- ===================================================== --}}

                @if($isRoot)

                    @if($viewMode === 'tiles')

                        <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">

                            @foreach($documentTypes as $type)

                                    <button
                                        wire:click="openType('{{ $type }}')"
                                        class="group rounded-xl border border-gray-200 bg-white p-5 text-left transition hover:border-indigo-300 hover:bg-indigo-50/40 hover:shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:hover:border-indigo-500 dark:hover:bg-indigo-500/5"
                                    >

                                        <x-heroicon-o-folder
                                            class="h-14 w-14 text-indigo-500"
                                        />

                                        <p class="mt-4 truncate text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $type }}
                                        </p>

                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            {{ count($cabinet[$type]) }}
                                            @if($type === 'Others')
                                                {{ Str::plural('document type', count($cabinet[$type])) }}
                                            @else
                                                {{ Str::plural('source', count($cabinet[$type])) }}
                                            @endif
                                        </p>

                                    </button>

                            @endforeach

                        </div>


                    @else

                        {{-- CONTENT VIEW --}}

                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">

                            @foreach($documentTypes as $type)

                                    <button
                                        wire:click="openType('{{ $type }}')"
                                        class="flex w-full items-center gap-4 border-b border-gray-100 px-5 py-4 text-left transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800"
                                    >

                                        <x-heroicon-o-folder
                                            class="h-9 w-9 shrink-0 text-indigo-500"
                                        />

                                        <div class="min-w-0 flex-1">

                                            <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">
                                                {{ $type }}
                                            </p>

                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                {{ count($cabinet[$type]) }}
                                                @if($type === 'Others')
                                                    {{ Str::plural('document type', count($cabinet[$type])) }}
                                                @else
                                                    {{ Str::plural('source office', count($cabinet[$type])) }}
                                                @endif
                                            </p>

                                        </div>

                                        <span class="text-xs text-gray-400">
                                            Folder
                                        </span>

                                    </button>

                            @endforeach

                        </div>

                    @endif


                {{-- ===================================================== --}}
                {{-- DOCUMENT TYPE → OFFICE / OTHERS → CUSTOM TYPE --}}
                {{-- ===================================================== --}}

                @elseif($isTypeView)

                    @if($viewMode === 'tiles')

                        <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">

                            @foreach($currentFolders as $office => $documents)

                                    <button
                                        wire:click="openOffice('{{ $office }}')"
                                        class="group rounded-xl border border-gray-200 bg-white p-5 text-left transition hover:border-indigo-300 hover:bg-indigo-50/40 hover:shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:hover:border-indigo-500 dark:hover:bg-indigo-500/5"
                                    >

                                        <x-heroicon-o-folder
                                            class="h-14 w-14 text-indigo-500"
                                        />

                                        <p class="mt-4 line-clamp-2 text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $office }}
                                        </p>

                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            {{ count($documents) }}
                                            {{ Str::plural('document', count($documents)) }}
                                        </p>

                                    </button>

                            @endforeach

                        </div>


                    @else

                        {{-- CONTENT VIEW --}}

                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">

                            @foreach($currentFolders as $office => $documents)

                                    <button
                                        wire:click="openOffice('{{ $office }}')"
                                        class="flex w-full items-center gap-4 border-b border-gray-100 px-5 py-4 text-left transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800"
                                    >

                                        <x-heroicon-o-folder
                                            class="h-9 w-9 shrink-0 text-indigo-500"
                                        />

                                        <div class="min-w-0 flex-1">

                                            <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">
                                                {{ $office }}
                                            </p>

                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                {{ count($documents) }} documents
                                            </p>

                                        </div>

                                        <span class="text-xs text-gray-400">
                                            Folder
                                        </span>

                                    </button>

                            @endforeach

                        </div>

                    @endif


                {{-- ===================================================== --}}
                {{-- OFFICE → DOCUMENTS --}}
                {{-- ===================================================== --}}

                @else

                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">

                        {{-- HEADER --}}

                        <div class="grid grid-cols-[minmax(0,1fr)_120px_160px_60px] border-b border-gray-200 bg-gray-50 px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">

                            <div>Name</div>

                            <div>Size</div>

                            <div>Date modified</div>

                            <div></div>

                        </div>


                        @forelse($currentDocuments as $document)

                            @php

                                $fileName = $document['name'];

                                $displayName = $showFileExtensions
                                    ? $fileName
                                    : pathinfo($fileName, PATHINFO_FILENAME);

                            @endphp

                                <a
                                    href="{{ route('admin.documents.file', [
                                        'document' => $document['id'],
                                        'filename' => $fileName,
                                    ]) }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="grid w-full cursor-pointer grid-cols-[minmax(0,1fr)_120px_160px_60px] items-center border-b border-gray-100 px-5 py-4 text-left transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800"
                                >

                                    {{-- DOCUMENT NAME --}}

                                    <div class="flex min-w-0 items-center gap-3">

                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-500 dark:bg-red-500/10 dark:text-red-400">

                                            @if(str_ends_with(strtolower($fileName), '.pdf'))

                                                <x-heroicon-o-document-text class="h-6 w-6" />

                                            @else

                                                <x-heroicon-o-document class="h-6 w-6" />

                                            @endif

                                        </div>


                                        <div class="min-w-0">

                                            <p class="truncate text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $displayName }}
                                            </p>

                                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                                {{ $document['type'] }}
                                            </p>

                                        </div>

                                    </div>


                                    {{-- SIZE --}}

                                    <div class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ $document['size'] }}
                                    </div>


                                    {{-- DATE --}}

                                    <div class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ $document['date'] }}
                                    </div>


                                    {{-- ACTIONS --}}

                                    <div class="flex justify-end">

                                        <span class="rounded-lg p-2 text-gray-400">
                                            <x-heroicon-m-ellipsis-horizontal class="h-5 w-5" />
                                        </span>

                                    </div>

                                </a>

                        @empty

                            <div class="px-6 py-16 text-center">

                                <x-heroicon-o-document-magnifying-glass
                                    class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600"
                                />

                                <h3 class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">
                                    No documents found
                                </h3>

                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    This folder does not contain any documents yet.
                                </p>

                            </div>

                        @endforelse

                    </div>

                @endif

            </div>


            {{-- ========================================================= --}}
            {{-- DETAILS PANE --}}
            {{-- ========================================================= --}}

            @if($detailsPane)

                <aside class="hidden w-80 shrink-0 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm lg:block dark:border-gray-700 dark:bg-gray-900">

                    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700">

                        <h3 class="font-semibold text-gray-900 dark:text-white">
                            Details
                        </h3>

                        <button
                            wire:click="toggleDetailsPane"
                            class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800"
                        >

                            <x-heroicon-m-x-mark class="h-5 w-5" />

                        </button>

                    </div>


                    <div class="p-6">

                        @if($selectedItem)

                            <div class="flex justify-center">

                                <div class="flex h-20 w-20 items-center justify-center rounded-xl bg-indigo-50 dark:bg-indigo-500/10">

                                    @if($currentOffice)

                                        <x-heroicon-o-document-text
                                            class="h-11 w-11 text-indigo-500"
                                        />

                                    @else

                                        <x-heroicon-o-folder
                                            class="h-11 w-11 text-indigo-500"
                                        />

                                    @endif

                                </div>

                            </div>


                            <h4 class="mt-5 break-words text-center font-semibold text-gray-900 dark:text-white">
                                {{ $selectedItem }}
                            </h4>


                            <div class="mt-7 space-y-5">

                                <div>

                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Type
                                    </p>

                                    <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $currentOffice ? 'Document' : 'Folder' }}
                                    </p>

                                </div>


                                @if($currentOffice)

                                    <div>

                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Location
                                        </p>

                                        <p class="mt-1 break-words text-sm font-medium text-gray-900 dark:text-white">
                                            Cabinet / {{ $currentType }} / {{ $currentOffice }}
                                        </p>

                                    </div>

                                @endif


                                <div>

                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Source
                                    </p>

                                    <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $currentOffice ?: 'Multiple offices' }}
                                    </p>

                                </div>

                            </div>

                        @else

                            <div class="py-16 text-center">

                                <x-heroicon-o-information-circle
                                    class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600"
                                />

                                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                                    Select an item to view its details.
                                </p>

                            </div>

                        @endif

                    </div>

                </aside>

            @endif


            {{-- ========================================================= --}}
            {{-- PREVIEW PANE --}}
            {{-- ========================================================= --}}

            @if($previewPane)

                <aside class="hidden w-96 shrink-0 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm lg:block dark:border-gray-700 dark:bg-gray-900">

                    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700">

                        <h3 class="font-semibold text-gray-900 dark:text-white">
                            Preview
                        </h3>

                        <button
                            wire:click="togglePreviewPane"
                            class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800"
                        >

                            <x-heroicon-m-x-mark class="h-5 w-5" />

                        </button>

                    </div>


                    <div class="flex h-[500px] items-center justify-center p-6">

                        @if($selectedItem)

                            <div class="text-center">

                                <x-heroicon-o-document-text
                                    class="mx-auto h-16 w-16 text-gray-300 dark:text-gray-600"
                                />

                                <h4 class="mt-4 break-words text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $selectedItem }}
                                </h4>

                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    Document preview will appear here.
                                </p>

                            </div>

                        @else

                            <div class="text-center">

                                <x-heroicon-o-document-magnifying-glass
                                    class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600"
                                />

                                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                                    Select a document to preview it.
                                </p>

                            </div>

                        @endif

                    </div>

                </aside>

            @endif

        </div>

    </div>

</x-filament-panels::page>
