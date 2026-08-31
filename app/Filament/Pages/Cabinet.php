<?php

namespace App\Filament\Pages;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\DocumentType;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class Cabinet extends Page
{
    protected static ?int $navigationSort = 6;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Cabinet';

    protected static ?string $title = 'Cabinet';

    protected string $view = 'filament.pages.cabinet';

    public array $cabinet = [];

    public string $search = '';

    public string $sourceFilter = 'all';

    public string $sortBy = 'name';

    public string $viewMode = 'tiles';

    public bool $detailsPane = false;

    public bool $previewPane = false;

    public bool $showFileExtensions = true;

    public ?string $selectedItem = null;

    public ?int $selectedDocumentId = null;

    public string $currentType = '';

    public string $currentOffice = '';

    public function mount(): void
    {
        $this->loadCabinet();
    }

    public function loadCabinet(): void
    {
        $documents = Document::query()
            ->with(['type', 'latestVersion'])
            ->whereNotNull('type_id')
            ->whereNotNull('office_unit')
            ->get();

        $this->cabinet = $documents
            ->filter(
                fn (Document $document) =>
                    $document->type !== null
            )
            ->groupBy(
                fn (Document $document) =>
                    $document->type->type_name
            )
            ->map(function ($documentsByType) {
                $isOthers = $documentsByType
                    ->first()?->type?->type_name === 'Others';

                return $documentsByType
                    ->groupBy(function (Document $document) use ($isOthers) {
                        if ($isOthers) {
                            $customType = trim(
                                (string) $document->other_document_type
                            );

                            return $customType !== ''
                                ? $customType
                                : 'Unspecified Type';
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

                                $fileName = $filePath
                                    ? basename($filePath)
                                    : ($document->particulars ?: 'Untitled Document');

                                $fileSize = '—';

                                if ($version && $filePath && $version->storageDisk()->exists($filePath)) {
                                    $bytes = $version->storageDisk()->size($filePath);

                                    $fileSize = $this->formatFileSize($bytes);
                                }

                                return [
                                    'id' => $document->document_id,

                                    'name' => $fileName,

                                    'particulars' => $document->particulars,

                                    'lao_number' => $document->lao_number,

                                    'size' => $fileSize,

                                    'date' => $document->updated_at
                                        ? $document->updated_at->format('M d, Y')
                                        : '—',

                                    'type' => $document->type?->type_name
                                        ?? 'Unknown',

                                    'other_document_type' =>
                                        $document->other_document_type,

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
                Select::make('type_id')
                    ->label('Document Type')
                    ->options(function () {
                        return DocumentType::query()
                            ->get(['type_id', 'type_name'])
                            ->sort(function (DocumentType $first, DocumentType $second): int {
                                $firstName = trim((string) $first->type_name);
                                $secondName = trim((string) $second->type_name);

                                $firstIsOthers = strcasecmp($firstName, 'Others') === 0;
                                $secondIsOthers = strcasecmp($secondName, 'Others') === 0;

                                if ($firstIsOthers !== $secondIsOthers) {
                                    return $firstIsOthers ? 1 : -1;
                                }

                                return strcasecmp($firstName, $secondName);
                            })
                            ->pluck('type_name', 'type_id')
                            ->all();
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required(),

                TextInput::make('other_document_type')
                    ->label('Specify Document Type')
                    ->placeholder('e.g. Affidavit, Certification')
                    ->maxLength(255)
                    ->visible(function ($get): bool {
                        $typeId = $get('type_id');

                        if (! $typeId) {
                            return false;
                        }

                        return DocumentType::query()
                            ->where('type_id', $typeId)
                            ->where('type_name', 'Others')
                            ->exists();
                    })
                    ->required(function ($get): bool {
                        $typeId = $get('type_id');

                        if (! $typeId) {
                            return false;
                        }

                        return DocumentType::query()
                            ->where('type_id', $typeId)
                            ->where('type_name', 'Others')
                            ->exists();
                    }),

                TextInput::make('office_unit')
                    ->label('Office / Unit')
                    ->required()
                    ->maxLength(255),

                TextInput::make('lao_number')
                    ->label('LAO Number')
                    ->maxLength(255),

                Textarea::make('particulars')
                    ->label('Particulars')
                    ->rows(3),

                FileUpload::make('file_path')
                    ->label('Document File')
                    ->disk('local')
                    ->directory('documents/versions')
                    ->preserveFilenames()
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    ])
                    ->required(),
            ])
            ->action(function (array $data): void {
                $type = DocumentType::find($data['type_id']);
                $filePath = $data['file_path'];

                DB::transaction(function () use ($data, $type, $filePath): void {
                    $document = Document::create([
                        'user_id' => auth()->id(),

                        'type_id' => $data['type_id'],

                        'other_document_type' =>
                            $type?->type_name === 'Others'
                                ? ($data['other_document_type'] ?? null)
                                : null,

                        'office_unit' =>
                            $data['office_unit'],

                        'lao_number' =>
                            $data['lao_number'] ?? null,

                        'particulars' =>
                            $data['particulars'] ?? null,

                        'status' =>
                            'in_progress',
                    ]);

                    DocumentVersion::create([
                        'user_id' => auth()->id(),
                        'document_id' => $document->document_id,
                        'version_number' => '1',
                        'file_path' => $filePath,
                    ]);
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
        $this->currentOffice = '';
        $this->sourceFilter = 'all';

        $this->selectedItem = null;
        $this->selectedDocumentId = null;
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

        $this->selectedItem = null;
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

        $this->selectedItem = null;
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
        if (! in_array($sort, ['name', 'date', 'type', 'size'])) {
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
        ?int $documentId = null
    ): void {
        $this->selectedItem = $item;

        $this->selectedDocumentId = $documentId;
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
}
