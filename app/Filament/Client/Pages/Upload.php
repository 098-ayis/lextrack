<?php

namespace App\Filament\Client\Pages;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema; 
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\DocumentType;
use App\Models\OfficeUnit;
use App\Services\AdminDocumentNotificationService;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class Upload extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.client.pages.upload';
    
    protected static ?string $navigationLabel = 'Upload';
    
    protected static ?string $title = 'Upload Document';

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
                TextInput::make('particulars')
                    ->label('Document Name')
                    ->columnSpan('full')
                    ->required(),
                
                Select::make('office_unit')
                    ->label('Office From')
                    ->options(fn () => OfficeUnit::query()
                        ->orderBy('name')
                        ->pluck('name', 'name'))
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('document_type')
                    ->label('Document Type')
                    ->options(fn () => DocumentType::query()
                        ->orderBy('type_name')
                        ->pluck('type_name', 'type_name'))
                    ->searchable()
                    ->preload()
                    ->required(),
                    
                FileUpload::make('file_path')
                    ->label('Upload Document')
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    ])
                    ->maxSize(5120)
                    ->disk('local')
                    ->directory('client-documents')
                    ->preserveFilenames()
                    ->columnSpan('full')
                    ->required(),
                            ])
                            ->columns(2)
                            ->statePath('data');
                    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $uploadedFile = $data['file_path'] ?? null;
        $upload = $this->inspectUploadedFile($uploadedFile);

        if ($upload === null) {
            Notification::make()
                ->danger()
                ->title('Upload could not be verified')
                ->body('The uploaded file could not be read. Please select the file again and try again.')
                ->send();

            return;
        }

        $fileHash = $upload['hash'];
        $userId = auth()->id();

        if (Document::where('user_id', $userId)
            ->where('file_hash', $fileHash)
            ->exists()) {
            $this->removeDuplicateUpload($upload);
            $this->notifyDuplicateDocument();

            return;
        }

        $filePath = $uploadedFile;

        if ($upload['temporary']) {
            $filePath = $uploadedFile->store('client-documents', 'local');

            if (! is_string($filePath) || $filePath === '') {
                Notification::make()
                    ->danger()
                    ->title('Upload could not be stored')
                    ->body('The uploaded file could not be saved. Please try again.')
                    ->send();

                return;
            }

            $upload['disk'] = 'local';
            $upload['stored_path'] = $filePath;
        }

        try {
            $document = DB::transaction(function () use ($data, $filePath, $fileHash, $userId): ?Document {
                if (Document::where('user_id', $userId)
                    ->where('file_hash', $fileHash)
                    ->exists()) {
                    return null;
                }

                $document = Document::create([
                    'user_id' => $userId,
                    'file_hash' => $fileHash,
                    'particulars' => $data['particulars'],
                    'document_name' => basename((string) $filePath),
                    'office_unit' => $data['office_unit'],
                    'document_type' => $data['document_type'],
                    'status' => 'pending',
                ]);

                DocumentVersion::create([
                    'user_id' => $userId,
                    'document_id' => $document->document_id,
                    'version_number' => '1',
                    'file_path' => $filePath,
                ]);

                return $document;
            });
        } catch (QueryException $exception) {
            if (! $this->isFileHashUniqueViolation($exception)) {
                throw $exception;
            }

            $this->removeDuplicateUpload($upload);
            $this->notifyDuplicateDocument();

            return;
        }

        if ($document === null) {
            $this->removeDuplicateUpload($upload);
            $this->notifyDuplicateDocument();

            return;
        }

        app(AdminDocumentNotificationService::class)
            ->notifyDocumentSubmitted($document);

        Notification::make()
            ->title('Document submitted successfully!')
            ->success()
            ->send();

        $this->form->fill();
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

        if (DocumentVersion::where('file_path', $storedPath)->exists()) {
            return;
        }

        Storage::disk($diskName)->delete($storedPath);
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
