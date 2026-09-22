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
use Illuminate\Support\Facades\DB;

class ReviseDocument extends Page implements HasForms
{
    use InteractsWithForms;

    private const string REVISION_REQUEST_BODY = 'revision_request';

    private const string REVISION_UPLOAD_PREFIX = 'A revised document was uploaded';

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
                    ->helperText('Accepted files: PDF or DOCX. Maximum file size: 5 MB.')
                    ->required(),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $filePaths = array_values(array_filter(
            (array) ($data['file_path'] ?? []),
            static fn (mixed $filePath): bool => is_string($filePath) && filled($filePath),
        ));

        if ($filePaths === [] || ! $this->documentRecord) {
            return;
        }

        $uploads = [];
        $duplicateFiles = [];
        $unreadableFiles = [];

        foreach ($filePaths as $filePath) {
            $fileHash = DocumentVersion::hashForUpload($filePath);

            if ($fileHash === null) {
                DocumentVersion::removeUnreferencedUpload($filePath);
                $unreadableFiles[] = basename($filePath);

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
                DocumentVersion::removeUnreferencedUpload($filePath);
                $duplicateFiles[] = basename($filePath);

                continue;
            }

            $uploads[$fileHash] = $filePath;
        }

        if ($duplicateFiles !== [] || $unreadableFiles !== []) {
            foreach ($uploads as $filePath) {
                DocumentVersion::removeUnreferencedUpload($filePath);
            }

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

        $fileHashes = array_keys($uploads);
        $filePaths = array_values($uploads);
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
            foreach ($filePaths as $filePath) {
                DocumentVersion::removeUnreferencedUpload($filePath);
            }

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
                str_starts_with(
                    (string) $message->body,
                    self::REVISION_UPLOAD_PREFIX
                )
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
}
