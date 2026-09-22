<?php

namespace App\Filament\Client\Pages;

use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema; 
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\DocumentTransmittal;
use App\Models\DocumentType;
use App\Models\OfficeUnit;
use App\Services\AdminDocumentNotificationService;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class Upload extends Page implements HasForms
{
    private const string OTHER_OFFICE_UNIT = '__other__';

    private const string OTHER_DOCUMENT_TYPE = '__other_document_type__';

    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected string $view = 'filament.client.pages.upload';
    
    protected static ?string $navigationLabel = 'Submit';
    
    protected static ?string $title = 'Submit Document';

    public function getHeading(): string
    {
        return '';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Textarea::make('description')
                    ->label('Document Subject')
                    ->placeholder('Please explain what you need this document for...')
                    ->rows(2)
                    ->maxLength(200)
                    ->live()
                    ->helperText(fn (?string $state): HtmlString => new HtmlString(
                        '<span class="client-document-subject-counter" style="display: block; width: 100%; margin-left: auto; text-align: right; font-size: 0.75rem; line-height: 1rem;">' .
                        mb_strlen($state ?? '') .
                        '/200 characters</span>'
                    ))
                    ->extraFieldWrapperAttributes(['class' => 'client-document-subject-field'])
                    ->columnSpan('full')
                    ->required(),

                Hidden::make('office_unit_mode')
                    ->default('select')
                    ->dehydrated(false),

                Hidden::make('document_type_mode')
                    ->default('select')
                    ->dehydrated(false),
                
                Select::make('office_unit')
                    ->label('Office From')
                    ->options(fn () => OfficeUnit::query()
                        ->orderBy('name')
                        ->pluck('name', 'name')
                        ->prepend('Others', self::OTHER_OFFICE_UNIT)
                        ->toArray())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->visible(fn (Get $get): bool => $get('office_unit_mode') !== self::OTHER_OFFICE_UNIT)
                    ->dehydrated(fn (Get $get): bool => $get('office_unit_mode') !== self::OTHER_OFFICE_UNIT)
                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                        if ($state === self::OTHER_OFFICE_UNIT) {
                            $set('office_unit_mode', self::OTHER_OFFICE_UNIT);
                            $set('office_unit', null);

                            return;
                        }

                        $set('office_unit_mode', 'select');
                    })
                    ->required(),

                TextInput::make('office_unit')
                    ->label('Office From')
                    ->placeholder('e.g. College of Science')
                    ->maxLength(255)
                    ->suffixAction(
                        Action::make('chooseListedOffice')
                            ->icon(Heroicon::ChevronDown)
                            ->tooltip('Choose from listed offices')
                            ->action(function (Set $set): void {
                                $set('office_unit_mode', 'select');
                                $set('office_unit', null);
                            }),
                    )
                    ->visible(fn (Get $get): bool => $get('office_unit_mode') === self::OTHER_OFFICE_UNIT)
                    ->dehydrated(fn (Get $get): bool => $get('office_unit_mode') === self::OTHER_OFFICE_UNIT)
                    ->required(fn (Get $get): bool => $get('office_unit_mode') === self::OTHER_OFFICE_UNIT),

                Select::make('document_type')
                    ->label('Document Type')
                    ->options(fn () => DocumentType::query()
                        ->orderBy('type_name')
                        ->pluck('type_name', 'type_name')
                        ->prepend('Others', self::OTHER_DOCUMENT_TYPE)
                        ->toArray())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->visible(fn (Get $get): bool => $get('document_type_mode') !== self::OTHER_DOCUMENT_TYPE)
                    ->dehydrated(fn (Get $get): bool => $get('document_type_mode') !== self::OTHER_DOCUMENT_TYPE)
                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                        if ($state === self::OTHER_DOCUMENT_TYPE) {
                            $set('document_type_mode', self::OTHER_DOCUMENT_TYPE);
                            $set('document_type', null);

                            return;
                        }

                        $set('document_type_mode', 'select');
                    })
                    ->required(),

                TextInput::make('document_type')
                    ->label('Document Type')
                    ->placeholder('Enter the document type')
                    ->maxLength(255)
                    ->suffixAction(
                        Action::make('chooseListedDocumentType')
                            ->icon(Heroicon::ChevronDown)
                            ->tooltip('Choose from listed document types')
                            ->action(function (Set $set): void {
                                $set('document_type_mode', 'select');
                                $set('document_type', null);
                            }),
                    )
                    ->visible(fn (Get $get): bool => $get('document_type_mode') === self::OTHER_DOCUMENT_TYPE)
                    ->dehydrated(fn (Get $get): bool => $get('document_type_mode') === self::OTHER_DOCUMENT_TYPE)
                    ->required(fn (Get $get): bool => $get('document_type_mode') === self::OTHER_DOCUMENT_TYPE),
                    
                FileUpload::make('transmittal')
                    ->label('Transmittal/Endorsement')
                    ->multiple()
                    ->appendFiles()
                    ->panelLayout('compact')
                    ->removeUploadedFileButtonPosition('right')
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    ])
                    ->maxSize(5120)
                    ->validationAttribute('transmittal/endorsement file')
                    ->validationMessages([
                        'required' => 'Please select a transmittal/endorsement file before submitting.',
                        'mimetypes' => 'This file type is not supported. Please upload a PDF or DOCX file.',
                        'max' => 'The transmittal/endorsement file is too large. Please choose a file up to 5 MB.',
                        'file' => 'The selected transmittal/endorsement file could not be uploaded. Please choose a valid file.',
                    ])
                    ->disk('local')
                    ->directory('client-transmittals')
                    ->preserveFilenames()
                    ->helperText('Accepted files: PDF or DOCX. Maximum file size: 5 MB each.')
                    ->columnSpan('full')
                    ->required(),

                FileUpload::make('file_path')
                    ->label('Document File')
                    ->multiple()
                    ->appendFiles()
                    ->panelLayout('compact')
                    ->removeUploadedFileButtonPosition('right')
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    ])
                    ->maxSize(5120)
                    ->validationAttribute('document file')
                    ->validationMessages([
                        'required' => 'Please select a document file before submitting.',
                        'mimetypes' => 'This file type is not supported. Please upload a PDF or DOCX file.',
                        'max' => 'The document file is too large. Please choose a file up to 5 MB.',
                        'file' => 'The selected document could not be uploaded. Please choose a valid file.',
                    ])
                    ->disk('local')
                    ->directory('client-documents')
                    ->preserveFilenames()
                    ->helperText('Accepted files: PDF or DOCX. Maximum file size: 5 MB each.')
                    ->columnSpan('full')
                    ->required(),
                            ])
                            ->columns(2)
                            ->statePath('data');
                    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $uploadedFiles = $this->normalizeUploadedFiles($data['file_path'] ?? null);
        $transmittalFiles = $this->normalizeUploadedFiles($data['transmittal'] ?? null);
        $uploads = $this->inspectUploadedFiles($uploadedFiles);
        $transmittalUploads = $this->inspectUploadedFiles($transmittalFiles);

        if (count($uploads) !== count($uploadedFiles)) {
            $this->cleanupUploads([...$uploads, ...$transmittalUploads]);

            Notification::make()
                ->danger()
                ->title('Upload could not be verified')
                ->body('One or more document files could not be read. Please select the files again and try again.')
                ->send();

            return;
        }

        if (count($transmittalUploads) !== count($transmittalFiles)) {
            $this->cleanupUploads([...$uploads, ...$transmittalUploads]);

            Notification::make()
                ->danger()
                ->title('Transmittal/endorsement could not be verified')
                ->body('One or more transmittal/endorsement files could not be read. Please select the files again and try again.')
                ->send();

            return;
        }

        $transmittalHashes = array_column($transmittalUploads, 'hash');

        if (
            count($transmittalHashes) !== count(array_unique($transmittalHashes))
            || ($transmittalHashes !== [] && DocumentTransmittal::query()
                ->whereIn('file_hash', $transmittalHashes)
                ->exists())
        ) {
            $this->cleanupUploads([...$uploads, ...$transmittalUploads]);

            Notification::make()
                ->danger()
                ->title('Duplicate transmittal/endorsement detected')
                ->body('One or more transmittal/endorsement files have already been uploaded. Please choose different files.')
                ->send();

            return;
        }

        if (count($transmittalUploads) !== 1 && count($transmittalUploads) !== count($uploads)) {
            $this->cleanupUploads([...$uploads, ...$transmittalUploads]);

            Notification::make()
                ->danger()
                ->title('File counts do not match')
                ->body('Upload one transmittal/endorsement file to share with all document files, or upload one for each document file.')
                ->send();

            return;
        }

        $userId = auth()->id();
        $officeUnit = trim((string) ($data['office_unit'] ?? ''));
        $fileHashes = array_column($uploads, 'hash');

        if (count($fileHashes) !== count(array_unique($fileHashes))
            || DocumentVersion::where('user_id', $userId)
                ->whereIn('file_hash', $fileHashes)
                ->exists()) {
            $this->cleanupUploads([...$uploads, ...$transmittalUploads]);
            $this->notifyDuplicateDocument();

            return;
        }

        $filePaths = [];

        foreach ($uploadedFiles as $index => $uploadedFile) {
            $filePath = $this->storeUpload($uploadedFile, $uploads[$index], 'client-documents');

            if ($filePath === null) {
                $this->cleanupUploads([...$uploads, ...$transmittalUploads]);

                Notification::make()
                    ->danger()
                    ->title('Upload could not be stored')
                    ->body('One or more document files could not be saved. Please try again.')
                    ->send();

                return;
            }

            $filePaths[] = $filePath;
        }

        $transmittalPaths = [];

        foreach ($transmittalFiles as $index => $transmittalFile) {
            $transmittalPath = $this->storeUpload($transmittalFile, $transmittalUploads[$index], 'client-transmittals');

            if ($transmittalPath === null) {
                $this->cleanupUploads([...$uploads, ...$transmittalUploads]);

                Notification::make()
                    ->danger()
                    ->title('Transmittal/endorsement could not be stored')
                    ->body('One or more transmittal/endorsement files could not be saved. Please try again.')
                    ->send();

                return;
            }

            $transmittalPaths[] = $transmittalPath;
        }

        try {
            $documents = DB::transaction(function () use ($data, $officeUnit, $uploadedFiles, $filePaths, $transmittalPaths, $uploads, $transmittalUploads, $userId): array {
                $createdDocuments = [];

                foreach ($filePaths as $index => $filePath) {
                    $transmittalPath = count($transmittalPaths) === 1
                        ? $transmittalPaths[0]
                        : $transmittalPaths[$index];

                    $document = Document::create([
                        'user_id' => $userId,
                        'particulars' => null,
                        'description' => $data['description'],
                        'document_name' => $this->uploadedFileName($uploadedFiles[$index] ?? null, $filePath),
                        'office_unit' => $officeUnit,
                        'document_type' => $data['document_type'],
                        'transmittal' => $transmittalPath,
                        'status' => 'pending',
                    ]);

                    if ($transmittalPath !== null) {
                        $transmittalIndex = count($transmittalPaths) === 1 ? 0 : $index;

                        DocumentTransmittal::create([
                            'document_id' => $document->document_id,
                            'user_id' => $userId,
                            'file_path' => $transmittalPath,
                            'file_hash' => $transmittalUploads[$transmittalIndex]['hash'],
                        ]);
                    }

                    DocumentVersion::create([
                        'user_id' => $userId,
                        'document_id' => $document->document_id,
                        'version_number' => '1',
                        'file_path' => $filePath,
                        'file_hash' => $uploads[$index]['hash'],
                    ]);

                    $createdDocuments[] = $document;
                }

                return $createdDocuments;
            });
        } catch (QueryException $exception) {
            if (! $this->isFileHashUniqueViolation($exception)) {
                throw $exception;
            }

            $this->cleanupUploads([...$uploads, ...$transmittalUploads]);
            $this->notifyDuplicateDocument();

            return;
        }

        foreach ($documents as $document) {
            app(AdminDocumentNotificationService::class)
                ->notifyDocumentSubmitted($document);
        }

        Notification::make()
            ->title('Document submitted successfully!')
            ->success()
            ->send();

        $this->form->fill();
    }

    /**
     * Keep the upload workflow compatible with a single value while the
     * FileUpload fields accept multiple files.
     *
     * @return list<mixed>
     */
    private function normalizeUploadedFiles(mixed $files): array
    {
        if ($files === null || $files === '') {
            return [];
        }

        return array_values(is_array($files) ? $files : [$files]);
    }

    /**
     * @param list<mixed> $files
     * @return list<array{hash: string, temporary: bool, disk: ?string, stored_path: ?string}>
     */
    private function inspectUploadedFiles(array $files): array
    {
        $uploads = [];

        foreach ($files as $file) {
            $upload = $this->inspectUploadedFile($file);

            if ($upload !== null) {
                $uploads[] = $upload;
            }
        }

        return $uploads;
    }

    /**
     * Resolve the actual uploaded contents regardless of whether Filament has
     * left the value temporary or has already stored it on a configured disk.
     *
     * @return array{hash: string, temporary: bool, disk: ?string, stored_path: ?string}|null
     */
    private function inspectUploadedFile(mixed $file): ?array
    {
        $realPath = null;
        $temporary = false;
        $diskName = null;
        $storedPath = null;

        if ($file instanceof TemporaryUploadedFile || $file instanceof UploadedFile) {
            $realPath = $file->getRealPath();
            $temporary = $file instanceof TemporaryUploadedFile;
        } elseif (is_string($file) && $file !== '') {
            foreach (['local', 'public'] as $candidateDisk) {
                $disk = Storage::disk($candidateDisk);

                if (! $disk->exists($file)) {
                    continue;
                }

                try {
                    $candidatePath = $disk->path($file);
                } catch (\Throwable) {
                    continue;
                }

                if (is_file($candidatePath)) {
                    $realPath = $candidatePath;
                    $diskName = $candidateDisk;
                    $storedPath = $file;

                    break;
                }
            }
        }

        if (! is_string($realPath) || ! is_file($realPath)) {
            return null;
        }

        $fileHash = hash_file('sha256', $realPath);

        if (! is_string($fileHash)) {
            return null;
        }

        return [
            'hash' => $fileHash,
            'temporary' => $temporary,
            'disk' => $diskName,
            'stored_path' => $storedPath,
        ];
    }

    /**
     * Store a newly uploaded file while preserving files that Filament has
     * already stored on the configured disk.
     *
     * @param array{hash: string, temporary: bool, disk: ?string, stored_path: ?string} $upload
     */
    private function storeUpload(mixed $file, array &$upload, string $directory): ?string
    {
        if ($file instanceof TemporaryUploadedFile || $file instanceof UploadedFile) {
            $filePath = $file->store($directory, 'local');

            if (! is_string($filePath) || $filePath === '') {
                return null;
            }

            $upload['disk'] = 'local';
            $upload['stored_path'] = $filePath;

            return $filePath;
        }

        return is_string($file) && $file !== '' ? $file : null;
    }

    private function uploadedFileName(mixed $file, string $storedPath): string
    {
        if ($file instanceof TemporaryUploadedFile || $file instanceof UploadedFile) {
            $originalName = basename($file->getClientOriginalName());

            if ($originalName !== '') {
                return $originalName;
            }
        }

        return basename($storedPath);
    }

    /**
     * Remove a duplicate that Filament stored before submit, without deleting
     * a path already referenced by an existing document version.
     *
     * @param array{disk: ?string, stored_path: ?string} $upload
     */
    private function removeDuplicateUpload(array $upload): void
    {
        $diskName = $upload['disk'] ?? null;
        $storedPath = $upload['stored_path'] ?? null;

        if (! is_string($diskName) || ! is_string($storedPath) || $storedPath === '') {
            return;
        }

        if (DocumentVersion::where('file_path', $storedPath)->exists()
            || Document::where('transmittal', $storedPath)->exists()) {
            return;
        }

        Storage::disk($diskName)->delete($storedPath);
    }

    /**
     * @param list<array{disk: ?string, stored_path: ?string}> $uploads
     */
    private function cleanupUploads(array $uploads): void
    {
        foreach ($uploads as $upload) {
            $this->removeDuplicateUpload($upload);
        }
    }

    private function isFileHashUniqueViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        $message = strtolower($exception->getMessage());

        return in_array($sqlState, ['23000', '23505'], true)
            && str_contains($message, 'file_hash');
    }

    private function notifyDuplicateDocument(): void
    {
        Notification::make()
            ->danger()
            ->title('Duplicate document detected')
            ->body('Duplicate document detected. This exact file has already been uploaded. Please check your previous submissions or select another file.')
            ->send();
    }
    
    public function clearForm(): void
    {
        $this->form->fill();
    }
}
