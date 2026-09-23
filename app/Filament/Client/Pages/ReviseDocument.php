<?php

namespace App\Filament\Client\Pages;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Message;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ReviseDocument extends Page implements HasForms
{
    use InteractsWithForms;

    private const string REVISION_REQUEST_BODY = 'revision_request';

    private const string REVISION_UPLOAD_PREFIX = 'A revised document was uploaded';

    private const string REVISION_UPLOADS_PREFIX = 'Revised documents were uploaded';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Upload Revised Document';

    protected static ?string $slug = 'revise-document/{document}';

    protected string $view = 'filament.client.pages.revise-document';

    public ?array $data = [];

    public ?Document $documentRecord = null;

    public bool $revisionRequestClosed = false;

    public function getHeading(): string
    {
        return '';
    }

    public function mount(string|int $document): void
    {
        $this->documentRecord = Document::query()
            ->where('public_id', $document)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $this->revisionRequestClosed = ! $this->hasOpenRevisionRequest(
            $this->documentRecord
        );

        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                FileUpload::make('file_path')
                    ->label('Revised Document')
                    ->multiple()
                    ->appendFiles()
                    ->panelLayout('compact')
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    ])
                    ->maxSize(5120)
                    ->validationAttribute('revised document file')
                    ->validationMessages([
                        'required' => 'Please select the revised document before submitting.',
                        'mimetypes' => 'This file type is not supported. Please upload a PDF or DOCX file.',
                        'max' => 'The revised document is too large. Please choose a file up to 5 MB.',
                        'file' => 'The selected document could not be uploaded. Please choose a valid file.',
                    ])
                    ->disk('local')
                    ->directory('client-document-revisions')
                    ->preserveFilenames()
                    // Validate and store the temporary upload in submit(). This
                    // keeps the Livewire temporary file available for hashing.
                    ->storeFiles(false)
                    ->helperText('Accepted files: PDF or DOCX. Maximum file size: 5 MB.')
                    ->required(),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $uploadedFiles = array_values(array_filter(
            (array) ($data['file_path'] ?? []),
            static fn (mixed $file): bool => filled($file),
        ));

        if ($uploadedFiles === [] || ! $this->documentRecord) {
            return;
        }

        $uploads = [];
        $duplicateFiles = [];
        $unreadableFiles = [];

        foreach ($uploadedFiles as $file) {
            $fileHash = $this->hashUploadedFile($file);
            $fileName = $this->uploadedFileName($file);

            if ($fileHash === null) {
                $unreadableFiles[] = $fileName;

                continue;
            }

            if (
                isset($uploads[$fileHash])
                || DocumentVersion::existsForDocumentOrUserHash(
                    $this->documentRecord->document_id,
                    $fileHash,
                    auth()->id(),
                )
            ) {
                $duplicateFiles[] = $fileName;

                continue;
            }

            $uploads[$fileHash] = $file;
        }

        if ($duplicateFiles !== [] || $unreadableFiles !== []) {
            $this->cleanupUploadedFiles($uploadedFiles);

            $messages = [];
            if ($duplicateFiles !== []) {
                $messages[] = 'Already uploaded: ' . implode(', ', $duplicateFiles) . '.';
            }
            if ($unreadableFiles !== []) {
                $messages[] = 'Could not read: ' . implode(', ', $unreadableFiles) . '.';
            }

            Notification::make()
                ->danger()
                ->title($duplicateFiles !== [] ? 'Duplicate document detected' : 'Revision could not be verified')
                ->body(implode(' ', $messages))
                ->send();

            return;
        }

        $storedPaths = [];
        foreach ($uploads as $fileHash => $file) {
            $filePath = $this->storeRevisionUpload($file);

            if ($filePath === null) {
                $this->cleanupUploadedFiles($uploadedFiles);
                $this->cleanupStoredPaths($storedPaths);

                Notification::make()
                    ->danger()
                    ->title('Revision could not be uploaded')
                    ->body('One or more revised documents could not be saved. Please select the files again and try again.')
                    ->send();

                return;
            }

            $storedPaths[$fileHash] = $filePath;
        }

        $fileHashes = array_keys($storedPaths);
        $filePaths = array_values($storedPaths);
        $versionNumbers = [];
        $duplicate = false;

        DB::transaction(function () use ($filePaths, $fileHashes, &$versionNumbers, &$duplicate): void {
            $document = Document::query()
                ->where('document_id', $this->documentRecord->document_id)
                ->where('user_id', auth()->id())
                ->lockForUpdate()
                ->firstOrFail();

            if (DocumentVersion::query()
                ->whereIn('file_hash', $fileHashes)
                ->where(function ($query) use ($document): void {
                    $query->where('document_id', $document->document_id)
                        ->orWhere('user_id', auth()->id());
                })
                ->exists()) {
                $duplicate = true;

                return;
            }

            abort_unless(
                $this->hasOpenRevisionRequest($document),
                403,
                'This revision request has already been completed or is no longer available.'
            );

            $highestVersion = ($document->versions()
                ->get()
                ->map(fn (DocumentVersion $version): int => (int) $version->version_number)
                ->max() ?? 0);

            foreach ($filePaths as $index => $filePath) {
                $versionNumber = $highestVersion + $index + 1;
                $versionNumbers[] = $versionNumber;

                DocumentVersion::create([
                    'document_id' => $document->document_id,
                    'user_id' => auth()->id(),
                    'version_number' => (string) $versionNumber,
                    'file_path' => $filePath,
                    'file_hash' => $fileHashes[$index],
                    'source' => 'client',
                ]);
            }

            // A revision belongs to the existing document. Keep its LAO number
            // unchanged. Keep it in the existing incoming workflow instead of
            // sending the same request back to the new-document pending queue.
            $document->update([
                'lao_number' => $document->lao_number,
                'status' => 'in_progress',
                'rejection_reason' => null,
            ]);

            $conversation = $document->conversation()->first();

            if ($conversation) {
                Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => auth()->id(),
                    'body' => count($filePaths) === 1
                        ? 'A revised document was uploaded as version ' . $versionNumbers[0] . ' and is ready for review.'
                        : 'Revised documents were uploaded as versions ' . implode(', ', $versionNumbers) . ' and are ready for review.',
                ]);

                $conversation->touch();
            }
        });

        if ($duplicate) {
            $this->cleanupStoredPaths($filePaths);

            Notification::make()
                ->danger()
                ->title('Duplicate document detected')
                ->body('This exact file has already been uploaded for this document. Please select a different file.')
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title(count($filePaths) === 1 ? 'Revised document uploaded' : 'Revised documents uploaded')
            ->body(count($filePaths) === 1
                ? 'Your revised document was sent to the Legal Affairs Office for review.'
                : count($filePaths) . ' revised documents were uploaded as new versions and sent to the Legal Affairs Office for review.')
            ->send();

        $this->revisionRequestClosed = true;
        $this->form->fill();
    }

    private function hashUploadedFile(mixed $file): ?string
    {
        $realPath = null;

        if ($file instanceof TemporaryUploadedFile || $file instanceof UploadedFile) {
            $realPath = $file->getRealPath();
        } elseif (is_string($file) && $file !== '') {
            foreach (['local', 'public', 'livewire'] as $diskName) {
                $disk = Storage::disk($diskName);

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

                    break;
                }
            }
        }

        if (! is_string($realPath) || ! is_file($realPath)) {
            return null;
        }

        $hash = hash_file('sha256', $realPath);

        return is_string($hash) ? $hash : null;
    }

    private function uploadedFileName(mixed $file): string
    {
        if ($file instanceof TemporaryUploadedFile || $file instanceof UploadedFile) {
            $originalName = basename($file->getClientOriginalName());

            if ($originalName !== '') {
                return $originalName;
            }
        }

        return is_string($file) && $file !== '' ? basename($file) : 'uploaded file';
    }

    private function storeRevisionUpload(mixed $file): ?string
    {
        if ($file instanceof TemporaryUploadedFile || $file instanceof UploadedFile) {
            $originalName = $this->uploadedFileName($file);
            $filePath = $this->uniqueRevisionPath($originalName);
            $storedPath = $file->storeAs(
                'client-document-revisions',
                basename($filePath),
                'local',
            );

            if (! is_string($storedPath) || $storedPath === '') {
                return null;
            }

            if ($file instanceof TemporaryUploadedFile) {
                $file->delete();
            }

            return $storedPath;
        }

        if (! is_string($file) || $file === '') {
            return null;
        }

        foreach (['local', 'public'] as $diskName) {
            if (Storage::disk($diskName)->exists($file)) {
                return $file;
            }
        }

        $livewireDisk = Storage::disk('livewire');
        if (! $livewireDisk->exists($file)) {
            return null;
        }

        $filePath = $this->uniqueRevisionPath(basename($file));
        $stream = $livewireDisk->readStream($file);

        if (! is_resource($stream)) {
            return null;
        }

        $stored = Storage::disk('local')->put($filePath, $stream);
        fclose($stream);

        if (! $stored) {
            return null;
        }

        $livewireDisk->delete($file);

        return $filePath;
    }

    private function uniqueRevisionPath(string $originalName): string
    {
        $safeName = basename($originalName);
        if ($safeName === '' || $safeName === '.' || $safeName === DIRECTORY_SEPARATOR) {
            $safeName = (string) Str::ulid();
        }

        $disk = Storage::disk('local');
        $path = 'client-document-revisions/' . $safeName;

        if (! $disk->exists($path)) {
            return $path;
        }

        $extension = pathinfo($safeName, PATHINFO_EXTENSION);
        $name = pathinfo($safeName, PATHINFO_FILENAME);
        $suffix = '-' . Str::ulid();

        return 'client-document-revisions/' . $name . $suffix . ($extension !== '' ? '.' . $extension : '');
    }

    private function cleanupUploadedFiles(array $files): void
    {
        foreach ($files as $file) {
            if ($file instanceof TemporaryUploadedFile) {
                $file->delete();

                continue;
            }

            if ($file instanceof UploadedFile) {
                continue;
            }

            if (! is_string($file) || $file === '') {
                continue;
            }

            if (Storage::disk('livewire')->exists($file)) {
                Storage::disk('livewire')->delete($file);

                continue;
            }

            DocumentVersion::removeUnreferencedUpload($file);
        }
    }

    private function cleanupStoredPaths(array $filePaths): void
    {
        foreach ($filePaths as $filePath) {
            if (is_string($filePath) && $filePath !== '') {
                DocumentVersion::removeUnreferencedUpload($filePath);
            }
        }
    }

    public function clearForm(): void
    {
        if ($this->revisionRequestClosed) {
            return;
        }

        $this->form->fill();
    }

    /**
     * A revision request stays open until its matching client upload message
     * is created. This also invalidates direct visits to the old URL.
     */
    private function hasOpenRevisionRequest(Document $document): bool
    {
        $conversation = $document->conversation()->first();

        if (! $conversation) {
            return false;
        }

        $openRequest = false;

        foreach (
            $conversation->messages()
                ->oldest('created_at')
                ->oldest('id')
                ->get()
            as $message
        ) {
            if ($this->isRevisionRequest($message)) {
                $openRequest = true;

                continue;
            }

            if (
                $openRequest &&
                (int) $message->sender_id === (int) $document->user_id &&
                $this->isRevisionUploadMessage((string) $message->body)
            ) {
                $openRequest = false;
            }
        }

        return $openRequest;
    }

    private function isRevisionRequest(Message $message): bool
    {
        return $message->body === self::REVISION_REQUEST_BODY
            || str_contains(
                (string) $message->body,
                'Please upload a revised version of your document using this link:'
            );
    }

    private function isRevisionUploadMessage(string $body): bool
    {
        return str_starts_with($body, self::REVISION_UPLOAD_PREFIX)
            || str_starts_with($body, self::REVISION_UPLOADS_PREFIX);
    }
}
