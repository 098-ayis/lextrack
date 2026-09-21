<?php

namespace App\Filament\Pages;

use App\Models\Document;
use App\Models\Note;
use App\Models\DocumentVersion;
use App\Models\ActivityLog;
use App\Models\ActionType;
use App\Models\DocumentType;
use App\Models\OfficeUnit;
use App\Models\Message;
use App\Models\RejectedDocument;
use App\Rules\UniqueDocumentVersionUpload;
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
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Js;
use Illuminate\Validation\Rule;
// use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class ViewDocument extends Page
{
    public const string OTHER_DOCUMENT_TYPE_VALUE = '__custom_document_type__';

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

    public ?int $previewPageCount = null;

    public ?int $selectedVersionId = null;

    public bool $isTransmittalSelected = false;

    public bool $isEditingDetails = false;

    public array $documentDetailsForm = [];

    public array $originalDocumentDetailsForm = [];

    public bool $isAddingNote = false;

    public string $newNoteText = '';

    public ?int $editingNoteId = null;

    public string $editingNoteText = '';

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
        $this->documentRecord = Document::findForRoute($document);
        $this->documentRecord->load([
            'user',
            'notes.user',
            'versions',
            'latestVersion',
            'rejections',
            'activityLogs.user',
        ]);

        $this->previewUrl = $this->generatePreview();
        $this->previewPageCount = $this->pageCountForVersion($this->documentRecord->latestVersion);
    }

    public function startEditingDetails(): void
    {
        if (in_array($this->documentRecord->status, ['pending', 'rejected'], true)) {
            Notification::make()
                ->warning()
                ->title('Document details cannot be edited')
                ->body('Pending and rejected documents are locked.')
                ->send();

            return;
        }

        $this->documentDetailsForm = [
            'lao_number' => $this->documentRecord->lao_number,
            'document_type' => $this->documentRecord->document_type,
            'document_type_other' => '',
            'office_unit' => $this->documentRecord->office_unit,
            'particulars' => $this->documentRecord->particulars ?: $this->documentRecord->description,
            'action_type' => $this->documentRecord->action_type,
            'deadline' => $this->documentRecord->deadline?->format('Y-m-d'),
            'outgoing_date' => $this->documentRecord->outgoing_date?->format('Y-m-d'),
            'sent_date' => $this->documentRecord->sent_date?->format('Y-m-d'),
            'sent_to' => $this->documentRecord->sent_to,
            'returned_from' => $this->documentRecord->returned_from,
            'date_returned' => $this->documentRecord->date_returned?->format('Y-m-d'),
        ];

        if ($this->documentRecord->status === 'completed') {
            $this->documentDetailsForm['status'] = $this->documentRecord->status;
        }

        $this->originalDocumentDetailsForm = $this->documentDetailsForm;
        $this->resetValidation();
        $this->isEditingDetails = true;
    }

    public function cancelEditingDetails(): void
    {
        $this->isEditingDetails = false;
        $this->documentDetailsForm = [];
        $this->originalDocumentDetailsForm = [];
        $this->resetValidation();
    }

    public function hasDocumentDetailsChanges(): bool
    {
        if (! $this->isEditingDetails) {
            return false;
        }

        $original = $this->originalDocumentDetailsForm;
        $current = $this->documentDetailsForm;

        if (($current['document_type'] ?? null) === self::OTHER_DOCUMENT_TYPE_VALUE) {
            $customType = trim((string) ($current['document_type_other'] ?? ''));
            $current['document_type'] = $customType !== ''
                ? $customType
                : ($original['document_type'] ?? null);
        }

        unset($original['document_type_other'], $current['document_type_other']);

        $normalize = static fn (array $values): array => array_map(
            static fn ($value) => $value === '' ? null : $value,
            $values,
        );

        return $normalize($current) !== $normalize($original);
    }

    public function getActionTypeOptions(): array
    {
        $options = ActionType::query()
            ->orderBy('action_name')
            ->pluck('action_name', 'action_name')
            ->all();

        $currentActionType = $this->documentRecord->action_type;

        if (filled($currentActionType) && ! array_key_exists($currentActionType, $options)) {
            $options = [$currentActionType => $currentActionType.' (existing)'] + $options;
        }

        return $options;
    }

    public function getDocumentTypeOptions(): array
    {
        $options = DocumentType::query()
            ->orderedForChoices()
            ->pluck('type_name', 'type_name')
            ->all();

        $currentDocumentType = $this->documentRecord->document_type;

        if (filled($currentDocumentType) && ! array_key_exists($currentDocumentType, $options)) {
            $options = [$currentDocumentType => $currentDocumentType.' (existing)'] + $options;
        }

        $options[self::OTHER_DOCUMENT_TYPE_VALUE] = 'Others';

        return $options;
    }

    public function getOfficeUnitOptions(): array
    {
        $options = OfficeUnit::query()
            ->orderBy('name')
            ->pluck('name', 'name')
            ->all();

        $currentOfficeUnit = $this->documentRecord->office_unit;

        if (filled($currentOfficeUnit) && ! array_key_exists($currentOfficeUnit, $options)) {
            $options = [$currentOfficeUnit => $currentOfficeUnit.' (existing)'] + $options;
        }

        return $options;
    }

    public function saveDocumentDetails(): void
    {
        if (! $this->hasDocumentDetailsChanges()) {
            return;
        }

        $document = Document::findOrFail($this->documentRecord->document_id);

        if (in_array($document->status, ['pending', 'rejected'], true)) {
            $this->cancelEditingDetails();

            Notification::make()
                ->warning()
                ->title('Document details cannot be edited')
                ->body('Pending and rejected documents are locked.')
                ->send();

            return;
        }

        $actionTypeOptions = ActionType::query()
            ->pluck('action_name', 'action_name')
            ->all();

        if (filled($document->action_type)) {
            $actionTypeOptions[$document->action_type] = $document->action_type;
        }

        $documentTypeOptions = DocumentType::query()
            ->orderedForChoices()
            ->pluck('type_name', 'type_name')
            ->all();

        if (filled($document->document_type)) {
            $documentTypeOptions[$document->document_type] = $document->document_type;
        }

        $officeUnitOptions = OfficeUnit::query()
            ->pluck('name', 'name')
            ->all();

        if (filled($document->office_unit)) {
            $officeUnitOptions[$document->office_unit] = $document->office_unit;
        }

        $rules = [
            'documentDetailsForm.lao_number' => ['required', 'string', 'max:255'],
            'documentDetailsForm.document_type' => ['required', 'string', Rule::in([...array_keys($documentTypeOptions), self::OTHER_DOCUMENT_TYPE_VALUE])],
            'documentDetailsForm.document_type_other' => ['nullable', 'required_if:documentDetailsForm.document_type,'.self::OTHER_DOCUMENT_TYPE_VALUE, 'string', 'max:255'],
            'documentDetailsForm.office_unit' => ['required', 'string', 'max:255', Rule::in(array_keys($officeUnitOptions))],
            'documentDetailsForm.particulars' => ['required', 'string', 'max:5000'],
            'documentDetailsForm.action_type' => ['nullable', 'string', 'max:255', Rule::in(array_keys($actionTypeOptions))],
            'documentDetailsForm.deadline' => ['nullable', 'date'],
            'documentDetailsForm.outgoing_date' => ['nullable', 'date'],
            'documentDetailsForm.sent_date' => ['nullable', 'date'],
            'documentDetailsForm.sent_to' => ['nullable', 'string', 'max:255'],
            'documentDetailsForm.returned_from' => ['nullable', 'string', 'max:255'],
            'documentDetailsForm.date_returned' => ['nullable', 'date'],
        ];

        if ($document->status === 'completed') {
            $rules['documentDetailsForm.status'] = ['required', 'in:pending,in_progress,completed,returned,outgoing,rejected'];
        }

        $validated = $this->validate($rules);
        $data = $validated['documentDetailsForm'];
        if (($data['document_type'] ?? null) === self::OTHER_DOCUMENT_TYPE_VALUE) {
            $data['document_type'] = trim($data['document_type_other']);
        }
        unset($data['document_type_other']);
        $data['lao_number'] = $document->lao_number;
        $oldValues = $document->only(array_keys($data));

        $document->fill($data);
        $updatedFields = array_keys($document->getDirty());
        $document->save();

        if ($updatedFields !== []) {
            $newValues = $document->only(array_keys($data));

            $this->logDocumentActivity(
                'Document details updated',
                implode("\n", $this->formatDocumentDetailsChanges($oldValues, $newValues)),
                (string) json_encode($oldValues),
                (string) json_encode($newValues),
            );
        }

        $this->documentRecord->refresh()->load([
            'user',
            'notes.user',
            'versions',
            'latestVersion',
            'rejections',
            'activityLogs.user',
        ]);
        $this->isEditingDetails = false;
        $this->documentDetailsForm = [];
        $this->originalDocumentDetailsForm = [];
        $this->resetValidation();

        Notification::make()
            ->success()
            ->title('Document details updated successfully')
            ->send();
    }

    public function startAddingNote(): void
    {
        $this->newNoteText = '';
        $this->resetValidation('newNoteText');
        $this->isAddingNote = true;
    }

    public function cancelAddingNote(): void
    {
        $this->isAddingNote = false;
        $this->newNoteText = '';
        $this->resetValidation('newNoteText');
    }

    public function saveNote(): void
    {
        $validated = $this->validate([
            'newNoteText' => ['required', 'string', 'max:5000'],
        ]);
        $noteText = trim($validated['newNoteText']);

        Note::create([
            'document_id' => $this->documentRecord->document_id,
            'user_id' => auth()->id(),
            'note' => $noteText,
        ]);

        $this->logDocumentActivity(
            'Note added',
            'Added a document note.',
            null,
            $noteText
        );

        $this->documentRecord->load([
            'notes.user',
            'versions',
            'activityLogs.user',
        ]);
        $this->isAddingNote = false;
        $this->newNoteText = '';
        $this->resetValidation('newNoteText');

        Notification::make()
            ->success()
            ->title('Note added')
            ->send();
    }

    public function startEditingNote(int $noteId): void
    {
        $note = Note::where('document_id', $this->documentRecord->document_id)
            ->findOrFail($noteId);

        $this->editingNoteId = (int) $note->note_id;
        $this->editingNoteText = (string) $note->note;
        $this->resetValidation('editingNoteText');
    }

    public function cancelEditingNote(): void
    {
        $this->editingNoteId = null;
        $this->editingNoteText = '';
        $this->resetValidation('editingNoteText');
    }

    public function saveNoteEdit(): void
    {
        $validated = $this->validate([
            'editingNoteText' => ['required', 'string', 'max:5000'],
        ]);
        $note = Note::where('document_id', $this->documentRecord->document_id)
            ->findOrFail($this->editingNoteId);
        $oldNote = (string) $note->note;
        $newNote = trim($validated['editingNoteText']);

        if ($oldNote !== $newNote) {
            $note->update(['note' => $newNote]);

            $this->logDocumentActivity(
                'Note updated',
                'Updated a document note.',
                $oldNote,
                $newNote
            );
        }

        $this->documentRecord->load('notes.user');
        $this->cancelEditingNote();

        Notification::make()
            ->success()
            ->title($oldNote === $newNote ? 'No changes made' : 'Note updated')
            ->send();
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
            ->tooltip($status === 'pending'
                ? 'Accept the document first'
                : ($status === 'rejected'
                    ? 'Editing is disabled for rejected documents'
                    : 'Edit document details'))
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
                            ->label('Particulars')
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
                        ->label('Particulars')
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
                            'in_progress' => 'Incoming',
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

                $fileHash = filled($filePath)
                    ? DocumentVersion::hashForUpload($filePath)
                    : null;

                if (filled($filePath) && $fileHash === null) {
                    DocumentVersion::removeUnreferencedUpload($filePath);

                    Notification::make()
                        ->danger()
                        ->title('Upload could not be verified')
                        ->body('The uploaded file could not be read. Please select the file again and try again.')
                        ->send();

                    return;
                }

                if (
                    filled($filePath)
                    && DocumentVersion::existsForDocumentOrUserHash(
                        $this->documentRecord->document_id,
                        $fileHash,
                        auth()->id(),
                    )
                ) {
                    DocumentVersion::removeUnreferencedUpload($filePath);

                    Notification::make()
                        ->danger()
                        ->title('Duplicate document detected')
                        ->body('This exact file has already been uploaded for this document. Please select a different file.')
                        ->send();

                    return;
                }

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
                        'file_hash' => $fileHash,
                    ]);

                    $updatedFields[] = 'Document File';
                }

                if ($updatedFields !== []) {
                    $newValues = $document->only(array_keys($data));
                    $changeDetails = implode("\n", $this->formatDocumentDetailsChanges($oldValues, $newValues));

                    if (filled($filePath)) {
                        $changeDetails .= ($changeDetails !== '' ? "\n" : '')
                            . 'Document File: uploaded ' . basename($filePath) . '.';
                    }

                    $this->logDocumentActivity(
                        'Document details updated',
                        $changeDetails !== '' ? $changeDetails : 'Document file updated.',
                        (string) json_encode($oldValues),
                        (string) json_encode($newValues)
                    );
                }

                $this->documentRecord->load([
                    'user',
                    'rejections',
                    'versions',
                    'latestVersion',
                    'activityLogs.user',
                ]);

                Notification::make()
                    ->success()
                    ->title('Document details updated successfully')
                    ->send();

                $this->redirect(static::getUrl([
                    'document' => $document->getPublicRouteKey(),
                ]), navigate: true);
            });
    }

    /**
     * Reuse the existing document-review action from the Documents page so
     * the View Document page does not duplicate its review process.
     */
    public function acceptDocumentAction(): Action
    {
        return app(\App\Filament\Pages\Document::class)->acceptDocumentAction();
    }

    public function rejectDocumentAction(): Action
    {
        return app(\App\Filament\Pages\Document::class)->rejectDocumentAction();
    }

    public function notifyDuplicateVersionUpload(): void
    {
        Notification::make()
            ->warning()
            ->title('Duplicate file not added')
            ->body('This file is already selected for upload or has already been uploaded for this document.')
            ->send();
    }

    public function addVersionAction(): Action
    {
        $isLocked = in_array(
            $this->documentRecord->status,
            ['pending', 'rejected'],
            true
        ) || $this->hasPendingRevision();

        $documentId = $this->documentRecord->document_id;
        $userId = auth()->id();
        $existingFileHashes = DocumentVersion::query()
            ->where(function ($query) use ($documentId, $userId): void {
                $query->where('document_id', $documentId);

                if ($userId !== null) {
                    $query->orWhere('user_id', $userId);
                }
            })
            ->whereNotNull('file_hash')
            ->pluck('file_hash')
            ->all();

        $duplicateFileGuard = <<<'JS'
const installDocumentVersionDuplicateGuard = (pond) => {
    if (! pond || pond.__documentVersionDuplicateGuardInstalled) {
        return;
    }

    pond.__documentVersionDuplicateGuardInstalled = true;

    const uploadedHashes = new Set(__EXISTING_FILE_HASHES__);
    const selectedHashes = new Map();
    const selectedFileSignatures = new Map();
    const livewire = $wire;

    const hashFile = async (file) => {
        const digest = await crypto.subtle.digest('SHA-256', await file.arrayBuffer());

        return Array.from(new Uint8Array(digest), (byte) => byte.toString(16).padStart(2, '0')).join('');
    };

    pond.setOptions({
        beforeAddFile: (fileItem) => {
            const file = fileItem.file;

            if (! (file instanceof File)) {
                return true;
            }

            // Reject a file re-selected in this dialog synchronously. Waiting
            // for its hash here makes FilePond briefly show a gray loading row.
            const signature = JSON.stringify([file.name, file.size, file.lastModified]);

            if (Array.from(selectedFileSignatures.values()).includes(signature)) {
                livewire.call('notifyDuplicateVersionUpload');

                return false;
            }

            selectedFileSignatures.set(fileItem.id, signature);

            return hashFile(file).then((hash) => {
                if (uploadedHashes.has(hash) || Array.from(selectedHashes.values()).includes(hash)) {
                    selectedFileSignatures.delete(fileItem.id);
                    livewire.call('notifyDuplicateVersionUpload');

                    return false;
                }

                selectedHashes.set(fileItem.id, hash);

                return true;
            }).catch(() => {
                // Keep the server-side hash validation as the fallback if browser hashing is unavailable.
                return true;
            });
        },
    });

    pond.on('removefile', (_error, fileItem) => {
        selectedHashes.delete(fileItem.id);
        selectedFileSignatures.delete(fileItem.id);
    });
};

installDocumentVersionDuplicateGuard(pond);
$watch('pond', installDocumentVersionDuplicateGuard);
JS;
        $duplicateFileGuard = str_replace(
            '__EXISTING_FILE_HASHES__',
            (string) Js::from($existingFileHashes),
            $duplicateFileGuard,
        );

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
            ->modalSubmitAction(fn (Action $action): Action => $action
                ->label('Upload')
                ->extraAttributes([
                    'style' => 'background-color: #6366F1; border-color: #6366F1; color: #ffffff;',
                ]))
            ->modalFooterActionsAlignment(Alignment::End)
            ->schema([
                FileUpload::make('file_path')
                    ->label('PDF or DOCX Files')
                    ->disk('local')
                    ->directory('documents/versions')
                    ->preserveFilenames()
                    ->multiple()
                    ->appendFiles()
                    ->nestedRecursiveRule(new UniqueDocumentVersionUpload(
                        $this->documentRecord->document_id,
                        auth()->id(),
                    ))
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    ])
                    ->extraAlpineAttributes(['x-init' => $duplicateFileGuard])
                    ->extraAttributes(['class' => 'document-version-upload-files'])
                    ->required(),
            ])
            ->action(function (array $data): void {
                $filePaths = array_values(array_filter(
                    (array) ($data['file_path'] ?? []),
                    fn (mixed $filePath): bool => is_string($filePath) && $filePath !== '',
                ));

                if (
                    in_array($this->documentRecord->status, ['pending', 'rejected'], true)
                    || $this->hasPendingRevision()
                ) {
                    foreach ($filePaths as $filePath) {
                        DocumentVersion::removeUnreferencedUpload($filePath);
                    }

                    Notification::make()
                        ->warning()
                        ->title('Version upload is disabled')
                        ->body('Review the pending revision before uploading another version.')
                        ->send();

                    return;
                }

                if ($filePaths === []) {
                    Notification::make()
                        ->danger()
                        ->title('No files selected')
                        ->body('Select at least one PDF or DOCX file to upload.')
                        ->send();

                    return;
                }

                $uploadedVersions = [];
                $duplicates = [];
                $unreadableFiles = [];

                foreach ($filePaths as $filePath) {
                    $fileName = basename($filePath);
                    $fileHash = DocumentVersion::hashForUpload($filePath);

                    if ($fileHash === null) {
                        DocumentVersion::removeUnreferencedUpload($filePath);
                        $unreadableFiles[] = $fileName;

                        continue;
                    }

                    $version = DB::transaction(function () use ($filePath, $fileHash): ?DocumentVersion {
                        $document = Document::query()
                            ->whereKey($this->documentRecord->document_id)
                            ->lockForUpdate()
                            ->firstOrFail();

                        if (DocumentVersion::existsForDocumentOrUserHash(
                            $document->document_id,
                            $fileHash,
                            auth()->id(),
                        )) {
                            return null;
                        }

                        $versionNumber = $this->getNextVersionNumber(
                            $document->versions()->get()
                        );

                        return DocumentVersion::create([
                            'document_id' => $document->document_id,
                            'user_id' => auth()->id(),
                            'version_number' => (string) $versionNumber,
                            'file_path' => $filePath,
                            'file_hash' => $fileHash,
                        ]);
                    });

                    if ($version === null) {
                        DocumentVersion::removeUnreferencedUpload($filePath);
                        $duplicates[] = $fileName;

                        continue;
                    }

                    $uploadedVersions[] = $version;

                    $this->logDocumentActivity(
                        'Version uploaded',
                        'Uploaded ' . basename($version->file_path) .
                        ' as version ' . $version->version_number . '.'
                    );
                }

                if ($uploadedVersions !== []) {
                    $this->documentRecord->load([
                        'notes.user',
                        'versions',
                        'latestVersion',
                        'activityLogs.user',
                    ]);
                }

                if ($uploadedVersions === []) {
                    $details = [];

                    if ($duplicates !== []) {
                        $details[] = 'Already uploaded: ' . implode(', ', $duplicates) . '.';
                    }

                    if ($unreadableFiles !== []) {
                        $details[] = 'Could not read: ' . implode(', ', $unreadableFiles) . '.';
                    }

                    Notification::make()
                        ->danger()
                        ->title('No files uploaded')
                        ->body(implode(' ', $details) ?: 'The selected files could not be uploaded.')
                        ->send();

                    return;
                }

                $details = [count($uploadedVersions) . ' file' . (count($uploadedVersions) === 1 ? '' : 's') . ' uploaded.'];

                if ($duplicates !== []) {
                    $details[] = 'Skipped duplicates: ' . implode(', ', $duplicates) . '.';
                }

                if ($unreadableFiles !== []) {
                    $details[] = 'Could not read: ' . implode(', ', $unreadableFiles) . '.';
                }

                Notification::make()
                    ->success()
                    ->title('Document versions uploaded')
                    ->body(implode(' ', $details))
                    ->send();
            });
    }

    public function selectVersion(int $versionId): void
    {
        $version = $this->documentRecord->versions()
            ->whereKey($versionId)
            ->firstOrFail();

        $this->selectedVersionId = $version->version_id;
        $this->isTransmittalSelected = false;
        $this->previewUrl = route('admin.document.version.preview', [
            'document' => $this->documentRecord->getPublicRouteKey(),
            'version' => $version->version_id,
        ]);
        $this->previewPageCount = $this->pageCountForVersion($version);
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

        $wasSelected = $this->selectedVersionId === $versionId;

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

        if ($wasSelected) {
            $this->selectedVersionId = null;
            $this->previewUrl = $this->generatePreview();
            $this->previewPageCount = $this->pageCountForVersion($this->documentRecord->latestVersion);
        }

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
        $this->isTransmittalSelected = false;
        $this->previewUrl = $this->generatePreview();
        $this->previewPageCount = $this->pageCountForVersion($this->documentRecord->latestVersion);
    }

    public function selectTransmittal(): void
    {
        $filePath = (string) $this->documentRecord->transmittal;

        abort_unless(filled($filePath), 404, 'No transmittal is available.');

        $this->selectedVersionId = null;
        $this->isTransmittalSelected = true;
        $this->previewUrl = route('admin.documents.transmittal.preview', [
            'document' => $this->documentRecord->getPublicRouteKey(),
        ]);
        $this->previewPageCount = $this->pageCountForStoredFile($filePath);
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

    public function shouldShowActivity(ActivityLog $log): bool
    {
        if (in_array($log->action_type, [
            'Document viewed',
            'Version viewed',
            'Current document viewed',
            'Transmittal viewed',
            'Document downloaded',
            'Rejection reason viewed',
            'Message opened',
        ], true)) {
            return false;
        }

        if (in_array($log->action_type, ['Document details updated', 'Document updated'], true)) {
            $details = strtolower(trim((string) $log->action_details));
            $oldValues = json_decode((string) $log->old_value, true);
            $newValues = json_decode((string) $log->new_value, true);

            if (is_array($oldValues) && is_array($newValues)) {
                return $this->documentDetailsChanges($log) !== []
                    || str_contains($details, 'document file');
            }

            return $details !== '' && $details !== 'no fields changed';
        }

        return true;
    }

    public function activityDescription(ActivityLog $log): string
    {
        if (in_array($log->action_type, ['Document details updated', 'Document updated'], true)) {
            $changes = $this->documentDetailsChanges($log);

            if ($changes !== []) {
                return 'updated document details';
            }
        }

        if (in_array($log->action_type, ['Note added', 'Note updated', 'Note deleted'], true)) {
            $oldNote = trim((string) $log->old_value);
            $newNote = trim((string) $log->new_value);

            if ($log->action_type === 'Note added' && $newNote !== '') {
                return 'added a note: ' . $newNote;
            }

            if ($log->action_type === 'Note updated' && $oldNote !== '' && $newNote !== '') {
                return 'updated a note';
            }

            if ($log->action_type === 'Note updated' && $newNote !== '') {
                return 'updated a note to: ' . $newNote;
            }

            if ($log->action_type === 'Note deleted' && $oldNote !== '') {
                return 'deleted a note: ' . $oldNote;
            }
        }

        $description = trim((string) ($log->action_details ?: $log->action_type ?: 'Document updated'));

        return $description === '' ? 'updated the document.' : lcfirst($description);
    }

    public function activityActorFirstName(ActivityLog $log): string
    {
        $name = preg_replace('/\s+/u', ' ', trim((string) $log->user?->name)) ?? '';

        return explode(' ', $name, 2)[0] ?: 'Someone';
    }

    /** @return list<array{label: string, before: string, after: string}> */
    public function activityChangeRows(ActivityLog $log): array
    {
        if (in_array($log->action_type, ['Document details updated', 'Document updated'], true)) {
            $oldValues = json_decode((string) $log->old_value, true);
            $newValues = json_decode((string) $log->new_value, true);

            if (is_array($oldValues) && is_array($newValues)) {
                return $this->formatDocumentDetailsChangeRows($oldValues, $newValues);
            }
        }

        if ($log->action_type === 'Note updated') {
            $oldNote = trim((string) $log->old_value);
            $newNote = trim((string) $log->new_value);

            if ($oldNote !== '' && $newNote !== '') {
                return [[
                    'label' => 'Note',
                    'before' => $oldNote,
                    'after' => $newNote,
                ]];
            }
        }

        return [];
    }

    /** @return list<string> */
    private function documentDetailsChanges(ActivityLog $log): array
    {
        $oldValues = json_decode((string) $log->old_value, true);
        $newValues = json_decode((string) $log->new_value, true);

        if (! is_array($oldValues) || ! is_array($newValues)) {
            return [];
        }

        return $this->formatDocumentDetailsChanges($oldValues, $newValues);
    }

    /** @return list<string> */
    private function formatDocumentDetailsChanges(array $oldValues, array $newValues): array
    {
        return array_map(
            fn (array $change): string => $change['label'] . ': ' . $change['before'] . ' → ' . $change['after'],
            $this->formatDocumentDetailsChangeRows($oldValues, $newValues),
        );
    }

    /** @return list<array{label: string, before: string, after: string}> */
    private function formatDocumentDetailsChangeRows(array $oldValues, array $newValues): array
    {
        $labels = [
            'lao_number' => 'LAO Number',
            'document_type' => 'Document Type',
            'office_unit' => 'Office / Unit',
            'particulars' => 'Particulars',
            'description' => 'Description',
            'action_type' => 'Action Taken',
            'deadline' => 'Deadline',
            'outgoing_date' => 'Outgoing Date',
            'sent_date' => 'Sent Date',
            'sent_to' => 'Sent To',
            'returned_from' => 'Returned From',
            'date_returned' => 'Date Returned',
            'status' => 'Status',
            'file_path' => 'Document File',
        ];
        $dateFields = ['deadline', 'outgoing_date', 'sent_date', 'date_returned'];
        $changes = [];

        foreach (array_unique([...array_keys($oldValues), ...array_keys($newValues)]) as $field) {
            $oldValue = $oldValues[$field] ?? null;
            $newValue = $newValues[$field] ?? null;
            $oldDisplay = $this->formatActivityValue($field, $oldValue, $dateFields);
            $newDisplay = $this->formatActivityValue($field, $newValue, $dateFields);

            if ($oldDisplay === $newDisplay) {
                continue;
            }

            $changes[] = [
                'label' => $labels[$field] ?? str($field)->replace('_', ' ')->ucfirst()->toString(),
                'before' => $oldDisplay,
                'after' => $newDisplay,
            ];
        }

        return $changes;
    }

    private function formatActivityValue(string $field, mixed $value, array $dateFields): string
    {
        if ($value === null || $value === '') {
            return 'Not set';
        }

        if (in_array($field, $dateFields, true)) {
            try {
                return \Illuminate\Support\Carbon::parse((string) $value)
                    ->setTimezone(config('app.timezone'))
                    ->format('F j, Y');
            } catch (\Throwable) {
                // Keep the stored value visible if an old activity row has a non-date value.
            }
        }

        if ($field === 'status') {
            return match ((string) $value) {
                'in_progress' => 'Incoming',
                default => str((string) $value)->replace('_', ' ')->title()->toString(),
            };
        }

        return is_scalar($value) ? (string) $value : (string) json_encode($value);
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

    public function deleteNoteAction(): Action
    {
        return Action::make('deleteNote')
            ->label('Delete')
            ->color('danger')
            ->extraAttributes([
                'class' => 'w-full justify-start rounded-md px-3 py-2 text-xs !bg-red-50 !text-red-700 hover:!bg-red-100',
            ])
            ->requiresConfirmation()
            ->modalHeading('Delete Note')
            ->modalDescription('Are you sure you want to delete this note?')
            ->modalSubmitActionLabel('Delete')
            ->action(function (array $arguments): void {
                $note = Note::where(
                    'document_id',
                    $this->documentRecord->document_id
                )->findOrFail($arguments['note']);

                $noteText = (string) $note->note;
                $note->delete();

                $this->logDocumentActivity(
                    'Note deleted',
                    'Deleted a document note.',
                    $noteText
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

        return route('admin.documents.preview', ['document' => $this->documentRecord->getPublicRouteKey()]);
    }

    private function pageCountForVersion(?DocumentVersion $version): ?int
    {
        if (! $version || strtolower(pathinfo((string) $version->file_path, PATHINFO_EXTENSION)) !== 'pdf') {
            return null;
        }

        $disk = $version->storageDisk();

        if (! $disk->exists($version->file_path)) {
            return null;
        }

        return app(\App\Services\DocumentPreviewService::class)
            ->pageCount($disk->path($version->file_path));
    }

    private function pageCountForStoredFile(string $filePath): ?int
    {
        if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) !== 'pdf') {
            return null;
        }

        foreach (['local', 'public'] as $diskName) {
            $disk = Storage::disk($diskName);

            if ($disk->exists($filePath)) {
                return app(\App\Services\DocumentPreviewService::class)
                    ->pageCount($disk->path($filePath));
            }
        }

        return null;
    }
}
