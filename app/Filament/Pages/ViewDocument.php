<?php

namespace App\Filament\Pages;

use App\Models\Document;
use App\Models\Note;
use App\Models\DocumentVersion;
use App\Models\ActivityLog;
use App\Models\ActionType;
use App\Models\DocumentType;
use App\Models\Message;
use App\Models\RejectedDocument;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
// use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class ViewDocument extends Page
{
    private const string REVISION_UPLOAD_PREFIX = 'A revised document was uploaded';

    private const string REVISION_DECISION_PREFIX = 'The revised document (';

    // use HasPageShield;

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

                        TextInput::make('document_name')
                            ->label('Document Name')
                            ->maxLength(255),

                        Select::make('office_unit')
                            ->label('Office / Unit')
                            ->options(fn () => \App\Models\OfficeUnit::query()
                                ->orderBy('name')
                                ->pluck('name', 'name'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        Textarea::make('particulars')
                            ->label('Document Details')
                            ->required()
                            ->rows(4),

                        Select::make('document_type')
                            ->label('Document Type')
                            ->options(fn () => DocumentType::query()
                                ->orderBy('type_name')
                                ->pluck('type_name', 'type_name'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        DatePicker::make('outgoing_date')
                            ->label('Outgoing Date')
                            ->default(now()->toDateString()),

                        TextInput::make('sent_to')
                            ->label('Sent To')
                            ->maxLength(255),

                        DatePicker::make('sent_date')
                            ->label('Sent Date')
                            ->default(now()->toDateString()),

                        TextInput::make('returned_from')
                            ->label('Returned From')
                            ->maxLength(255),

                        DatePicker::make('date_returned')
                            ->label('Returned Date')
                            ->default(now()->toDateString()),
                    ];
                }

                $incomingFields = [
                    TextInput::make('lao_number')
                        ->label('LAO Number')
                        ->required(),

                    TextInput::make('document_name')
                        ->label('Document Name')
                        ->maxLength(255),

                    Select::make('office_unit')
                        ->label('Office / Unit')
                        ->options(fn () => \App\Models\OfficeUnit::query()
                            ->orderBy('name')
                            ->pluck('name', 'name'))
                        ->searchable()
                        ->preload()
                        ->required(),

                    Textarea::make('particulars')
                        ->label('Document Details')
                        ->required()
                        ->rows(4),

                    Select::make('document_type')
                        ->label('Document Type')
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

                    Select::make('action_type')
                        ->label('Action Taken')
                        ->options(fn () => ActionType::query()
                            ->orderBy('action_name')
                            ->pluck('action_name', 'action_name'))
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    DatePicker::make('deadline')
                        ->label('Deadline')
                        ->default(now()->toDateString())
                        ->helperText('Preselected to today; calculated from the document type when configured.'),
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
                        ->label('Outgoing Date')
                        ->default(now()->toDateString()),

                    TextInput::make('sent_to')
                        ->label('Sent To')
                        ->maxLength(255),

                    DatePicker::make('sent_date')
                        ->label('Sent Date')
                        ->default(now()->toDateString()),

                    TextInput::make('returned_from')
                        ->label('Returned From')
                        ->maxLength(255),

                    DatePicker::make('date_returned')
                        ->label('Returned Date')
                        ->default(now()->toDateString()),

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
                    'document_name' => $this->documentRecord->document_name
                        ?: ($this->documentRecord->latestVersion?->file_path
                            ? basename($this->documentRecord->latestVersion->file_path)
                            : null),
                    'document_type' => $this->documentRecord->document_type,
                    'action_type' => $this->documentRecord->action_type,
                    'office_unit' => $this->documentRecord->office_unit,
                    'particulars' => $this->documentRecord->particulars,
                    'deadline' => $this->documentRecord->deadline ?? now()->toDateString(),
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
        ) || $this->hasPendingRevision();

        return Action::make('addVersion')
            ->label('')
            ->icon('heroicon-o-plus')
            ->iconButton()
            ->disabled($isLocked)
            ->tooltip($isLocked
                ? 'Uploading is disabled while this document is awaiting review'
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
                if (
                    in_array($this->documentRecord->status, ['pending', 'rejected'], true)
                    || $this->hasPendingRevision()
                ) {
                    Notification::make()
                        ->warning()
                        ->title('Version upload is disabled')
                        ->body('Review the pending revision before uploading another version.')
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

    /**
     * A revised version is reviewed from this document page instead of being
     * treated as a new request that needs a new LAO number.
     */
    public function hasPendingRevision(): bool
    {
        $conversation = $this->documentRecord->conversation()
            ->with([
                'messages' => fn ($query) => $query
                    ->oldest('created_at')
                    ->oldest('id'),
            ])
            ->first();

        if (! $conversation) {
            return false;
        }

        $pending = false;

        foreach ($conversation->messages as $message) {
            $body = (string) $message->body;

            if (
                (int) $message->sender_id === (int) $this->documentRecord->user_id
                && str_starts_with($body, self::REVISION_UPLOAD_PREFIX)
            ) {
                $pending = true;

                continue;
            }

            if (
                $pending
                && (
                    str_starts_with($body, self::REVISION_DECISION_PREFIX)
                    || str_starts_with($body, 'The revised document was ')
                )
            ) {
                $pending = false;
            }
        }

        return $pending;
    }

    public function acceptRevisionAction(): Action
    {
        return Action::make('acceptRevision')
            ->label('')
            ->icon('heroicon-o-check')
            ->iconButton()
            ->color('success')
            ->size('xs')
            ->tooltip('Accept revision')
            ->modalHeading('Accept revised document')
            ->modalDescription('Accept this revised version and keep the existing LAO number?')
            ->modalIcon('heroicon-o-check-circle')
            ->modalIconColor('success')
            ->modalSubmitActionLabel('Accept')
            ->action(function (array $arguments): void {
                $this->acceptRevision((int) ($arguments['version'] ?? 0));
            });
    }

    public function acceptRevision(int $versionId): void
    {
        $document = DB::transaction(function () use ($versionId): Document {
            $document = Document::query()
                ->with('user')
                ->whereKey($this->documentRecord->document_id)
                ->lockForUpdate()
                ->firstOrFail();

            $version = $document->versions()
                ->whereKey($versionId)
                ->firstOrFail();

            $latestVersionId = $document->versions()
                ->latest('created_at')
                ->latest('version_id')
                ->value('version_id');

            abort_unless(
                (int) $version->version_id === (int) $latestVersionId,
                403,
                'Only the latest revision can be reviewed.'
            );
            abort_unless($this->hasPendingRevision(), 403, 'There is no pending revision to accept.');

            $document->update([
                // Accepting a revision never generates or changes the LAO number.
                'lao_number' => $document->lao_number,
                'status' => 'in_progress',
                'rejection_reason' => null,
            ]);

            $conversation = $document->conversation()->first();

            if ($conversation) {
                Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => auth()->id(),
                    'body' => 'The revised document (version ' .
                        $version->version_number .
                        ') was accepted and is now being processed.',
                ]);

                $conversation->touch();
            }

            return $document;
        });

        $this->documentRecord->load([
            'user',
            'notes.user',
            'versions',
            'latestVersion',
            'rejections',
            'activityLogs.user',
        ]);

        $this->logDocumentActivity(
            'Revised document accepted',
            'Accepted the revised document without changing the LAO number.'
        );

        Notification::make()
            ->success()
            ->title('Revision accepted')
            ->body('The existing LAO number was kept unchanged.')
            ->send();
    }

    public function rejectRevisionAction(): Action
    {
        return Action::make('rejectRevision')
            ->label('')
            ->icon('heroicon-o-x-mark')
            ->iconButton()
            ->color('danger')
            ->size('xs')
            ->tooltip('Reject revision')
            ->modalHeading('Reject revised document')
            ->modalDescription('Please provide a reason for rejecting this revised version.')
            ->modalIcon('heroicon-o-x-circle')
            ->modalIconColor('danger')
            ->modalSubmitActionLabel('Reject')
            ->schema([
                Select::make('reason')
                    ->label('Rejection reason')
                    ->placeholder('Select a reason')
                    ->options([
                        'Incomplete or missing information' => 'Incomplete or missing information',
                        'Incorrect document type' => 'Incorrect document type',
                        'Missing signature or approval' => 'Missing signature or approval',
                        'Unreadable or corrupted file' => 'Unreadable or corrupted file',
                        'other' => 'Other',
                    ])
                    ->live()
                    ->required(),
                Textarea::make('custom_reason')
                    ->label('Specify other reason')
                    ->placeholder('Type the reason for rejecting this revision')
                    ->visible(fn (Get $get): bool => $get('reason') === 'other')
                    ->required(fn (Get $get): bool => $get('reason') === 'other')
                    ->rows(4)
                    ->maxLength(5000),
            ])
            ->action(function (array $data, array $arguments): void {
                $reason = $data['reason'] === 'other'
                    ? trim((string) ($data['custom_reason'] ?? ''))
                    : (string) $data['reason'];

                $this->rejectRevision(
                    (int) ($arguments['version'] ?? 0),
                    $reason,
                );
            });
    }

    public function rejectRevision(int $versionId, string $reason): void
    {
        [$document, $rejection] = DB::transaction(function () use ($versionId, $reason): array {
            $document = Document::query()
                ->with('user')
                ->whereKey($this->documentRecord->document_id)
                ->lockForUpdate()
                ->firstOrFail();

            $version = $document->versions()
                ->whereKey($versionId)
                ->firstOrFail();

            $latestVersionId = $document->versions()
                ->latest('created_at')
                ->latest('version_id')
                ->value('version_id');

            abort_unless(
                (int) $version->version_id === (int) $latestVersionId,
                403,
                'Only the latest revision can be reviewed.'
            );
            abort_unless($this->hasPendingRevision(), 403, 'There is no pending revision to reject.');

            $rejection = RejectedDocument::create([
                'document_id' => $document->document_id,
                'reason' => $reason,
            ]);

            $conversation = $document->conversation()->first();

            if ($conversation) {
                Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => auth()->id(),
                    'body' => 'The revised document (version ' .
                        $version->version_number .
                        ') was rejected. Reason: ' . $reason,
                ]);

                $conversation->touch();
            }

            return [$document, $rejection];
        });

        $this->documentRecord->load([
            'user',
            'notes.user',
            'versions',
            'latestVersion',
            'rejections',
            'activityLogs.user',
        ]);

        $this->logDocumentActivity(
            'Revised document rejected',
            'Rejected the revised document: ' . $reason
        );

        Notification::make()
            ->success()
            ->title('Revision rejected')
            ->body('The rejection reason was sent to the client. The existing LAO number was kept unchanged.')
            ->send();
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

    /** Generate an authenticated preview URL for browser-supported and Word files. */
    protected function generatePreview(): string
    {
        $version = $this->documentRecord->latestVersion;
        if (! $version?->file_path || ! $version->storageDisk()->exists($version->file_path)) {
            return '';
        }

        if (! in_array(strtolower(pathinfo($version->file_path, PATHINFO_EXTENSION)), ['pdf', 'doc', 'docx'], true)) {
            return '';
        }

        return route('admin.documents.preview', ['document' => $this->documentRecord->document_id]);
    }
}
