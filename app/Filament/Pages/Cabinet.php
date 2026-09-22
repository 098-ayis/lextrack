<?php

namespace App\Filament\Pages;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\DocumentType;
use App\Models\OfficeUnit;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use UnitEnum;
// use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class Cabinet extends Page
{
    // use HasPageShield;

    protected static ?int $navigationSort = 3;

    protected static string|UnitEnum|null $navigationGroup = 'OPERATIONS';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Cabinet';

    protected static ?string $title = 'Cabinet';

    protected string $view = 'filament.pages.cabinet';

    public array $cabinet = [];

    public string $search = '';

    public string $sourceFilter = 'all';

    public ?int $clipboardDocumentId = null;

    public ?string $clipboardFolderType = null;

    public ?string $clipboardFolderOffice = null;


    public string $sortBy = 'name';

    public string $viewMode = 'tiles';

    public bool $detailsPane = false;

    public bool $previewPane = false;

    public bool $showFileExtensions = true;

    public ?string $selectedItem = null;

    public ?int $selectedDocumentId = null;

    public ?int $selectedCopyId = null;

    public ?string $selectedFolderType = null;

    public ?string $selectedFolderOffice = null;

    public ?string $newFolderParentType = null;

    public ?string $newFolderParentOffice = null;

    public ?string $newDocumentType = null;

    public ?string $newDocumentOffice = null;

    public string $currentType = '';

    public string $currentOffice = '';

    public function mount(): void
    {
        $this->loadCabinet();
    }

    public function loadCabinet(): void
    {
        $knownTypes = DocumentType::query()->pluck('type_name')
            ->mapWithKeys(fn (string $name) => [strtolower(trim($name)) => trim($name)])
            ->all();

        $recycled = DB::table('cabinet_recycle_bin')->pluck('document_id')->all();
        $locations = DB::table('cabinet_document_locations')->get()->keyBy('document_id');
        $folderRecords = DB::table('cabinet_folders')->get();
        $folders = $folderRecords->whereNull('recycled_at')->pluck('name', 'id');

        $documents = Document::query()
            ->with(['latestVersion'])
            ->where('status', '!=', 'archived')
            ->whereNotNull('document_type')
            ->whereNotNull('office_unit')
            ->get();

        $this->cabinet = $documents
            ->filter(
                fn (Document $document) =>
                    filled($document->document_type)
            )
            ->groupBy(
                fn (Document $document) =>
                    in_array($document->document_id, $recycled)
                        ? 'Recycle Bin'
                        : ($knownTypes[strtolower(trim($document->document_type))] ?? 'Others')
            )
            ->map(function ($documentsByType, string $type) use ($recycled) {
                return $documentsByType
                    ->groupBy(function (Document $document) use ($type, $recycled) {
                        if (in_array($document->document_id, $recycled)) { return 'Documents'; }
                        if (strcasecmp($type, 'Others') === 0) {
                            return trim($document->document_type);
                        }

                        $office = trim((string) $document->office_unit);

                        return $office !== ''
                            ? $office
                            : 'Unspecified Office';
                    })
                    ->map(function ($documents) {
                        return $documents
                            ->map(function (Document $document) {
                                $version = $document->latestVersion;
                                $filePath = $version?->file_path;

                                $fileName = $document->document_name ?: ($filePath
                                    ? basename($filePath)
                                    : ($document->particulars ?: 'Untitled Document'));

                                $fileSize = '—';

                                if ($version && $filePath && $version->storageDisk()->exists($filePath)) {
                                    $bytes = $version->storageDisk()->size($filePath);

                                    $fileSize = $this->formatFileSize($bytes);
                                }

                                return [
                                    'id' => $document->document_id,
                                    'public_id' => $document->public_id,

                                    'name' => $fileName,

                                    'particulars' => $document->particulars,

                                    'lao_number' => $document->lao_number,

                                    'size' => $fileSize,

                                    'date' => $document->updated_at
                                        ? $document->updated_at->format('M d, Y')
                                        : '—',

                                    'type' => $document->document_type
                                        ?? 'Unknown',

                                    'office_unit' =>
                                        $document->office_unit,

                                    'status' =>
                                        $document->status,

                                    'file_path' => $filePath,
                                ];
                            })
                            ->values()
                            ->toArray();
                    })
                    ->toArray();
            })
            ->toArray();
        // Folder visibility is independent of whether its documents are archived or recycled.
        foreach (Document::query()->whereNotNull('document_type')->whereNotNull('office_unit')->get(['document_type', 'office_unit']) as $document) {
            $type = $knownTypes[strtolower(trim($document->document_type))] ?? 'Others';
            $office = strcasecmp($type, 'Others') === 0
                ? trim($document->document_type)
                : (trim($document->office_unit) ?: 'Unspecified Office');
            $this->cabinet[$type][$office] ??= [];
        }
        foreach (array_values($knownTypes) as $type) { $this->cabinet[$type] ??= []; }
        foreach ($locations as $location) {
            if ($location->cabinet_type !== null && $location->cabinet_office !== null) {
                $this->cabinet[$location->cabinet_type][$location->cabinet_office] ??= [];
            }
        }
        foreach ($folderRecords->whereNotNull('recycled_at') as $folder) {
            $this->cabinet['Recycle Bin'][$folder->name] ??= [];
        }
        foreach ($folderRecords->whereNull('recycled_at') as $folder) {
            $this->cabinet[$folder->name] ??= ['Documents' => []];
        }
        $entries = collect($this->cabinet)->flatMap(fn ($groups) => collect($groups)->flatten(1))->keyBy('id');
        foreach ($locations as $documentId => $location) {
            if (in_array($documentId, $recycled) || ! isset($entries[$documentId])) {
                continue;
            }

            $destinationType = $location->cabinet_type ?? ($folders[$location->folder_id] ?? null);
            $destinationOffice = $location->cabinet_office ?? 'Documents';

            if (! $destinationType) {
                continue;
            }

            $destination = $this->cabinet[$destinationType][$destinationOffice] ?? [];
            if (! collect($destination)->contains(fn (array $entry) => $entry['id'] === (int) $documentId)) {
                $this->cabinet[$destinationType][$destinationOffice][] = $entries[$documentId];
            }
        }
        foreach (DB::table('cabinet_copies')->get() as $copy) {
            if (!in_array($copy->document_id, $recycled) && isset($entries[$copy->document_id]) && ($copy->cabinet_type !== null || isset($folders[$copy->folder_id]))) {
                $entry = $entries[$copy->document_id];
                $entry['copy_key'] = 'copy-'.$copy->id;
                $entry['name'] = $copy->display_name ?? $entry['name'];
                $this->cabinet[$copy->cabinet_type ?? $folders[$copy->folder_id]][$copy->cabinet_office ?? 'Documents'][] = $entry;
            }
        }
    }

    public function destinationOptions(): array
    {
        $options = ['original' => 'Original type / office folder'];
        foreach ($this->cabinet as $type => $offices) {
            if ($type === 'Recycle Bin') { continue; }
            foreach ($offices as $office => $documents) {
                $options['path:'.base64_encode(json_encode([$type, $office]))] = $type.' / '.$office;
            }
        }
        foreach (DB::table('cabinet_folders')->orderBy('name')->pluck('name', 'id') as $id => $name) {
            $options[(string) $id] = $name;
        }
        return $options;
    }

    protected function destinationData(string $destination): array
    {
        abort_unless(array_key_exists($destination, $this->destinationOptions()), 422);
        if (str_starts_with($destination, 'path:')) {
            [$type, $office] = json_decode(base64_decode(substr($destination, 5)), true);
            return ['folder_id' => null, 'cabinet_type' => $type, 'cabinet_office' => $office];
        }
        return ['folder_id' => $destination, 'cabinet_type' => null, 'cabinet_office' => null];
    }

    public function selectFolder(string $type, ?string $office = null): void
    {
        abort_unless(isset($this->cabinet[$type]) && ($office === null || isset($this->cabinet[$type][$office])), 404);
        $this->selectedFolderType = $type;
        $this->selectedFolderOffice = $office;
        $this->selectedDocumentId = null;
    }

    public function renameFolderAction(): Action
    {
        return Action::make('renameFolder')->label('Rename')->modalHeading('Rename Folder')
            ->modalWidth(\Filament\Support\Enums\Width::Medium)
            ->schema([
                TextInput::make('name')->label('Folder name')->default(fn () => $this->selectedFolderOffice ?? $this->selectedFolderType)->required()->maxLength(255),
            ])->action(function (array $data): void {
                abort_unless(auth()->user()?->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')), 403);
                $folder = DB::table('cabinet_folders')->where('name', $this->selectedFolderType)->first();
                if (! $folder || $this->selectedFolderOffice !== null) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['name' => 'Only folders created by staff can be renamed.']);
                }
                $name = trim($data['name']);
                $reserved = array_merge(array_keys($this->cabinet), DocumentType::pluck('type_name')->all(), ['Others', 'Recycle Bin']);
                $duplicate = collect($reserved)->contains(fn ($value) => strcasecmp($value, $name) === 0 && strcasecmp($value, $folder->name) !== 0);
                if ($name === '' || $duplicate) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['name' => 'Choose a unique folder name.']);
                }
                DB::table('cabinet_folders')->where('id', $folder->id)->update(['name' => $name, 'updated_at' => now()]);
                $this->selectedFolderType = $name;
                $this->loadCabinet();
            });
    }

    public function deleteFolderAction(): Action
    {
        return Action::make('deleteFolder')->label('Delete')->requiresConfirmation()
            ->modalHeading('Send folder contents to Recycle Bin?')
            ->action(function (): void {
                abort_unless(auth()->user()?->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')), 403);
                $folder = DB::table('cabinet_folders')->where('name', $this->selectedFolderType)->whereNull('recycled_at')->first();
                if (! $folder || $this->selectedFolderOffice !== null) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['folder' => 'Only folders created by staff can be deleted.']);
                }
                $documentIds = DB::table('cabinet_document_locations')->where('folder_id', $folder->id)->pluck('document_id')
                    ->concat(DB::table('cabinet_copies')->where('folder_id', $folder->id)->pluck('document_id'))->unique();
                DB::transaction(function () use ($folder, $documentIds): void {
                    DB::table('cabinet_folders')->where('id', $folder->id)->update(['recycled_at' => now(), 'updated_at' => now()]);
                    foreach ($documentIds as $documentId) {
                        DB::table('cabinet_recycle_bin')->updateOrInsert(['document_id' => $documentId], ['created_at' => now(), 'updated_at' => now()]);
                    }
                });
                $this->loadCabinet();
            });
    }

    public function restoreFolder(): void
    {
        abort_unless(auth()->user()?->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')), 403);
        $folder = DB::table('cabinet_folders')->where('name', $this->selectedFolderOffice)->whereNotNull('recycled_at')->first();
        abort_unless($folder, 404);
        $documentIds = DB::table('cabinet_document_locations')->where('folder_id', $folder->id)->pluck('document_id')
            ->concat(DB::table('cabinet_copies')->where('folder_id', $folder->id)->pluck('document_id'))->unique();
        DB::transaction(function () use ($folder, $documentIds): void {
            DB::table('cabinet_folders')->where('id', $folder->id)->update(['recycled_at' => null, 'updated_at' => now()]);
            DB::table('cabinet_recycle_bin')->whereIn('document_id', $documentIds)->delete();
        });
        $this->loadCabinet();
        $this->selectedFolderType = $this->selectedFolderOffice = null;
    }

    public function copyFolderToClipboard(string $type, ?string $office = null): void
    {
        abort_unless(auth()->user()?->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')), 403);
        abort_unless(isset($this->cabinet[$type]) && ($office === null || isset($this->cabinet[$type][$office])), 404);
        $this->clipboardFolderType = $type;
        $this->clipboardFolderOffice = $office;
        $this->clipboardDocumentId = null;
        \Filament\Notifications\Notification::make()->title('Folder copied')->success()->send();
    }

    public function pasteFolderAction(): Action
    {
        return Action::make('pasteFolder')->modalHeading('Paste Folder')->modalWidth(\Filament\Support\Enums\Width::Medium)
            ->schema([
                TextInput::make('name')->label('Folder name')->default(fn () => $this->clipboardFolderOffice ?? $this->clipboardFolderType)->required()->maxLength(255),
            ])->action(function (array $data): void {
                abort_unless(auth()->user()?->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')), 403);
                $name = trim($data['name']);
                $reserved = array_merge(array_keys($this->cabinet), DocumentType::pluck('type_name')->all(), ['Others', 'Recycle Bin']);
                if (collect($reserved)->contains(fn ($value) => strcasecmp($value, $name) === 0)) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['name' => 'Rename the folder before pasting.']);
                }
                $folderId = DB::table('cabinet_folders')->insertGetId(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
                $groups = $this->cabinet[$this->clipboardFolderType] ?? [];
                $documents = $this->clipboardFolderOffice === null ? collect($groups)->flatten(1) : collect($groups[$this->clipboardFolderOffice] ?? []);
                foreach ($documents->unique('id') as $document) {
                    DB::table('cabinet_copies')->insert(['document_id' => $document['id'], 'folder_id' => $folderId, 'display_name' => $document['name'], 'created_at' => now(), 'updated_at' => now()]);
                }
                $this->loadCabinet();
                \Filament\Notifications\Notification::make()->title('Folder pasted')->success()->send();
            });
    }

    public function copyToClipboard(int $documentId, string $mode = 'copy'): void
    {
        abort_unless(auth()->user()?->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')), 403);
        abort_unless($mode === 'copy', 422);
        Document::findOrFail($documentId);
        abort_if(DB::table('cabinet_recycle_bin')->where('document_id', $documentId)->exists(), 422);
        $this->clipboardDocumentId = $documentId;
        \Filament\Notifications\Notification::make()->title('Copied to clipboard')->success()->send();
    }

    public function pasteDocument(?string $requestedName = null): void
    {
        abort_unless(auth()->user()?->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')), 403);
        if ($this->currentType === '' || $this->currentType === 'Recycle Bin') {
            \Filament\Notifications\Notification::make()->title('Open a folder before pasting')->warning()->send();
            return;
        }
        $folderId = DB::table('cabinet_folders')->where('name', $this->currentType)->whereNull('recycled_at')->value('id');
        if ($this->currentOffice === '' && !$folderId) {
            \Filament\Notifications\Notification::make()->title('Open a source folder before pasting')->warning()->send();
            return;
        }
        Document::findOrFail($this->clipboardDocumentId);
        abort_if(DB::table('cabinet_recycle_bin')->where('document_id', $this->clipboardDocumentId)->exists(), 422);
        $destination = $folderId
            ? $this->destinationData((string) $folderId)
            : $this->destinationData('path:'.base64_encode(json_encode([$this->currentType, $this->currentOffice])));
        $source = collect($this->cabinet)->flatMap(fn ($groups) => collect($groups)->flatten(1))
            ->first(fn ($entry) => $entry['id'] === $this->clipboardDocumentId && !isset($entry['copy_key']));
        abort_unless($source, 422);
        $office = $this->currentOffice ?: 'Documents';
        $existing = collect($this->cabinet[$this->currentType][$office] ?? [])
            ->pluck('name')->map(fn ($name) => mb_strtolower($name));
        $name = trim($requestedName ?? $source['name']);
        if ($existing->contains(mb_strtolower($name))) {
            if ($requestedName !== null) {
                throw \Illuminate\Validation\ValidationException::withMessages(['name' => 'This filename already exists in this folder.']);
            }
            $this->mountAction('pasteRename');
            return;
        }
        validator(['name' => $name], ['name' => ['required', 'string', 'max:255', 'not_regex:/[\\\\\/]/']])->validate();
        DB::table('cabinet_copies')->insert([
            ...$destination, 'display_name' => $name, 'document_id' => $this->clipboardDocumentId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->loadCabinet();
        if ($folderId && $this->currentOffice === '') { $this->currentOffice = 'Documents'; }
        \Filament\Notifications\Notification::make()->title('Document pasted')->success()->send();
    }

    protected function clipboardSourceName(): string
    {
        if (! $this->clipboardDocumentId) {
            return '';
        }

        $document = Document::with('latestVersion')->find($this->clipboardDocumentId);

        return $document?->document_name
            ?: ($document?->latestVersion?->file_path ? basename($document->latestVersion->file_path) : '')
            ?: ($document?->particulars ?: 'Untitled Document');
    }

    public function pasteRenameAction(): Action
    {
        return Action::make('pasteRename')->modalHeading('Rename before pasting')
            ->modalWidth(\Filament\Support\Enums\Width::Medium)
            ->modalSubmitActionLabel('Rename and Paste')->schema([
                TextInput::make('name')
                    ->label('New filename')
                    ->default(fn (): string => $this->clipboardSourceName())
                    ->required()
                    ->maxLength(255)
                    ->rules([
                        fn (): \Closure => function (string $attribute, mixed $value, \Closure $fail): void {
                            if (mb_strtolower(trim((string) $value)) === mb_strtolower($this->clipboardSourceName())) {
                                $fail('Rename the file before pasting.');
                            }
                        },
                    ]),
            ])->action(fn (array $data) => $this->pasteDocument($data['name']));
    }

    public function renameDocumentAction(): Action
    {
        return Action::make('renameDocument')->label('Rename')->modalHeading('Rename Document')
            ->modalWidth(\Filament\Support\Enums\Width::Medium)->schema([
            TextInput::make('name')->label('Filename')->required()->maxLength(255)
                ->default(fn () => $this->selectedItem),
        ])->action(function (array $data): void {
            abort_unless(auth()->user()?->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')), 403);
            $name = trim($data['name']);
            validator(['name' => $name], ['name' => ['required', 'string', 'max:255', 'not_regex:/[\\\\\/]/']])->validate();
            $entries = collect($this->cabinet[$this->currentType][$this->currentOffice] ?? []);
            $conflict = $entries->contains(function ($entry) use ($name) {
                $copyId = isset($entry['copy_key']) ? (int) substr($entry['copy_key'], 5) : null;
                $same = $entry['id'] === $this->selectedDocumentId && $copyId === $this->selectedCopyId;
                return !$same && mb_strtolower($entry['name']) === mb_strtolower($name);
            });
            if ($conflict) {
                throw \Illuminate\Validation\ValidationException::withMessages(['name' => 'This filename already exists in this folder.']);
            }
            if ($this->selectedCopyId) {
                DB::table('cabinet_copies')->where('id', $this->selectedCopyId)->where('document_id', $this->selectedDocumentId)->update(['display_name' => $name]);
            } else {
                Document::findOrFail($this->selectedDocumentId)->update(['document_name' => $name]);
            }
            $this->selectedItem = $name;
            $this->loadCabinet();
        });
    }

    public function prepareAddFolder(): void
    {
        $this->newFolderParentType = $this->currentType !== '' ? $this->currentType : null;
        $this->newFolderParentOffice = $this->currentType !== '' ? ($this->currentOffice ?: null) : null;
        $this->mountAction('addFolder');
    }

    public function addFolderAction(): Action
    {
        return Action::make('addFolder')->label('Add Folder')->icon('heroicon-o-folder-plus')
            ->modalHeading('Add Folder')->schema([
                TextInput::make('name')->label('Folder name')->required()->maxLength(255),
            ])->action(function (array $data): void {
                abort_unless(auth()->user()?->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')), 403);
                $name = trim($data['name']);
                $reserved = array_merge(array_keys($this->cabinet), DocumentType::pluck('type_name')->all(), ['Others', 'Recycle Bin']);
                if ($name === '' || collect($reserved)->contains(fn ($value) => strcasecmp($value, $name) === 0)) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['name' => 'Choose a unique folder name.']);
                }
                DB::table('cabinet_folders')->insert([
                    'name' => $name,
                    'parent_type' => $this->newFolderParentType,
                    'parent_office' => $this->newFolderParentOffice,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
                $this->loadCabinet();
            });
    }

    public function deleteCabinetDocumentAction(): Action
    {
        return Action::make('deleteCabinetDocument')->label('Delete')->requiresConfirmation()
            ->modalHeading('Send document to Recycle Bin?')
            ->action(function (): void {
                abort_unless(auth()->user()?->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')), 403);
                Document::findOrFail($this->selectedDocumentId);
                DB::table('cabinet_recycle_bin')->updateOrInsert(['document_id' => $this->selectedDocumentId], ['created_at' => now(), 'updated_at' => now()]);
                $this->loadCabinet();
                $this->goToRoot();
            });
    }

    public function archiveCabinetDocumentAction(): Action
    {
        return Action::make('archiveCabinetDocument')->label('Archive')->requiresConfirmation()
            ->action(function (): void {
                abort_unless(auth()->user()?->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')), 403);
                (new \App\Filament\Pages\Document)->archiveDocument($this->selectedDocumentId);
                $this->loadCabinet();
                $this->goToRoot();
            });
    }

    public function restoreCabinetDocument(): void
    {
        abort_unless(auth()->user()?->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')), 403);
        DB::table('cabinet_recycle_bin')->where('document_id', $this->selectedDocumentId)->delete();
        $this->loadCabinet();
        $this->goToRoot();
    }

    protected function currentDocumentDefaults(): array
    {
        if ($this->currentType === '' || $this->currentType === 'Recycle Bin') {
            return [null, null];
        }

        $type = $this->currentType;
        $office = $this->currentOffice;
        $seen = [];

        while ($folder = DB::table('cabinet_folders')->where('name', $type)->whereNull('recycled_at')->first()) {
            if (in_array($folder->id, $seen, true) || $folder->parent_type === null) {
                return [null, null];
            }
            $seen[] = $folder->id;
            $type = $folder->parent_type;
            $office = $folder->parent_office;
        }

        if (strcasecmp($type, 'Others') === 0) {
            return [$office && $office !== 'Documents' ? $office : null, null];
        }

        $knownType = DocumentType::query()->whereRaw('LOWER(type_name) = ?', [mb_strtolower($type)])->value('type_name');

        return [$knownType, $office && $office !== 'Documents' ? $office : null];
    }

    public function prepareAddDocument(): void
    {
        [$this->newDocumentType, $this->newDocumentOffice] = $this->currentDocumentDefaults();
        $this->mountAction('addDocument');
    }

    public function addDocumentAction(): Action
    {
        return Action::make('addDocument')
            ->label('Add Document')
            ->icon('heroicon-o-document-plus')
            ->color('primary')
            ->modalHeading('Add Document')
            ->modalSubmitActionLabel('Save Document')
            ->form([
                TextInput::make('lao_number')
                    ->label('LAO Number')
                    ->default(fn (): string => Document::generateLaoNumber(now()))
                    ->readOnly(),

                TextInput::make('document_name')
                    ->label('Document Name')
                    ->readOnly()
                    ->maxLength(255),

                Select::make('document_type')
                    ->label('Document Type')
                    ->default(fn () => $this->newDocumentType)
                    ->options(fn () => DocumentType::query()
                        ->orderBy('type_name')
                        ->pluck('type_name', 'type_name'))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set): void {
                        $deadline = Document::deadlineForType($state);

                        if (filled($deadline)) {
                            $set('deadline', $deadline);
                        }
                    })
                    ->required(),

                DatePicker::make('deadline')
                    ->label('Deadline')
                    ->default(fn () => Document::deadlineForType($this->newDocumentType) ?: now()->toDateString()),

                Select::make('office_unit')
                    ->label('Office / Unit')
                    ->default(fn () => $this->newDocumentOffice)
                    ->options(fn () => OfficeUnit::query()
                        ->orderBy('name')
                        ->pluck('name', 'name'))
                    ->searchable()
                    ->preload()
                    ->required(),

                Textarea::make('particulars')
                    ->label('Particulars')
                    ->rows(3),

                FileUpload::make('file_path')
                    ->label('Document File')
                    ->disk('local')
                    ->directory(function (Get $get): string {
                        $type = $this->currentType ?: (string) $get('document_type');
                        $office = $this->currentOffice ?: (string) $get('office_unit');
                        $segments = collect([$type, $office])
                            ->filter(fn (string $segment) => $segment !== '' && $segment !== 'Documents')
                            ->map(fn (string $segment) => \Illuminate\Support\Str::slug($segment))
                            ->filter()
                            ->implode('/');

                        return 'documents/versions/cabinet/'.($segments ?: 'root');
                    })
                    ->multiple()
                    ->appendFiles()
                    ->panelLayout('integrated')
                    ->preserveFilenames()
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    ])
                    ->rules(['mimes:pdf,docx'])
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set): void {
                        $set('document_name', $this->uploadedDocumentName($state));
                    }),
            ])
            ->action(function (array $data): void {
                $filePaths = array_values(array_filter(
                    (array) ($data['file_path'] ?? []),
                    static fn (mixed $path): bool => is_string($path) && filled($path),
                ));
                $fileHashes = array_map(
                    static fn (string $path): ?string => DocumentVersion::hashForUpload($path),
                    $filePaths,
                );

                if ($filePaths === [] || in_array(null, $fileHashes, true)) {
                    foreach ($filePaths as $filePath) {
                        DocumentVersion::removeUnreferencedUpload($filePath);
                    }

                    Notification::make()
                        ->danger()
                        ->title('Upload could not be verified')
                        ->body('One or more document files could not be read. Please select the files again and try again.')
                        ->send();

                    return;
                }

                $hasDuplicates = count($fileHashes) !== count(array_unique($fileHashes));

                foreach ($fileHashes as $fileHash) {
                    if (DocumentVersion::existsForDocumentOrUserHash(0, $fileHash, auth()->id())) {
                        $hasDuplicates = true;

                        break;
                    }
                }

                if ($hasDuplicates) {
                    foreach ($filePaths as $filePath) {
                        DocumentVersion::removeUnreferencedUpload($filePath);
                    }

                    Notification::make()
                        ->danger()
                        ->title('Duplicate document detected')
                        ->body('One or more selected files have already been uploaded. Please remove duplicates and try again.')
                        ->send();

                    return;
                }

                $destinationType = $this->currentType;
                $destinationOffice = $this->currentOffice;
                DB::transaction(function () use ($data, $filePaths, $fileHashes, $destinationType, $destinationOffice): void {
                    $document = Document::create([
                        'user_id' => auth()->id(),

                        'document_type' => $data['document_type'],

                        'office_unit' =>
                            $data['office_unit'],

                        'document_name' =>
                            $this->uploadedDocumentName($filePaths[0]),

                        // Generate at save time so an old form value cannot
                        // reuse a LAO number assigned by another upload.
                        'lao_number' =>
                            Document::generateLaoNumber(now()),

                        'particulars' =>
                            $data['particulars'] ?? null,

                        'deadline' =>
                            $data['deadline'] ?? Document::deadlineForType($data['document_type']),

                        'status' =>
                            'in_progress',
                    ]);

                    foreach ($filePaths as $index => $filePath) {
                        DocumentVersion::create([
                            'user_id' => auth()->id(),
                            'document_id' => $document->document_id,
                            'version_number' => (string) ($index + 1),
                            'file_path' => $filePath,
                            'file_hash' => $fileHashes[$index],
                        ]);
                    }
                    if ($destinationType !== '' && $destinationType !== 'Recycle Bin') {
                        $folderId = DB::table('cabinet_folders')->where('name', $destinationType)->value('id');
                        DB::table('cabinet_document_locations')->updateOrInsert(
                            ['document_id' => $document->document_id],
                            $folderId
                                ? ['folder_id' => $folderId, 'cabinet_type' => null, 'cabinet_office' => null]
                                : ['folder_id' => null, 'cabinet_type' => $destinationType, 'cabinet_office' => $destinationOffice ?: $data['office_unit']]
                        );
                    }
                });

                $this->loadCabinet();
            });
    }

    public function openType(string $type): void
    {
        if (! isset($this->cabinet[$type])) {
            return;
        }

        $this->currentType = $type;
        $this->currentOffice = DB::table('cabinet_folders')->where('name', $type)->whereNull('recycled_at')->exists()
            ? 'Documents'
            : '';
        $this->sourceFilter = 'all';

        $this->selectedItem = $type;
        $this->selectedDocumentId = null;
    }

    public function folderBreadcrumbs(): array
    {
        if ($this->currentType === '') {
            return [];
        }

        $folder = DB::table('cabinet_folders')
            ->where('name', $this->currentType)
            ->whereNull('recycled_at')
            ->first();

        if (! $folder) {
            return array_values(array_filter([$this->currentType, $this->currentOffice]));
        }

        $breadcrumbs = [];
        $seen = [];

        while ($folder && ! in_array($folder->id, $seen, true)) {
            $seen[] = $folder->id;
            array_unshift($breadcrumbs, $folder->name);

            if (! $folder->parent_type) {
                break;
            }

            $parent = DB::table('cabinet_folders')
                ->where('name', $folder->parent_type)
                ->whereNull('recycled_at')
                ->first();

            if ($parent) {
                $folder = $parent;
                continue;
            }

            if ($folder->parent_office && $folder->parent_office !== 'Documents') {
                array_unshift($breadcrumbs, $folder->parent_office);
            }

            array_unshift($breadcrumbs, $folder->parent_type);
            break;
        }

        return $breadcrumbs;
    }

    public function folderFileCount(string $folderName, array $seen = []): int
    {
        if (in_array($folderName, $seen, true)) {
            return 0;
        }

        $seen[] = $folderName;
        $count = collect($this->cabinet[$folderName] ?? [])->flatten(1)->count();

        $children = DB::table('cabinet_folders')
            ->where('parent_type', $folderName)
            ->whereNull('recycled_at')
            ->pluck('name');

        foreach ($children as $child) {
            $count += $this->folderFileCount($child, $seen);
        }

        return $count;
    }

    public function openOffice(string $office): void
    {
        if (
            $this->currentType === '' ||
            ! isset($this->cabinet[$this->currentType][$office])
        ) {
            return;
        }

        $this->currentOffice = $office;

        $this->selectedItem = $office;
        $this->selectedDocumentId = null;
    }

    public function goToRoot(): void
    {
        $this->currentType = '';
        $this->currentOffice = '';
        $this->sourceFilter = 'all';

        $this->selectedItem = null;
        $this->selectedDocumentId = null;
    }

    public function goToType(): void
    {
        $this->currentOffice = '';
        $this->sourceFilter = 'all';

        $this->selectedItem = $this->currentType;
        $this->selectedDocumentId = null;
    }

    public function clearSearch(): void
    {
        $this->search = '';
    }

    public function setViewMode(string $mode): void
    {
        if (! in_array($mode, ['tiles', 'content'])) {
            return;
        }

        $this->viewMode = $mode;
    }

    public function setSort(string $sort): void
    {
        if (! in_array($sort, ['name', 'date', 'size'])) {
            return;
        }

        $this->sortBy = $sort;
    }

    public function toggleDetailsPane(): void
    {
        $this->detailsPane = ! $this->detailsPane;

        if ($this->detailsPane) {
            $this->previewPane = false;
        }
    }

    public function togglePreviewPane(): void
    {
        $this->previewPane = ! $this->previewPane;

        if ($this->previewPane) {
            $this->detailsPane = false;
        }
    }

    public function toggleFileExtensions(): void
    {
        $this->showFileExtensions =
            ! $this->showFileExtensions;
    }

    public function selectItem(
        string $item,
        ?int $documentId = null,
        ?int $copyId = null
    ): void {
        $this->selectedItem = $item;
        $this->selectedFolderType = null;
        $this->selectedFolderOffice = null;

        $this->selectedDocumentId = $documentId;
        $this->selectedCopyId = $copyId;
    }

    protected function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format(
                $bytes / 1073741824,
                2
            ) . ' GB';
        }

        if ($bytes >= 1048576) {
            return number_format(
                $bytes / 1048576,
                2
            ) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format(
                $bytes / 1024,
                2
            ) . ' KB';
        }

        return $bytes . ' B';
    }

    protected function uploadedDocumentName(mixed $file): ?string
    {
        if (is_array($file)) {
            return $this->uploadedDocumentName(reset($file) ?: null);
        }

        if ($file instanceof TemporaryUploadedFile || $file instanceof UploadedFile) {
            $originalName = basename($file->getClientOriginalName());

            return $originalName !== '' ? $originalName : null;
        }

        return is_string($file) && filled($file)
            ? basename($file)
            : null;
    }
}
