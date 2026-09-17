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
        .cabinet-search:focus {
            outline: none;
            border-color: #d1d5db;
            box-shadow: 0 2px 8px rgb(15 23 42 / 12%);
        }

        .dark .cabinet-search:focus {
            border-color: #4b5563;
            box-shadow: 0 2px 8px rgb(0 0 0 / 25%);
        }
        .cabinet-toolbar-control {
            height: 40px;
            border: 0;
            background-color: #f3f4f6;
            box-shadow: 0 1px 2px rgb(15 23 42 / 5%);
        }
        .cabinet-toolbar-control:hover { background-color: #e5e7eb; }
        .cabinet-toolbar-control:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px rgb(99 102 241 / 20%);
        }
        .dark .cabinet-toolbar-control { background-color: #1f2937; }
        .dark .cabinet-toolbar-control:hover { background-color: #374151; }
        .cabinet-explorer-toolbar .cabinet-toolbar-control { background: transparent; box-shadow: none; }
        .cabinet-explorer-toolbar .cabinet-source-filter {
            width: 132px;
            border: 1px solid #d1d5db;
            background: #fff;
            box-shadow: 0 1px 2px rgb(15 23 42 / 6%);
        }
        .dark .cabinet-explorer-toolbar .cabinet-source-filter {
            border-color: #4b5563;
            background: #1f2937;
        }
        .cabinet-explorer-toolbar .cabinet-toolbar-control:hover { background: #f3f4f6; }
        .cabinet-icon-action { display: inline-flex; align-items: center; justify-content: center; width: 40px; padding: 8px; color: #64748b; }
        .cabinet-icon-action:disabled { opacity: .35; cursor: default; }
        .cabinet-toolbar-divider { width: 1px; height: 32px; background: #e5e7eb; margin: 0 4px; }
        .dark .cabinet-explorer-toolbar .cabinet-toolbar-control:hover { background: #374151; }
        /* Pull the Cabinet workspace closer to its page title. */
        .fi-page-content:has(> .cabinet-page-content) {
            margin-top: -4rem;
        }
        .cabinet-page-content { overflow-wrap: anywhere; }
        .cabinet-explorer-toolbar { min-width: 0; }
        @media (max-width: 767px) {
            .fi-page-content:has(> .cabinet-page-content) { margin-top: 0; }
            .cabinet-toolbar-divider { display: none; }
            .cabinet-explorer-toolbar .cabinet-toolbar-control { min-height: 44px; }
            .cabinet-list-heading { display: none; }
            .cabinet-list-row {
                grid-template-columns: minmax(0, 1fr) auto;
                gap: 8px 12px;
                padding: 12px;
            }
            .cabinet-list-row > :first-child { grid-column: 1 / -1; }
            .cabinet-list-row > :nth-child(3) { grid-column: 1; }
            .cabinet-list-row > :last-child { grid-column: 2; grid-row: 2 / 4; }
        }
    </style>

    <div x-data="{ open: false, item: {}, x: 0, y: 0 }"
         @cabinet-context.window="item = $event.detail; x = Math.max(8, Math.min(item.x, window.innerWidth - 210)); y = Math.max(8, Math.min(item.y, window.innerHeight - 310)); open = true"
         @click.outside="open = false" @keydown.escape.window="open = false" @scroll.window="open = false">
        <div x-show="open" x-cloak :style="{ position: 'fixed', left: x + 'px', top: y + 'px', zIndex: 100, width: '200px' }" class="rounded-lg bg-white p-2 shadow-xl dark:bg-gray-800" aria-label="Document actions">
            <a :href="item.url" target="_blank" rel="noopener" @click="open = false" class="block rounded-lg px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">Open</a>
            <a :href="item.url" download @click="open = false" class="block rounded-lg px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">Download</a>
            <button type="button" @click="$wire.copyToClipboard(item.id, 'copy'); open = false" class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700">Copy</button>
            <button type="button" @click="$wire.mountAction('renameDocument'); open = false" class="block w-full rounded-lg px-3 py-2 text-left text-sm">Rename</button>
            @if ($currentType === 'Recycle Bin')
                <button type="button" @click="$wire.restoreCabinetDocument(); open = false" class="block w-full rounded-lg px-3 py-2 text-left text-sm">Restore</button>
            @else
                <button type="button" @click="$wire.mountAction('archiveCabinetDocument'); open = false" class="block w-full rounded-lg px-3 py-2 text-left text-sm">Archive</button>
                <button type="button" @click="$wire.mountAction('deleteCabinetDocument'); open = false" class="block w-full rounded-lg px-3 py-2 text-left text-sm">Delete</button>
            @endif
            @if ($clipboardDocumentId)
                <button type="button" @click="$wire.pasteDocument(); open = false" class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700">Paste</button>
            @endif
        </div>
    </div>

    <div x-data="{ open: false, folder: {}, x: 0, y: 0 }" @cabinet-folder-context.window="folder = $event.detail; x = Math.max(8, Math.min(folder.x, window.innerWidth - 190)); y = Math.max(8, Math.min(folder.y, window.innerHeight - 180)); open = true" @click.outside="open = false" @keydown.escape.window="open = false">
        <div x-show="open" x-cloak :style="{ position: 'fixed', left: x + 'px', top: y + 'px', zIndex: 101, width: '180px' }" class="rounded-lg bg-white p-2 shadow-xl dark:bg-gray-800">
            @if ($currentType === 'Recycle Bin')
                <button type="button" @click="$wire.selectFolder(folder.type, folder.office); $wire.restoreFolder(); open = false" class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700">Restore</button>
            @else
                <button type="button" @click="$wire.selectFolder(folder.type, folder.office); $wire.copyFolderToClipboard(folder.type, folder.office); open = false" class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700">Copy</button>
                <button type="button" @click="$wire.selectFolder(folder.type, folder.office); $wire.mountAction('renameFolder'); open = false" class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700">Rename</button>
                <button type="button" @click="$wire.selectFolder(folder.type, folder.office); $wire.mountAction('deleteFolder'); open = false" class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700">Delete</button>
            @endif
            @if ($clipboardDocumentId || $clipboardFolderType)
                <button type="button" @click="folderPaste = @js((bool) $clipboardFolderType); folderPaste ? $wire.mountAction('pasteFolder') : $wire.pasteDocument(); open = false" class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700">Paste</button>
            @endif
        </div>
    </div>

    @php

        $filterOptions = collect($this->cabinet)
            ->flatMap(fn ($folders) => array_keys($folders))->unique()->sort()->values();
        $cabinet = $this->cabinet;
        if (!$this instanceof \App\Filament\Pages\RecycleBin) { unset($cabinet['Recycle Bin']); }
        $nestedFolderNames = \Illuminate\Support\Facades\DB::table('cabinet_folders')
            ->whereNotNull('parent_type')
            ->pluck('name');
        if ($sourceFilter !== 'all') {
            $cabinet = collect($cabinet)->map(fn ($folders) =>
                array_filter($folders, fn ($office) => $office === $sourceFilter, ARRAY_FILTER_USE_KEY)
            )->filter(fn ($folders) => count($folders) > 0)->all();
        }

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
                $document['office_unit'] ?? null,
                $document['status'] ?? null,
            ])));

            return str_contains($searchableText, $normalizedSearch);
        };

        $documentsForType = fn (string $type) => collect($cabinet[$type] ?? [])
            ->flatMap(fn (array $documents) => $documents);

        $documentTypes = collect(array_keys($cabinet))
            ->reject(fn (string $type) => $nestedFolderNames->contains($type))
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
            });

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
            'size' => $currentDocuments->sortByDesc(
                fn (array $document) => $sizeToBytes($document['size'] ?? null)
            ),
            default => $currentDocuments->sortBy(
                fn (array $document) => strtolower($document['name'] ?? '')
            ),
        };

        $currentDocuments = $currentDocuments->values();

        $selectedDocument = $currentDocuments->firstWhere('id', $selectedDocumentId);
        $customFolder = $currentType !== '' ? \Illuminate\Support\Facades\DB::table('cabinet_folders')->where('name', $currentType)->first() : null;
        $folderBreadcrumbs = collect($this->folderBreadcrumbs());

        $childFolders = $currentType !== '' ? \Illuminate\Support\Facades\DB::table('cabinet_folders')->where('parent_type', $currentType)->where(function ($query) use ($currentOffice) { $currentOffice !== '' ? $query->where('parent_office', $currentOffice) : $query->whereNull('parent_office'); })->orderBy('name')->get() : collect();

    @endphp

        

    {{-- ============================================================= --}}
    {{-- MAIN CABINET --}}
    {{-- ============================================================= --}}

    <div class="cabinet-page-content min-w-0 space-y-4">


        {{-- ============================================================= --}}
        {{-- BREADCRUMB --}}
        {{-- ============================================================= --}}

        @if(! $isRoot)

            <div class="flex flex-wrap items-center gap-2 text-sm">



                <button
                    type="button"
                    wire:click="goToRoot"
                    class="text-gray-500 transition hover:text-indigo-600 dark:text-gray-400"
                >
                    Cabinet
                </button>

                @if ($customFolder)
                    @foreach ($folderBreadcrumbs as $folderName)
                        <x-heroicon-m-chevron-right class="h-4 w-4 text-gray-400" />
                        @if (! $loop->last)
                            <button type="button" wire:click="openType(@js($folderName))" class="text-gray-500 transition hover:text-indigo-600 dark:text-gray-400">{{ $folderName }}</button>
                        @else
                            <span class="font-semibold text-gray-950 dark:text-white">{{ $folderName }}</span>
                        @endif
                    @endforeach
                @else
                    <x-heroicon-m-chevron-right class="h-4 w-4 text-gray-400" />
                    @if($currentOffice)
                        <button type="button" wire:click="goToType" class="text-gray-500 transition hover:text-indigo-600 dark:text-gray-400">{{ $currentType }}</button>
                        <x-heroicon-m-chevron-right class="h-4 w-4 text-gray-400" />
                        <span class="font-semibold text-gray-950 dark:text-white">{{ $currentOffice }}</span>
                    @else
                        <span class="font-semibold text-gray-950 dark:text-white">{{ $currentType }}</span>
                    @endif
                @endif

            </div>

        @endif


        {{-- ============================================================= --}}
        {{-- HEADER --}}
        {{-- ============================================================= --}}

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">

            <div class="w-full lg:max-w-xl lg:flex-1">

                @if(! $isRoot && $currentType !== 'Recycle Bin')

                    <h2 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">

                        @if ($customFolder || $isTypeView)
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
                        class="cabinet-search w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-10 pr-10 text-sm shadow-sm transition-colors hover:border-gray-400 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:hover:border-gray-500"
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

            <div class="cabinet-explorer-toolbar flex flex-wrap items-center gap-2">


                <span class="cabinet-toolbar-divider" aria-hidden="true"></span>
                <button type="button" wire:click="{{ $selectedFolderType ? "copyFolderToClipboard(".Illuminate\Support\Js::from($selectedFolderType).", ".Illuminate\Support\Js::from($selectedFolderOffice).")" : "copyToClipboard(".($selectedDocumentId ?? 0).")" }}" @disabled(!$selectedDocumentId && !$selectedFolderType || $currentType === 'Recycle Bin') class="cabinet-toolbar-control cabinet-icon-action" aria-label="Copy" title="Copy"><x-heroicon-o-square-2-stack class="h-5 w-5" /></button>
                <button type="button" wire:click="{{ $clipboardFolderType ? "mountAction('pasteFolder')" : "pasteDocument" }}" @disabled(!$clipboardDocumentId && !$clipboardFolderType || $currentType === 'Recycle Bin') class="cabinet-toolbar-control cabinet-icon-action" aria-label="Paste" title="Paste"><x-heroicon-o-clipboard class="h-5 w-5" /></button>

                <button type="button" wire:click="mountAction('{{ $selectedFolderType ? 'deleteFolder' : 'deleteCabinetDocument' }}')" @disabled(!$selectedDocumentId && !$selectedFolderType || $currentType === 'Recycle Bin') class="cabinet-toolbar-control cabinet-icon-action" aria-label="Delete" title="Delete"><x-heroicon-o-trash class="h-5 w-5" /></button>
                <span class="cabinet-toolbar-divider" aria-hidden="true"></span>


                <select wire:model.live="sourceFilter" aria-label="Filter by office or source" class="cabinet-source-filter cabinet-toolbar-control rounded-lg px-3 py-2.5 text-sm font-medium text-gray-800 dark:text-gray-100">
                    <option value="all">All sources</option>
                    @foreach ($filterOptions as $office)
                        <option value="{{ $office }}">{{ $office }}</option>
                    @endforeach
                </select>

                {{-- SORT --}}
                <div
                    x-data="{ open: false }"
                    class="relative"
                >

                    <button
                        type="button"
                        @click="open = !open"
                        class="cabinet-toolbar-control inline-flex items-center gap-1.5 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-800 transition dark:text-gray-100"
                    >

                        <x-heroicon-o-arrows-up-down class="h-5 w-5" /> Sort

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
                        class="cabinet-toolbar-control inline-flex items-center gap-2 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-800 transition dark:text-gray-100"
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




                @if (!$this instanceof \App\Filament\Pages\RecycleBin)
                <div x-data="{ open: false }" class="relative" @click.outside="open = false" @keydown.escape.window="open = false">
                    <button type="button" @click="open = !open" :aria-expanded="open" class="inline-flex items-center gap-2 rounded-lg bg-indigo-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-600">
                        <x-heroicon-o-plus-circle class="h-5 w-5" /> New <x-heroicon-m-chevron-down class="h-4 w-4" />
                    </button>
                    <div x-show="open" x-cloak class="absolute right-0 z-50 mt-2 rounded-lg bg-white p-2 shadow-xl dark:bg-gray-800" style="min-width:180px">
                        <button type="button" @click="open = false" wire:click="prepareAddFolder" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm"><x-heroicon-o-folder-plus class="h-5 w-5" /> Folder</button>
                        <button type="button" @click="open = false" wire:click="prepareAddDocument" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm"><x-heroicon-o-document-plus class="h-5 w-5" /> Document</button>
                    </div>
                </div>
                @endif
            </div>

        </div>
        {{-- ============================================================= --}}
        {{-- CONTENT AREA --}}
        {{-- ============================================================= --}}

        <div class="flex flex-col gap-4 lg:flex-row">


            {{-- MAIN CONTENT --}}
            <div class="min-w-0 flex-1">


                {{-- ===================================================== --}}
                {{-- ROOT: DOCUMENT TYPES --}}
                {{-- ===================================================== --}}

                @if($isRoot)

                    @if($viewMode === 'tiles')

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">

                            @foreach($documentTypes as $type)

                                    <button
                                        x-data="{ clickTimer: null }"
                                        @click="clearTimeout(clickTimer); clickTimer = setTimeout(() => $wire.selectFolder(@js($type)), 220)"
                                        @dblclick="clearTimeout(clickTimer); $wire.openType(@js($type))"
                                        wire:key="root-type-tile-{{ $type }}" @contextmenu.prevent="$dispatch('cabinet-folder-context', { type: @js($type), office: null, x: $event.currentTarget.getBoundingClientRect().right + 8, y: $event.currentTarget.getBoundingClientRect().top })"
                                        class="group rounded-xl border bg-white p-5 text-left transition {{ $selectedFolderType === $type && $selectedFolderOffice === null ? 'border-violet-500 ring-2 ring-violet-500/30' : 'border-gray-200' }} hover:border-indigo-300 hover:bg-indigo-50/40 hover:shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:hover:border-indigo-500 dark:hover:bg-indigo-500/5"
                                    >

                                        <x-dynamic-component :component="$type === 'Recycle Bin' ? 'heroicon-o-trash' : 'heroicon-o-folder'"
                                            class="h-14 w-14 text-indigo-500"
                                        />

                                        <p class="mt-4 truncate text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $type }}
                                        </p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $this->folderFileCount($type) }} {{ Str::plural('file', $this->folderFileCount($type)) }}</p>

                                    </button>

                            @endforeach

                        </div>


                    @else

                        {{-- CONTENT VIEW --}}

                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">

                            @foreach($documentTypes as $type)

                                    <button
                                        x-data="{ clickTimer: null }"
                                        @click="clearTimeout(clickTimer); clickTimer = setTimeout(() => $wire.selectFolder(@js($type)), 220)"
                                        @dblclick="clearTimeout(clickTimer); $wire.openType(@js($type))"
                                        wire:key="root-type-content-{{ $type }}" @contextmenu.prevent="$dispatch('cabinet-folder-context', { type: @js($type), office: null, x: $event.currentTarget.getBoundingClientRect().right + 8, y: $event.currentTarget.getBoundingClientRect().top })"
                                        class="flex w-full items-center gap-4 border-b {{ $selectedFolderType === $type && $selectedFolderOffice === null ? 'ring-2 ring-inset ring-violet-500' : '' }} border-gray-100 px-5 py-4 text-left transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800"
                                    >

                                        <x-dynamic-component :component="$type === 'Recycle Bin' ? 'heroicon-o-trash' : 'heroicon-o-folder'"
                                            class="h-9 w-9 shrink-0 text-indigo-500"
                                        />

                                        <div class="min-w-0 flex-1">

                                            <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">
                                                {{ $type }}
                                            </p>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $this->folderFileCount($type) }} {{ Str::plural('file', $this->folderFileCount($type)) }}</p>

                                        </div>

                                    </button>

                            @endforeach

                        </div>

                    @endif


                {{-- ===================================================== --}}
                {{-- DOCUMENT TYPE → OFFICE / OTHERS → CUSTOM TYPE --}}
                {{-- ===================================================== --}}

                @elseif($isTypeView)

                    @if($viewMode === 'tiles')

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">

                            @foreach($currentFolders as $office => $documents)

                                    <button
                                        x-data="{ clickTimer: null }"
                                        @click="clearTimeout(clickTimer); clickTimer = setTimeout(() => $wire.selectFolder(@js($currentType), @js($office)), 220)"
                                        @dblclick="clearTimeout(clickTimer); $wire.openOffice(@js($office))"
                                        wire:key="office-tile-{{ $office }}" @contextmenu.prevent="$dispatch('cabinet-folder-context', { type: @js($currentType), office: @js($office), x: $event.currentTarget.getBoundingClientRect().right + 8, y: $event.currentTarget.getBoundingClientRect().top })"
                                        class="group rounded-xl border border-gray-200 bg-white p-5 text-left transition hover:border-indigo-300 hover:bg-indigo-50/40 hover:shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:hover:border-indigo-500 dark:hover:bg-indigo-500/5 {{ $selectedFolderType === $currentType && $selectedFolderOffice === $office ? 'border-violet-500 ring-2 ring-violet-500/30' : 'border-gray-200' }}"
                                    >

                                        <x-heroicon-o-folder
                                            class="h-14 w-14 text-indigo-500"
                                        />

                                        <p class="mt-4 line-clamp-2 text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $office }}
                                        </p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ count($documents) }} {{ Str::plural('file', count($documents)) }}</p>

                                    </button>

                            @endforeach

                        </div>


                    @else

                        {{-- CONTENT VIEW --}}

                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">

                            @foreach($currentFolders as $office => $documents)

                                    <button
                                        x-data="{ clickTimer: null }"
                                        @click="clearTimeout(clickTimer); clickTimer = setTimeout(() => $wire.selectFolder(@js($currentType), @js($office)), 220)"
                                        @dblclick="clearTimeout(clickTimer); $wire.openOffice(@js($office))"
                                        wire:key="office-content-{{ $office }}" @contextmenu.prevent="$dispatch('cabinet-folder-context', { type: @js($currentType), office: @js($office), x: $event.currentTarget.getBoundingClientRect().right + 8, y: $event.currentTarget.getBoundingClientRect().top })"
                                        class="flex w-full items-center gap-4 border-b {{ $selectedFolderType === $currentType && $selectedFolderOffice === $office ? 'ring-2 ring-inset ring-violet-500' : '' }} border-gray-100 px-5 py-4 text-left transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800"
                                    >

                                        <x-heroicon-o-folder
                                            class="h-9 w-9 shrink-0 text-indigo-500"
                                        />

                                        <div class="min-w-0 flex-1">

                                            <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">
                                                {{ $office }}
                                            </p>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ count($documents) }} {{ Str::plural('file', count($documents)) }}</p>

                                        </div>

                                    </button>

                            @endforeach

                        </div>

                    @endif


                {{-- ===================================================== --}}
                {{-- OFFICE → DOCUMENTS --}}
                {{-- ===================================================== --}}

                @else

                    @if ($childFolders->isNotEmpty())
                        <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                            @foreach ($childFolders as $folder)
                                <button x-data="{ clickTimer: null }" @click="clearTimeout(clickTimer); clickTimer = setTimeout(() => $wire.selectFolder(@js($folder->name)), 220)" @dblclick="clearTimeout(clickTimer); $wire.openType(@js($folder->name))" @contextmenu.prevent="$dispatch('cabinet-folder-context', { type: @js($folder->name), office: null, x: $event.currentTarget.getBoundingClientRect().right + 8, y: $event.currentTarget.getBoundingClientRect().top })" class="rounded-xl border bg-white p-5 text-left transition {{ $selectedFolderType === $folder->name ? 'border-violet-500 ring-2 ring-violet-500/30' : 'border-gray-200' }}">
                                    <x-heroicon-o-folder class="h-14 w-14 text-indigo-500" /><p class="mt-4 truncate text-sm font-semibold">{{ $folder->name }}</p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $this->folderFileCount($folder->name) }} {{ Str::plural('file', $this->folderFileCount($folder->name)) }}</p>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @if($viewMode === 'tiles')

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">

                            @forelse($currentDocuments as $document)

                                @php
                                    $fileName = $document['name'];
                                    $displayName = $showFileExtensions
                                        ? $fileName
                                        : pathinfo($fileName, PATHINFO_FILENAME);
                                @endphp

                                <a
                                    href="{{ route('admin.documents.file', [
                                        'document' => $document['public_id'],
                                        'filename' => $fileName,
                                    ]) }}"
                                    wire:click.prevent="selectItem(@js($displayName), {{ $document['id'] }}, {{ isset($document['copy_key']) ? (int) substr($document['copy_key'], 5) : 'null' }})"
                                    @dblclick.prevent="window.open($el.href, '_blank', 'noopener')"
                                    wire:key="document-tile-{{ $document['copy_key'] ?? $document['id'] }}"
                                    @contextmenu.prevent="$dispatch('cabinet-context', { id: {{ $document['id'] }}, name: @js($displayName), url: $el.href, x: $event.currentTarget.getBoundingClientRect().right + 8, y: $event.currentTarget.getBoundingClientRect().top }); $wire.selectItem(@js($displayName), {{ $document['id'] }}, {{ isset($document['copy_key']) ? (int) substr($document['copy_key'], 5) : 'null' }})"
                                    rel="noopener noreferrer"
                                    class="group rounded-xl border bg-white p-5 text-left transition hover:border-violet-300 hover:bg-violet-50/40 hover:shadow-sm dark:bg-gray-900 dark:hover:border-violet-500 dark:hover:bg-violet-500/5 {{ $selectedDocumentId === $document['id'] && $selectedCopyId === (isset($document['copy_key']) ? (int) substr($document['copy_key'], 5) : null) ? 'border-violet-500 bg-violet-50 ring-2 ring-violet-500/25 dark:bg-violet-500/10' : 'border-gray-200 dark:border-gray-700' }}"
                                >

                                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-red-50 text-red-500 dark:bg-red-500/10 dark:text-red-400">
                                        @if(str_ends_with(strtolower($fileName), '.pdf'))
                                            <x-heroicon-o-document-text class="h-8 w-8" />
                                        @else
                                            <x-heroicon-o-document class="h-8 w-8" />
                                        @endif
                                    </div>

                                    <p class="mt-4 line-clamp-2 text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $displayName }}
                                    </p>

                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $document['size'] }} · {{ $document['date'] }}
                                    </p>

                                </a>

                            @empty

                                <div class="col-span-full px-6 py-16 text-center">
                                    <x-heroicon-o-document-magnifying-glass
                                        class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600"
                                    />
                                    <h3 class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">
                                        No documents found
                                    </h3>
                                </div>

                            @endforelse

                        </div>

                    @else

                    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">

                        {{-- HEADER --}}

                        <div class="cabinet-list-heading grid grid-cols-[minmax(0,1fr)_120px_160px_60px] border-b border-gray-200 bg-gray-50 px-5 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">

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
                                        'document' => $document['public_id'],
                                        'filename' => $fileName,
                                    ]) }}"
                                    wire:click.prevent="selectItem(@js($displayName), {{ $document['id'] }}, {{ isset($document['copy_key']) ? (int) substr($document['copy_key'], 5) : 'null' }})"
                                    @dblclick.prevent="window.open($el.href, '_blank', 'noopener')"
                                    wire:key="document-row-{{ $document['copy_key'] ?? $document['id'] }}"
                                    @contextmenu.prevent="$dispatch('cabinet-context', { id: {{ $document['id'] }}, name: @js($displayName), url: $el.href, x: $event.currentTarget.getBoundingClientRect().right + 8, y: $event.currentTarget.getBoundingClientRect().top }); $wire.selectItem(@js($displayName), {{ $document['id'] }}, {{ isset($document['copy_key']) ? (int) substr($document['copy_key'], 5) : 'null' }})"
                                    rel="noopener noreferrer"
                                    class="cabinet-list-row grid w-full cursor-pointer grid-cols-[minmax(0,1fr)_120px_160px_60px] items-center border-b px-5 py-4 text-left transition {{ $selectedDocumentId === $document['id'] && $selectedCopyId === (isset($document['copy_key']) ? (int) substr($document['copy_key'], 5) : null) ? 'border-violet-300 bg-violet-100 ring-1 ring-inset ring-violet-500 dark:bg-violet-500/20' : 'border-gray-100 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800' }}"
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

                @endif

            </div>


            {{-- ========================================================= --}}
            {{-- DETAILS PANE --}}
            {{-- ========================================================= --}}

            @if($detailsPane)

                <aside class="w-full shrink-0 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm lg:w-80 dark:border-gray-700 dark:bg-gray-900">

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

                                    @if($selectedDocumentId)

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
                                        {{ $selectedDocumentId ? 'Document' : 'Folder' }}
                                    </p>

                                </div>


                                @if($selectedDocumentId)

                                    <div>

                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Location
                                        </p>

                                        <p class="mt-1 break-words text-sm font-medium text-gray-900 dark:text-white">
                                            Cabinet / {{ $currentType }} / {{ $currentOffice }}
                                        </p>

                                    </div>

                                @elseif($currentOffice)

                                    <div>

                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Location
                                        </p>

                                        <p class="mt-1 break-words text-sm font-medium text-gray-900 dark:text-white">
                                            Cabinet / {{ $currentType }}
                                        </p>

                                    </div>

                                @endif


                                <div>

                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Source
                                    </p>

                                    <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $currentOffice ?: $currentType ?: 'Cabinet' }}
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

                <aside class="w-full shrink-0 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm lg:w-96 dark:border-gray-700 dark:bg-gray-900">

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

                        @if($selectedDocument)

                            @if(str_ends_with(strtolower($selectedDocument['name']), '.pdf'))

                                <iframe
                                    src="{{ route('admin.documents.preview', ['document' => $selectedDocument['public_id']]) }}"
                                    title="Preview of {{ $selectedDocument['name'] }}"
                                    class="h-full w-full rounded-lg border border-gray-200 dark:border-gray-700"
                                ></iframe>

                            @else

                            <div class="text-center">

                                <x-heroicon-o-document-text
                                    class="mx-auto h-16 w-16 text-gray-300 dark:text-gray-600"
                                />

                                <h4 class="mt-4 break-words text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $selectedItem }}
                                </h4>

                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    Preview is available for PDF documents.
                                </p>

                            </div>

                            @endif

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
