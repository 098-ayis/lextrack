<?php

namespace App\Filament\Pages;

use App\Models\Document;
use App\Models\Note;
use App\Models\DocumentVersion;
use App\Models\ActivityLog;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ViewDocument extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = null;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'View Documents';

    protected static ?string $slug = 'documents/{document}';

    protected string $view = 'filament.pages.view-document';

    public Document $documentRecord;

    /**
     * URL used by the Blade iframe.
     */
    public string $previewUrl = '';

    public ?int $selectedVersionId = null;

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    /**
     * Hide Filament's built-in page heading.
     */
    public function getHeading(): string|Htmlable
    {
        return '';
    }

    public function mount(string|int $document): void
    {
        $this->documentRecord = Document::where(
            'document_id',
            $document
        )->with([
            'user',
            'notes.user',
            'type',
            'actionType',
            'versions',
            'latestVersion',
            'rejections',
            'activityLogs.user',
        ])->firstOrFail();

        $this->previewUrl = $this->generatePreview();

        $this->logDocumentActivity(
            'Document viewed',
            'Opened the document viewer.'
        );
    }

    public function addNoteAction(): Action
    {
        return Action::make('addNote')
            ->label('Add Notes')
            ->icon('heroicon-o-plus')
            ->size('sm')
            ->color('gray')
            ->extraAttributes([
                'class' => 'add-note-button !h-8 !min-h-8 !rounded-full !border-0
                            !bg-[#5B5CE2] !px-3 !py-1 !text-[11px]
                            !font-semibold !text-white !shadow-none
                            hover:!bg-[#4F50D0]',
                'style' => 'color: #ffffff;',
            ])
            ->modalHeading('Add Notes')
            ->modalSubmitActionLabel('Save Note')
            ->schema([
                Textarea::make('note')
                    ->label('Note')
                    ->placeholder('Write a note about this document...')
                    ->required()
                    ->rows(5)
                    ->maxLength(5000),
            ])
            ->action(function (array $data, array $arguments): void {
                Note::create([
                    'document_id' => $arguments['document'] ?? $this->documentRecord->document_id,
                    'user_id' => auth()->id(),
                    'note' => $data['note'],
                ]);

                $this->logDocumentActivity(
                    'Note added',
                    'Added a document note.',
                    null,
                    $data['note']
                );

                $this->documentRecord->load([
                    'notes.user',
                    'versions',
                    'activityLogs.user',
                ]);

                Notification::make()
                    ->success()
                    ->title('Note added')
                    ->send();
            });
    }

    public function editDocumentDetailsAction(): Action
    {
        $status = (string) $this->documentRecord->status;
        $isLocked = in_array($status, ['pending', 'rejected'], true);

        return Action::make('editDocumentDetails')
            ->label('')
            ->icon('heroicon-o-pencil-square')
            ->iconButton()
            ->color('gray')
            ->disabled($isLocked)
            ->tooltip($isLocked
                ? 'Editing is disabled for pending or rejected documents'
                : 'Edit document details')
            ->extraAttributes([
                'class' => 'h-7 w-7 rounded-md p-1 text-gray-700 ' .
                    ($isLocked
                        ? 'cursor-not-allowed opacity-50'
                        : 'hover:bg-gray-100'),
            ])
            ->modalHeading('Edit Document Details')
            ->modalSubmitActionLabel('Save Changes')
            ->schema(function (): array {
                if ($this->documentRecord->status === 'outgoing') {
                    return [
                        TextInput::make('lao_number')
                            ->label('LAO Number')
                            ->required(),

                        TextInput::make('office_unit')
                            ->label('Office / Unit')
                            ->required(),

                        Textarea::make('particulars')
                            ->label('Document Details')
                            ->required()
                            ->rows(4),

                        Select::make('type_id')
                            ->label('Document Type')
                            ->options(
                                \App\Models\DocumentType::query()
                                    ->pluck('type_name', 'type_id')
                            )
                            ->searchable()
                            ->required(),

                        DatePicker::make('outgoing_date')
                            ->label('Outgoing Date'),

                        TextInput::make('sent_to')
                            ->label('Sent To')
                            ->maxLength(255),

                        DatePicker::make('sent_date')
                            ->label('Sent Date'),

                        TextInput::make('returned_from')
                            ->label('Returned From')
                            ->maxLength(255),

                        DatePicker::make('date_returned')
                            ->label('Returned Date'),
                    ];
                }

                $incomingFields = [
                    TextInput::make('lao_number')
                        ->label('LAO Number')
                        ->required(),

                    TextInput::make('office_unit')
                        ->label('Office / Unit')
                        ->required(),

                    Textarea::make('particulars')
                        ->label('Document Details')
                        ->required()
                        ->rows(4),

                    Select::make('type_id')
                        ->label('Document Type')
                        ->options(
                            \App\Models\DocumentType::query()
                                ->pluck('type_name', 'type_id')
                        )
                        ->searchable()
                        ->required(),

                    Select::make('action_id')
                        ->label('Action Taken')
                        ->options(
                            \App\Models\ActionType::query()
                                ->orderBy('action_name')
                                ->pluck('action_name', 'action_id')
                        )
                        ->searchable()
                        ->nullable(),

                    DatePicker::make('deadline')
                        ->label('Deadline'),
                ];

                if ($this->documentRecord->status !== 'completed') {
                    return $incomingFields;
                }

                return [
                    ...$incomingFields,

                    Select::make('status')
                        ->label('Status')
                        ->options([
                            'pending' => 'Pending',
                            'in_progress' => 'In Progress',
                            'completed' => 'Completed',
                            'returned' => 'Returned',
                            'outgoing' => 'Outgoing',
                            'rejected' => 'Rejected',
                        ])
                        ->required(),

                    DatePicker::make('outgoing_date')
                        ->label('Outgoing Date'),

                    TextInput::make('sent_to')
                        ->label('Sent To')
                        ->maxLength(255),

                    DatePicker::make('sent_date')
                        ->label('Sent Date'),

                    TextInput::make('returned_from')
                        ->label('Returned From')
                        ->maxLength(255),

                    DatePicker::make('date_returned')
                        ->label('Returned Date'),

                    FileUpload::make('file_path')
                        ->label('Upload New Document Version')
                        ->disk('local')
                        ->directory('documents/versions')
                        ->preserveFilenames(),
                ];
            })
            ->fillForm(function (): array {
                return [
                    'lao_number' => $this->documentRecord->lao_number,
                    'type_id' => $this->documentRecord->type_id,
                    'action_id' => $this->documentRecord->action_id,
                    'office_unit' => $this->documentRecord->office_unit,
                    'particulars' => $this->documentRecord->particulars,
                    'deadline' => $this->documentRecord->deadline,
                    'status' => $this->documentRecord->status,
                    'outgoing_date' => $this->documentRecord->outgoing_date,
                    'sent_to' => $this->documentRecord->sent_to,
                    'sent_date' => $this->documentRecord->sent_date,
                    'returned_from' => $this->documentRecord->returned_from,
                    'date_returned' => $this->documentRecord->date_returned,
                ];
            })
            ->action(function (array $data): void {
                $document = Document::findOrFail($this->documentRecord->document_id);

                if (in_array($document->status, ['pending', 'rejected'], true)) {
                    Notification::make()
                        ->warning()
                        ->title('Document details cannot be edited')
                        ->body('Pending and rejected documents are locked.')
                        ->send();

                    return;
                }

                $filePath = $data['file_path'] ?? null;
                unset($data['file_path']);

                $oldValues = $document->only(array_keys($data));

                $document->fill($data);
                $updatedFields = array_keys($document->getDirty());
                $document->save();

                if (filled($filePath)) {
                    DocumentVersion::create([
                        'document_id' => $document->document_id,
                        'user_id' => auth()->id(),
                        'version_number' => (string) $this->getNextVersionNumber($document),
                        'file_path' => $filePath,
                    ]);

                    $updatedFields[] = 'Document File';
                }

                $updatedSummary = $updatedFields !== []
                    ? implode(', ', $updatedFields) . ' changed'
                    : 'No fields changed';

                $this->logDocumentActivity(
                    'Document details updated',
                    $updatedSummary,
                    (string) json_encode($oldValues),
                    (string) json_encode($document->only(array_keys($data)))
                );

                $this->documentRecord->load([
                    'user',
                    'type',
                    'actionType',
                    'rejections',
                    'versions',
                    'latestVersion',
                    'activityLogs.user',
                ]);

                Notification::make()
                    ->success()
                    ->title('Document details updated')
                    ->send();
            });
    }

    public function addVersionAction(): Action
    {
        $isLocked = in_array(
            $this->documentRecord->status,
            ['pending', 'rejected'],
            true
        );

        return Action::make('addVersion')
            ->label('')
            ->icon('heroicon-o-plus')
            ->iconButton()
            ->disabled($isLocked)
            ->tooltip($isLocked
                ? 'Uploading is disabled for pending or rejected documents'
                : 'Add attachment')
            ->extraAttributes([
                'class' => 'h-7 w-7 rounded-md p-1 text-gray-900 ' .
                    ($isLocked
                        ? 'cursor-not-allowed opacity-50'
                        : 'hover:bg-gray-100'),
            ])
            ->modalHeading('Upload Document')
            ->modalSubmitActionLabel('Upload')
            ->schema([
                FileUpload::make('file_path')
                    ->label('PDF File')
                    ->disk('local')
                    ->directory('documents/versions')
                    ->preserveFilenames()
                    ->acceptedFileTypes(['application/pdf'])
                    ->required(),
            ])
            ->action(function (array $data): void {
                if (in_array($this->documentRecord->status, ['pending', 'rejected'], true)) {
                    Notification::make()
                        ->warning()
                        ->title('Version upload is disabled')
                        ->body('Pending and rejected documents are locked.')
                        ->send();

                    return;
                }

                $version = null;
                $versionNumber = null;

                DB::transaction(function () use ($data, &$version, &$versionNumber): void {
                    $document = Document::query()
                        ->whereKey($this->documentRecord->document_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $versionNumber = $this->getNextVersionNumber(
                        $document->versions()->get()
                    );

                    $version = DocumentVersion::create([
                        'document_id' => $document->document_id,
                        'user_id' => auth()->id(),
                        'version_number' => (string) $versionNumber,
                        'file_path' => $data['file_path'],
                    ]);
                });

                $this->logDocumentActivity(
                    'Version uploaded',
                    'Uploaded ' . basename($version->file_path) .
                    ' as version ' . $versionNumber . '.'
                );

                $this->documentRecord->load([
                    'notes.user',
                    'type',
                    'versions',
                    'latestVersion',
                    'activityLogs.user',
                ]);

                Notification::make()
                    ->success()
                    ->title('Document version added')
                    ->send();
            });
    }

    public function selectVersion(int $versionId): void
    {
        $version = $this->documentRecord->versions()
            ->whereKey($versionId)
            ->firstOrFail();

        $this->selectedVersionId = $version->version_id;
        $this->previewUrl = route('admin.document.version.preview', [
            'document' => $this->documentRecord->document_id,
            'version' => $version->version_id,
        ]);

        $this->logDocumentActivity(
            'Version viewed',
            'Viewed ' . basename($version->file_path) .
            ' (version ' . $version->version_number . ').'
        );
    }

    public function deleteVersion(int $versionId): void
    {
        $version = $this->documentRecord->versions()
            ->whereKey($versionId)
            ->firstOrFail();

        $filePath = (string) $version->file_path;

        $disk = $version->storageDisk();

        if (
            $filePath !== '' &&
            $disk->exists($filePath)
        ) {
            $disk->delete($filePath);
        }

        $versionNumber = $version->version_number;
        $version->delete();

        if ($this->selectedVersionId === $versionId) {
            $this->selectedVersionId = null;
            $this->previewUrl = $this->generatePreview();
        }

        $this->logDocumentActivity(
            'Version deleted',
            'Deleted ' . basename($filePath) .
            ' (version ' . $versionNumber . ').'
        );

        $this->documentRecord->load([
            'notes.user',
            'type',
            'versions',
            'latestVersion',
            'activityLogs.user',
        ]);

        Notification::make()
            ->success()
            ->title('Document version deleted')
            ->send();
    }

    public function deleteVersionAction(): Action
    {
        return Action::make('deleteVersion')
            ->label('')
            ->icon('heroicon-o-trash')
            ->iconButton()
            ->color('danger')
            ->tooltip('Delete version')
            ->extraAttributes([
                'class' => 'h-7 w-7 rounded-md p-1',
            ])
            ->requiresConfirmation()
            ->modalHeading('Delete document version')
            ->modalDescription('Are you sure you want to delete this uploaded version?')
            ->modalSubmitActionLabel('Delete version')
            ->action(function (array $arguments): void {
                $this->deleteVersion((int) $arguments['version']);
            });
    }

    public function viewAllAuditTrailsAction(): Action
    {
        return Action::make('viewAllAuditTrails')
            ->label('')
            ->icon('heroicon-o-list-bullet')
            ->iconButton()
            ->color('gray')
            ->size('sm')
            ->tooltip('View all audit trails')
            ->extraAttributes([
                'class' => 'h-7 w-7 rounded-md p-1 text-gray-700 hover:bg-gray-100',
            ])
            ->modalHeading('All Audit Trails')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->modalContent(fn () => view('filament.pages.audit-trails-modal', [
                'logs' => $this->documentRecord->activityLogs
                    ->sortByDesc('created_at')
                    ->values(),
            ]));
    }

    public function selectCurrentDocument(): void
    {
        $this->selectedVersionId = null;
        $this->previewUrl = $this->generatePreview();

        $latestFilePath = $this->documentRecord->latestVersion?->file_path;

        $this->logDocumentActivity(
            'Current document viewed',
            $latestFilePath
                ? 'Viewed ' . basename($latestFilePath) . '.'
                : 'Viewed the current document without an attachment.'
        );
    }

    protected function getNextVersionNumber($versions = null): int
    {
        $highestVersion = ($versions ?? $this->documentRecord->versions)
            ->map(function (DocumentVersion $version): int {
                preg_match('/(\d+)\s*$/', (string) $version->version_number, $matches);

                return (int) ($matches[1] ?? 0);
            })
            ->max() ?? 0;

        return $highestVersion + 1;
    }

    public function goBack(): void
    {
        $this->js('window.history.back()');
    }

    protected function logDocumentActivity(
        string $actionType,
        string $actionDetails = '',
        ?string $oldValue = null,
        ?string $newValue = null
    ): void {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'document_id' => $this->documentRecord->document_id,
            'action_type' => $actionType,
            'action_details' => $actionDetails !== ''
                ? $actionDetails
                : $actionType,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ]);

        $this->documentRecord->load('activityLogs.user');
    }

    public function editNoteAction(): Action
    {
        return Action::make('editNote')
            ->label('Edit')
            ->icon('heroicon-o-pencil-square')
            ->color('gray')
            ->extraAttributes([
                'class' => 'w-full justify-start rounded-md px-3 py-2 text-xs',
            ])
            ->modalHeading('Edit Note')
            ->modalSubmitActionLabel('Save Changes')
            ->schema([
                Textarea::make('note')
                    ->label('Note')
                    ->required()
                    ->rows(5)
                    ->maxLength(5000),
            ])
            ->fillForm(function (array $arguments): array {
                $note = Note::where(
                    'document_id',
                    $this->documentRecord->document_id
                )->findOrFail($arguments['note']);

                return ['note' => $note->note];
            })
            ->action(function (array $data, array $arguments): void {
                $note = Note::where(
                    'document_id',
                    $this->documentRecord->document_id
                )->findOrFail($arguments['note']);

                $note->update(['note' => $data['note']]);
                $this->logDocumentActivity(
                    'Note updated',
                    'Updated a document note.',
                    null,
                    $data['note']
                );
                $this->documentRecord->load('notes.user');

                Notification::make()
                    ->success()
                    ->title('Note updated')
                    ->send();
            });
    }

    public function deleteNoteAction(): Action
    {
        return Action::make('deleteNote')
            ->label('Delete')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->extraAttributes([
                'class' => 'w-full justify-start rounded-md px-3 py-2 text-xs',
            ])
            ->requiresConfirmation()
            ->modalHeading('Delete Note')
            ->modalDescription('Are you sure you want to delete this note?')
            ->modalSubmitActionLabel('Delete')
            ->action(function (array $arguments): void {
                Note::where(
                    'document_id',
                    $this->documentRecord->document_id
                )->findOrFail($arguments['note'])->delete();

                $this->logDocumentActivity(
                    'Note deleted',
                    'Deleted a document note.'
                );

                $this->documentRecord->load('notes.user');

                Notification::make()
                    ->success()
                    ->title('Note deleted')
                    ->send();
            });
    }

    /**
     * Generate a preview URL for the document.
     *
     * PDF:
     *     Display directly.
     *
     * DOC / DOCX:
     *     Convert to a temporary PDF using LibreOffice.
     */
    protected function generatePreview(): string
    {
        $path = $this->documentRecord->latestVersion?->file_path;

        if (!$path) {
            return '';
        }

        $disk = $this->documentRecord->latestVersion?->storageDisk()
            ?? Storage::disk('local');

        if (!$disk->exists($path)) {
            return '';
        }

        $extension = strtolower(
            pathinfo($path, PATHINFO_EXTENSION)
        );

        // PDF
        if ($extension === 'pdf') {
            return route(
                'admin.documents.preview',
                [
                    'document' => $this->documentRecord->document_id,
                ]
            );
        }

        if (!in_array($extension, ['doc', 'docx'], true)) {
            return '';
        }

        $source = $disk->path($path);

        $previewDirectory = storage_path(
            'app/private/temp-previews'
        );

        if (!is_dir($previewDirectory)) {
            mkdir($previewDirectory, 0775, true);
        }

        $previewName = md5($path) . '.pdf';

        $previewPath =
            $previewDirectory . '/' . $previewName;

        if (
            file_exists($previewPath) &&
            filemtime($previewPath) >= filemtime($source)
        ) {
            return route(
                'admin.document.temp-preview',
                ['file' => $previewName]
            );
        }

        if (file_exists($previewPath)) {
            unlink($previewPath);
        }

        $command = sprintf(
            'libreoffice --headless --convert-to pdf --outdir %s %s 2>&1',
            escapeshellarg($previewDirectory),
            escapeshellarg($source)
        );

        $output = [];
        $exitCode = 0;

        exec(
            $command,
            $output,
            $exitCode
        );

        if ($exitCode !== 0) {
            logger()->error(
                'LibreOffice conversion failed',
                [
                    'document_id' =>
                        $this->documentRecord->document_id,
                    'source' => $source,
                    'exit_code' => $exitCode,
                    'output' => $output,
                ]
            );

            return '';
        }

        $generatedPdf =
            $previewDirectory .
            '/' .
            pathinfo($source, PATHINFO_FILENAME) .
            '.pdf';

        if (!file_exists($generatedPdf)) {
            logger()->error(
                'LibreOffice PDF was not generated',
                [
                    'document_id' =>
                        $this->documentRecord->document_id,
                    'expected' => $generatedPdf,
                    'output' => $output,
                ]
            );

            return '';
        }

        if ($generatedPdf !== $previewPath) {
            rename(
                $generatedPdf,
                $previewPath
            );
        }

        return route(
            'admin.document.temp-preview',
            ['file' => $previewName]
        );
    }
}
