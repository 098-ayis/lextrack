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
use Filament\Schemas\Components\Utilities\Set;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
// use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class Cabinet extends Page
{
    // use HasPageShield;

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
            ->with(['latestVersion'])
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
                    $document->document_type
            )
            ->map(function ($documentsByType) {
                return $documentsByType
                    ->groupBy(function (Document $document) {
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
                    ->default(fn (): string => Document::generateLaoNumber())
                    ->readOnly()
                    ->helperText('Automatically assigned from the current LAO sequence.'),

                TextInput::make('document_name')
                    ->label('Document Name')
                    ->readOnly()
                    ->helperText('Initialized from the uploaded file name. Rename it using Edit.')
                    ->maxLength(255),

                TextInput::make('document_type')
                    ->label('Document Type')
                    ->datalist(fn () => DocumentType::query()->orderBy('type_name')->pluck('type_name'))
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set): void {
                        $set('deadline', Document::deadlineForType($state));
                    })
                    ->required(),

                DatePicker::make('deadline')
                    ->label('Deadline')
                    ->readOnly()
                    ->helperText('Calculated from the document type.'),

                TextInput::make('office_unit')
                    ->label('Office / Unit')
                    ->datalist(fn () => OfficeUnit::query()->orderBy('name')->pluck('name'))
                    ->required(),

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
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    ])
                    ->rules(['mimes:pdf,docx'])
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set): void {
                        $set('document_name', filled($state) ? basename((string) $state) : null);
                    }),
            ])
            ->action(function (array $data): void {
                $filePath = $data['file_path'];

                DB::transaction(function () use ($data, $filePath): void {
                    $document = Document::create([
                        'user_id' => auth()->id(),

                        'document_type' => $data['document_type'],

                        'office_unit' =>
                            $data['office_unit'],

                        'document_name' =>
                            basename((string) $filePath),

                        'lao_number' =>
                            $data['lao_number'] ?? Document::generateLaoNumber(),

                        'particulars' =>
                            $data['particulars'] ?? null,

                        'deadline' =>
                            Document::deadlineForType($data['document_type']),

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
