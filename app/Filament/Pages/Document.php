<?php

namespace App\Filament\Pages;

use App\Models\Document as DocumentModel;
use App\Models\DocumentVersion;
use App\Models\RejectedDocument;
use App\Notifications\DocumentRejectedNotification;
use App\Notifications\DocumentAcceptedNotification;
use App\Notifications\DocumentCompletedNotification;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use App\Models\ActionType;
use App\Models\DocumentType;
use App\Models\ActivityLog;
use App\Models\OfficeUnit;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QROutputInterface;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\DocumentDownloadService;
use Illuminate\Support\Facades\DB;
use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Schemas\Components\Grid;
// use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class Document extends Page implements HasTable
{
    use InteractsWithTable;
    // use HasPageShield;

    protected static ?string $slug = 'incoming';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Documents';

    protected static ?string $title = 'Documents';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox';

    protected string $view = 'filament.pages.document-filament';

    public string $search = '';

    public string $typeFilter = '';

    public string $dateFilter = '';

    public string $activeSection = 'incoming';

    public ?int $highlightedDocumentId = null;

    public bool $showAcceptedModal = false;

    public ?string $acceptedDocumentUploader = null;

    public ?int $qrCodeDocumentId = null;

    public ?string $qrCodeSvg = null;

    public ?string $qrCodeUrl = null;

    public function mount(): void
    {
        $section = request()->query('section', 'incoming');
        $document = request()->query('document');

        $this->activeSection = in_array($section, [
            'pending',
            'incoming',
            'outgoing',
            'completed',
            'rejected',
            'archived',
        ], true) ? $section : 'incoming';

        $this->highlightedDocumentId = is_numeric($document) && (int) $document > 0
            ? (int) $document
            : null;
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function getStats(): array
    {
        return [
            'total' => DocumentModel::count(),

            'pending' => DocumentModel::where('status', 'pending')
                ->count(),

            'active' => DocumentModel::where('status', 'in_progress')
                ->count(),

            'completed' => DocumentModel::where('status', 'completed')
                ->count(),
        ];
    }

    public function getStatusCounts(): array
    {
        $counts = DocumentModel::query()
            ->select('status')
            ->selectRaw('COUNT(*) as count')
            ->whereIn('status', [
                'pending',
                'in_progress',
                'outgoing',
                'completed',
                'rejected',
                'archived',
            ])
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            'pending' => (int) ($counts['pending'] ?? 0),
            'incoming' => (int) ($counts['in_progress'] ?? 0),
            'outgoing' => (int) ($counts['outgoing'] ?? 0),
            'completed' => (int) ($counts['completed'] ?? 0),
            'rejected' => (int) ($counts['rejected'] ?? 0),
            'archived' => (int) ($counts['archived'] ?? 0),
        ];
    }

    public function acceptDocument(int $documentId): void
    {
        $result = DB::transaction(function () use ($documentId): array {

            $document = DocumentModel::with('user')
                ->lockForUpdate()
                ->findOrFail($documentId);

            if ($document->status !== 'pending') {
                return [
                    'document' => $document,
                    'accepted' => false,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | 1. Generate LAO number
            |--------------------------------------------------------------------------
            */

            /*
            |--------------------------------------------------------------------------
            | 2. Accept document
            |--------------------------------------------------------------------------
            */

            $document->update([
                'lao_number' => DocumentModel::generateLaoNumber(),
                'status' => 'in_progress',
                'deadline' => DocumentModel::deadlineForType($document->document_type),
            ]);

            $this->recordDocumentActivity(
                $document->document_id,
                'Document accepted',
                'Accepted the document and moved it to Incoming.'
            );
            /*
            |--------------------------------------------------------------------------
            | 3. Create conversation
            |--------------------------------------------------------------------------
            */

            $conversation = Conversation::firstOrCreate(
                [
                    'document_id' => $document->document_id,
                ],
                [
                    'created_by' => $document->user_id,
                    'assigned_to' => null,
                    'status' => 'active',
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | 4. Add client as participant
            |--------------------------------------------------------------------------
            */

            if ($document->user_id) {
                $conversation->participants()->syncWithoutDetaching([
                    $document->user_id => [
                        'joined_at' => now(),
                    ],
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 5. Add staff participants
            |--------------------------------------------------------------------------
            */

            $staffIds = User::role(User::ADMIN_ROLES)
                ->pluck('id');

            foreach ($staffIds as $staffId) {
                $conversation->participants()->syncWithoutDetaching([
                    $staffId => [
                        'joined_at' => now(),
                    ],
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 6. Create initial message
            |--------------------------------------------------------------------------
            */

            if (! $conversation->messages()->exists()) {
                Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => auth()->id(),
                    'body' => 'Your document has been accepted by the Legal Affairs Office and is now being processed.',
                ]);
            }

            $conversation->touch();

            return [
                'document' => $document,
                'accepted' => true,
            ];
        });

        $document = $result['document'];

        /*
        |--------------------------------------------------------------------------
        | 7. Send acceptance notification
        |--------------------------------------------------------------------------
        */

        if ($result['accepted'] && $document->user) {
            Notification::make()
                ->title('Document accepted — QR code ready')
                ->body('Your document has been accepted. Open your QR code below and scan it to track the document status. LAO Number: ' . $document->lao_number)
                ->success()
                ->actions([
                    Action::make('viewDocumentQrCode')
                        ->label('View QR code')
                        ->icon('heroicon-o-qr-code')
                        ->url(URL::signedRoute('documents.qr', [
                            'document' => $document->document_id,
                        ]))
                        ->openUrlInNewTab()
                        ->button(),
                ])
                ->sendToDatabase($document->user);

            try {
                $document->user->notify(
                    new DocumentAcceptedNotification($document)
                );
            } catch (TransportExceptionInterface $exception) {
                report($exception);

                Notification::make()
                    ->title('Document accepted, but email failed')
                    ->body(
                        'Assigned LAO Number: ' . $document->lao_number .
                        '. The email notification could not be sent. Please contact your administrator to check the mail server connection.'
                    )
                    ->warning()
                    ->send();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 8. Redirect
        |--------------------------------------------------------------------------
        */

        $this->redirect(
            self::getUrl(['section' => 'incoming'])
        );
    }

    protected function getDocumentTableQuery(): Builder
    {
        $status = match ($this->activeSection) {
            'pending' => 'pending',
            'incoming' => 'in_progress',
            'outgoing' => 'outgoing',
            'rejected' => 'rejected',
            'completed' => 'completed',
            'archived' => 'archived',
            default => 'in_progress',
        };

        return DocumentModel::query()
            ->with(['user', 'rejections', 'latestVersion'])
            ->where('status', $status)
            ->when(trim($this->search) !== '', function (Builder $query): void {
                $search = '%' . trim($this->search) . '%';

                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('lao_number', 'like', $search)
                        ->orWhere('office_unit', 'like', $search)
                        ->orWhere('particulars', 'like', $search);
                });
            })
            ->when($this->typeFilter !== '', function (Builder $query): void {
                $query->where('document_type', $this->typeFilter);
            })
            ->when($this->dateFilter !== '', function (Builder $query): void {
                $query->whereDate('created_at', $this->dateFilter);
            })
            ->latest('created_at');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->getDocumentTableQuery())
            ->columns($this->getDocumentTableColumns())
            ->recordActions($this->getDocumentTableActions())
            ->recordActionsColumnLabel('ACTION')
            ->recordActionsAlignment('start')
            ->recordUrl(fn (DocumentModel $record): string => ViewDocument::getUrl([
                'document' => $record->document_id,
            ]))
            ->recordClasses(
                fn (DocumentModel $record): string => $this->highlightedDocumentId !== null &&
                    (int) $record->document_id === $this->highlightedDocumentId
                    ? 'document-highlighted'
                    : ''
            )
            ->groups([
                Group::make('created_at')
                    ->date()
                    ->label('Uploaded')
                    ->titlePrefixedWithLabel(false)
                    ->getTitleFromRecordUsing(
                        fn (DocumentModel $record): \Illuminate\Contracts\Support\Htmlable =>
                            new \Illuminate\Support\HtmlString('Uploaded ' . $record->created_at->format('F d, Y'))
                    ),
            ])
            ->defaultGroup('created_at')
            ->groupingSettingsHidden()
            ->defaultSort('created_at', 'desc')
            ->paginationPageOptions([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->searchable(false)
            ->striped()
            ->extraAttributes([
                'class' => 'admin-documents-filament-table',
            ]);
    }

    protected function getDocumentTableColumns(): array
    {
        $columns = [
            ViewColumn::make('document_details')
                ->label('DOCUMENT')
                ->view('filament.tables.columns.document-details')
                ->width('20rem')
                ->extraHeaderAttributes(['class' => 'min-w-[240px]']),
        ];

        if ($this->activeSection === 'incoming') {
            $columns[] = ViewColumn::make('subjects')
                ->label('DETAILS')
                ->view('filament.tables.columns.subjects')
                ->alignCenter()
                ->width('18rem')
                ->extraHeaderAttributes(['class' => 'min-w-[200px]'])
                ->extraCellAttributes(['class' => 'align-middle']);
        }

        $columns[] =
            ViewColumn::make('document_type')
                ->label('DOCUMENT TYPE')
                ->view('filament.tables.columns.document-type')
                ->alignCenter()
                ->width('9rem')
                ->extraHeaderAttributes(['class' => 'min-w-[140px]']);

        if ($this->activeSection === 'pending') {
            $columns[] = ViewColumn::make('uploaded_by')
                ->label('UPLOADED BY')
                ->view('filament.tables.columns.uploaded-by')
                ->extraHeaderAttributes(['class' => 'min-w-[180px]']);
        } elseif ($this->activeSection === 'outgoing') {
            $columns[] = TextColumn::make('outgoing_date')
                ->label('OUTGOING DATE')
                ->date('F d, Y')
                ->placeholder('No outgoing date')
                ->size('xs')
                ->extraCellAttributes(['class' => 'outgoing-date-cell'])
                ->alignCenter();

            $columns[] = ViewColumn::make('sent_details')
                ->label('SENT')
                ->view('filament.tables.columns.sent-details')
                ->alignCenter();

            $columns[] = ViewColumn::make('returned_details')
                ->label('RETURNED')
                ->view('filament.tables.columns.returned-details')
                ->alignCenter();
        } elseif ($this->activeSection === 'completed') {
            $columns[] = TextColumn::make('updated_at')
                ->label('LAST UPDATE')
                ->date('F d, Y')
                ->placeholder('Unknown date')
                ->alignCenter();
        } elseif ($this->activeSection === 'rejected') {
            $columns[] = TextColumn::make('rejection_reason')
                ->label('REJECTION REASON')
                ->state(function (DocumentModel $record): string {
                    return $record->rejections
                        ->sortByDesc('created_at')
                        ->first()?->reason ?? 'No reason recorded';
                })
                ->color('danger')
                ->action(function (DocumentModel $record): void {
                    $rejection = $record->rejections
                        ->sortByDesc('created_at')
                        ->first();

                    if ($rejection) {
                        $this->mountAction('viewRejectionReason', [
                            'rejection' => $rejection->rejected_id,
                        ]);
                    }
                })
                ->alignCenter();
        } elseif ($this->activeSection === 'archived') {
            $columns[] = TextColumn::make('archived_at')
                ->label('ARCHIVED AT')
                ->dateTime('F d, Y h:i A')
                ->placeholder('Unknown date')
                ->alignCenter();
        } else {
            $columns[] = ViewColumn::make('action_type')
                ->label('ACTION TAKEN')
                ->view('filament.tables.columns.action-type')
                ->alignCenter();

            $columns[] = ViewColumn::make('deadline_details')
                ->label('DEADLINE')
                ->view('filament.tables.columns.deadline')
                ->alignCenter();
        }

        return $columns;
    }

    protected function getDocumentTableActions(): array
    {
        $menuActions = [
            Action::make('viewDocument')
                ->label('View')
                ->icon('heroicon-o-eye')
                ->url(fn (DocumentModel $record): string => ViewDocument::getUrl([
                    'document' => $record->document_id,
                ])),
            Action::make('downloadDocument')
                ->label('Download')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn (DocumentModel $record): string => route('admin.documents.download', [
                    'document' => $record->document_id,
                ]))
                ->disabled(fn (DocumentModel $record): bool => blank($record->latestVersion?->file_path))
                ->tooltip(fn (DocumentModel $record): string =>
                    filled($record->latestVersion?->file_path)
                        ? 'Download'
                        : 'No file attached'
                ),
            Action::make('documentQrCode')
                ->label('QR Code')
                ->icon('heroicon-o-qr-code')
                ->action(fn (DocumentModel $record) => $this->openQrCode($record->document_id)),
        ];

        if (in_array($this->activeSection, ['incoming', 'outgoing', 'completed'], true)) {
            $menuActions[] = $this->archiveDocumentAction();
        }

        if (in_array($this->activeSection, ['incoming', 'outgoing'], true)) {
            $menuActions[] = $this->editDocumentAction(true)
                ->label('Edit')
                ->icon('heroicon-o-pencil-square');

            $menuActions[] = Action::make('messageDocumentTable')
                ->label('Message')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('gray')
                ->action(fn (DocumentModel $record) => $this->messageDocument($record->document_id));
        }

        $options = ActionGroup::make($menuActions)
            ->icon('heroicon-m-ellipsis-vertical')
            ->tooltip('More options')
            ->color('gray');

        $actions = [];

        if ($this->activeSection === 'pending') {
            return [
                ...$actions,
                $this->acceptDocumentAction()->button(),
                $this->rejectDocumentAction()->button(),
            ];
        }

        if (in_array($this->activeSection, ['completed', 'rejected'], true)) {
            return [
                ...$actions,
                $this->returnDocumentAction()->button(),
                $options,
            ];
        }

        if ($this->activeSection === 'archived') {
            return [
                $this->returnArchivedDocumentAction()->button(),
                $options,
            ];
        }

        $actions[] = $this->activeSection === 'outgoing'
            ? $this->completeDocumentAction()->button()
            : $this->markAsOutgoingAction()->button();
        $actions[] = $options;

        return $actions;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedDateFilter(): void
    {
        $this->resetPage();
    }

    public function openQrCode(int $documentId): void
    {
        try {
            DocumentModel::findOrFail($documentId);

            $url = URL::signedRoute('documents.public-status', [
                'document' => $documentId,
            ]);

            $this->qrCodeDocumentId = $documentId;
            $this->qrCodeUrl = $url;
            $this->qrCodeSvg = (new QRCode(new QROptions([
                'outputType' => QROutputInterface::MARKUP_SVG,
                'outputBase64' => false,
                'scale' => 5,
            ])))->render($url);
        } catch (\Throwable $exception) {
            report($exception);

            $this->closeQrCode();

            Notification::make()
                ->danger()
                ->title('QR code could not be generated')
                ->body('Please try again.')
                ->send();
        }
    }

    public function closeQrCode(): void
    {
        $this->qrCodeDocumentId = null;
        $this->qrCodeSvg = null;
        $this->qrCodeUrl = null;
    }

    public function addDocumentAction(): Action
    {
        return Action::make('addDocument')
            ->label('Add Document')
            ->icon('heroicon-o-plus')
            ->size('xs')
            ->modalHeading('Add New Document')
            ->extraAttributes([
                'class' => 'add-document-button',
            ])

            ->schema([
                TextInput::make('lao_number')
                    ->label('LAO Number')
                    ->default(fn (): string => DocumentModel::generateLaoNumber())
                    ->readOnly()
                    ->helperText('Automatically assigned from the current LAO sequence.'),

                TextInput::make('document_name')
                    ->label('Document Name')
                    ->placeholder('e.g. BSIT OJT Memo')
                    ->readOnly()
                    ->maxLength(255),

                Grid::make(2)
                    ->schema([
                        TextInput::make('document_type')
                            ->label('Document Type')
                            ->placeholder('Select document type')
                            ->datalist(fn () => DocumentType::query()->orderedForChoices()->pluck('type_name'))
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set): void {
                                $deadline = DocumentModel::deadlineForType($state);

                                if (filled($deadline)) {
                                    $set('deadline', $deadline);
                                }
                            })
                            ->required(),

                        DatePicker::make('deadline')
                            ->label('Deadline')
                            ->default(now()->toDateString()),
                    ]),

                TextInput::make('action_type')
                ->label('Action Taken')
                ->datalist(fn () => ActionType::query()->orderBy('action_name')->pluck('action_name'))
                ->nullable(),
                    
                TextInput::make('office_unit')
                    ->label('Office / Unit')
                    ->datalist(fn () => OfficeUnit::query()->orderBy('name')->pluck('name'))
                    ->required(),

                Textarea::make('particulars')
                    ->label('Particulars')
                    ->required(),

                FileUpload::make('file_path')
                    ->label('Document File')
                    ->disk('local')
                    ->directory('documents')
                    ->maxSize(5120)
                    ->helperText('Maximum file size: 5 MB.')
                    ->preserveFilenames()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set): void {
                        $set('document_name', filled($state) ? basename((string) $state) : null);
                    }),
            ])
            ->action(function (array $data) {
                $filePath = $data['file_path'] ?? null;
                unset($data['file_path']);

                $data['user_id'] = auth()->id();
                $data['deadline'] ??= DocumentModel::deadlineForType($data['document_type'] ?? null);
                $data['document_name'] = filled($filePath)
                    ? basename((string) $filePath)
                    : ($data['document_name'] ?? null);

                $document = DB::transaction(function () use ($data, $filePath): DocumentModel {
                    // Generate again at save time so the number is always the
                    // latest available one, even if the form stayed open.
                    $data['lao_number'] = DocumentModel::generateLaoNumber();
                    $document = DocumentModel::create($data);

                    if (filled($filePath)) {
                        DocumentVersion::create([
                            'document_id' => $document->document_id,
                            'user_id' => auth()->id(),
                            'version_number' => '1',
                            'file_path' => $filePath,
                        ]);
                    }

                    return $document;
                });

                $this->recordDocumentActivity(
                    $document->document_id,
                    'Document created',
                    'Created a new document.'
                );
            });
    }

    protected function resolveDocumentActionRecord(array $arguments, ?DocumentModel $record = null): DocumentModel
    {
        return $record ?? DocumentModel::findOrFail($arguments['document'] ?? null);
    }



    public function editDocumentAction(bool $asMenuItem = false): Action
    {
        return Action::make('editDocument')
            ->label($asMenuItem ? 'Edit' : '')
            ->icon('heroicon-o-pencil-square')
            ->tooltip('Edit')
            ->extraAttributes($asMenuItem ? [] : [
                'class' => 'edit-document-button',
            ])

            ->schema(function (array $arguments, ?DocumentModel $record = null): array {
                $document = $record ?? DocumentModel::find($arguments['document'] ?? null);

                if ($document?->status === 'outgoing') {
                    return [
                        TextInput::make('document_name')
                            ->label('Document Name')
                            ->maxLength(255),

                        Grid::make(2)
                        ->schema([
                            TextInput::make('document_type')
                                ->label('Document Type')
                                ->placeholder('Select document type')
                                ->datalist(fn () => DocumentType::query()->orderedForChoices()->pluck('type_name'))
                                ->live()
                                ->afterStateUpdated(function ($state, Set $set): void {
                                    $deadline = DocumentModel::deadlineForType($state);

                                    if (filled($deadline)) {
                                        $set('deadline', $deadline);
                                    }
                                })
                                ->required(),

                            DatePicker::make('deadline')
                                ->label('Deadline')
                                ->default(now()->toDateString())
                                ->helperText('Preselected to today; calculated from the document type when configured.'),
                        ]),



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

                return [
                TextInput::make('lao_number')
                    ->label('LAO Number')
                    ->required(),

                TextInput::make('document_name')
                    ->label('Document Name')
                    ->maxLength(255),

                TextInput::make('document_type')
                    ->label('Document Type')
                    ->placeholder('Select document type')
                    ->datalist(fn () => DocumentType::query()->orderedForChoices()->pluck('type_name'))
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set): void {
                        $deadline = DocumentModel::deadlineForType($state);

                        if (filled($deadline)) {
                            $set('deadline', $deadline);
                        }
                    })
                    ->required(),

                TextInput::make('action_type')
                ->label('Action Taken')
                ->placeholder('Select action')
                ->datalist(fn () => ActionType::query()->orderBy('action_name')->pluck('action_name'))
                ->nullable(),

                TextInput::make('office_unit')
                    ->label('Office / Unit')
                    ->datalist(fn () => OfficeUnit::query()->orderBy('name')->pluck('name'))
                    ->required(),

                Textarea::make('particulars')
                    ->label('Particulars')
                    ->required(),

                DatePicker::make('deadline')
                    ->label('Deadline')
                    ->default(now()->toDateString())
                    ->helperText('Preselected to today; calculated from the document type when configured.'),

                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'returned' => 'Returned',
                        'outgoing' => 'Outgoing',
                    ])
                    ->required(),

                FileUpload::make('file_path')
                    ->label('Upload New Document Version')
                    ->disk('local')
                    ->directory('documents/versions')
                    ->preserveFilenames(),
                ];
            })
            ->fillForm(function (array $arguments, ?DocumentModel $record = null): array {
                $document = $this->resolveDocumentActionRecord($arguments, $record);

                return [
                    'lao_number' => $document->lao_number,
                    'document_name' => $document->document_name
                        ?: ($document->latestVersion?->file_path
                            ? basename($document->latestVersion->file_path)
                            : null),
                    'document_type' => $document->document_type,

                    // Important: preload current Action Taken
                    'action_type' => $document->action_type,

                    'office_unit' => $document->office_unit,
                    'particulars' => $document->particulars,
                    'deadline' => $document->deadline ?? now()->toDateString(),
                    'status' => $document->status,
                    'outgoing_date' => $document->outgoing_date,
                    'sent_to' => $document->sent_to,
                    'sent_date' => $document->sent_date,
                    'returned_from' => $document->returned_from,
                    'date_returned' => $document->date_returned,
                ];
            })
            ->action(function (array $data, array $arguments, ?DocumentModel $record = null): void {
                $document = $this->resolveDocumentActionRecord($arguments, $record);
                $filePath = $data['file_path'] ?? null;
                unset($data['file_path']);
                $oldValues = $document->only(array_keys($data));

                $document->fill($data);

                $fieldLabels = [
                    'lao_number' => 'LAO Number',
                    'document_name' => 'Document Name',
                    'document_type' => 'Document Type',
                    'action_type' => 'Action Taken',
                    'office_unit' => 'Office / Unit',
                    'particulars' => 'Particulars',
                    'deadline' => 'Deadline',
                    'status' => 'Status',
                    'outgoing_date' => 'Outgoing Date',
                    'sent_to' => 'Sent To',
                    'sent_date' => 'Sent Date',
                    'returned_from' => 'Returned From',
                    'date_returned' => 'Returned Date',
                ];

                $updatedFields = collect(array_keys($document->getDirty()))
                    ->map(fn (string $field): string => $fieldLabels[$field] ?? $field)
                    ->values()
                    ->all();

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

                $this->recordDocumentActivity(
                    $document->document_id,
                    'Document updated',
                    $updatedSummary,
                    (string) json_encode($oldValues),
                    (string) json_encode($document->only(array_keys($data)))
                );

                Notification::make()
                    ->success()
                    ->title('Document updated successfully')
                    ->body(
                        $updatedSummary .
                        '. Updated on: ' . now()->format('F d, Y \a\t h:i A') . '.'
                    )
                    ->send();
            });
    }

    public function downloadDocument(int $documentId): BinaryFileResponse
    {
        $document = DocumentModel::with(['user', 'latestVersion'])->findOrFail($documentId);
        $version = $document->latestVersion;

        abort_unless(
            $version?->file_path &&
            $version->storageDisk()->exists($version->file_path),
            404
        );

        $disk = $version->storageDisk();

        $this->recordDocumentActivity(
            $document->document_id,
            'Document downloaded',
            'Downloaded ' . basename((string) $version->file_path) . '.'
        );

        return app(DocumentDownloadService::class)->download($document, $version);
    }

    protected function getNextVersionNumber(DocumentModel $document): int
    {
        $highestVersion = $document->versions()
            ->pluck('version_number')
            ->map(function ($versionNumber): int {
                preg_match('/(\d+)\s*$/', (string) $versionNumber, $matches);

                return (int) ($matches[1] ?? 0);
            })
            ->max() ?? 0;

        return max(1, $highestVersion + 1);
    }

    public function redirectToIncoming(): void
    {
        $this->redirect(self::getUrl(['section' => 'incoming']));
    }

    public function acceptDocumentAction(): Action
    {
        return Action::make('acceptDocument')
            ->label('Accept')
            ->color('success')
            ->size('xs')
            ->modalHeading('Accept Document')
            ->modalDescription(function (array $arguments, ?DocumentModel $record = null): string {
                $document = $record ?? DocumentModel::with('user')->find($arguments['document'] ?? null);
                $uploader = $document?->user?->name ?? 'Unknown user';

                return "Are you sure you want to accept this document uploaded by {$uploader}? It will be moved to the Incoming table.";
            })
            ->modalContent(fn (DocumentModel $record) => view(
                'filament.actions.review-document',
                ['document' => $record]
            ))
            ->modalIcon('heroicon-o-check-circle')
            ->modalIconColor('success')
            ->modalAlignment(Alignment::Center)
            ->modalFooterActionsAlignment(Alignment::Center)
            ->modalSubmitActionLabel('Accept document')
            ->modalCancelActionLabel('Cancel')
            ->extraAttributes([
                'class' => 'inline-flex h-9 items-center justify-center rounded-md bg-green-600 px-3 text-xs font-semibold text-white transition hover:bg-green-700',
            ])
            ->action(function (array $arguments, ?DocumentModel $record = null): void {
                $document = $this->resolveDocumentActionRecord($arguments, $record);

                $this->acceptDocument($document->document_id);
            });
    }

    public function markAsOutgoing(int $documentId, string $sentDate, string $sentTo): void
    {
        $document = DocumentModel::findOrFail($documentId);

        $document->update([
            'status' => 'outgoing',
            'sent_date' => $sentDate,
            'sent_to' => $sentTo,
        ]);

        $this->recordDocumentActivity(
            $document->document_id,
            'Document moved to outgoing',
            'Sent to ' . $sentTo . ' on ' . $sentDate . '.'
        );

        Notification::make()
            ->success()
            ->title('Document added to Outgoing')
            ->body('The document was successfully added to the Outgoing table.')
            ->send();

        $this->redirect(self::getUrl(['section' => 'outgoing']));
    }

    public function markAsOutgoingAction(): Action
    {
        return Action::make('markAsOutgoing')
            ->label('Outgoing')
            ->icon('heroicon-m-arrow-right')
            ->color('gray')
            ->modalHeading('Add Document to Outgoing')
            ->modalDescription('Provide the destination and sent date for this document.')
            ->modalAlignment(Alignment::Center)
            ->modalFooterActionsAlignment(Alignment::Center)
            ->modalSubmitActionLabel('Add to outgoing')
            ->modalCancelActionLabel('Cancel')
            ->extraAttributes([
                'class' => 'outgoing-document-button',
            ])
            ->schema([
                TextInput::make('sent_to')
                    ->label('Sent To')
                    ->required()
                    ->maxLength(255),

                DatePicker::make('sent_date')
                    ->label('Sent Date')
                    ->default(now())
                    ->required(),
            ])
            ->action(function (array $data, array $arguments, ?DocumentModel $record = null): void {
                $document = $this->resolveDocumentActionRecord($arguments, $record);

                $this->markAsOutgoing(
                    $document->document_id,
                    $data['sent_date'],
                    $data['sent_to'],
                );
            });
    }

    public function rejectDocumentAction(): Action
    {
        return Action::make('rejectDocument')
            ->label('Reject')
            ->color('danger')
            ->size('xs')
            ->modalHeading('Reject Document')
            ->modalIcon('heroicon-o-x-circle')
            ->modalIconColor('danger')
            ->modalDescription('Please provide a reason for rejecting this document.')
            ->modalAlignment(Alignment::Center)
            ->modalFooterActionsAlignment(Alignment::Center)
            ->modalSubmitActionLabel('Reject document')
            ->modalCancelActionLabel('Cancel')
            ->extraAttributes([
                'class' => 'inline-flex h-9 items-center justify-center rounded-md bg-red-600 px-3 text-xs font-semibold text-white transition hover:bg-red-700',
            ])
            ->schema([
                Select::make('reason')
                    ->label('Rejection Reason')
                    ->placeholder('Select a reason')
                    ->options([
                        'Incomplete or missing information' => 'Incomplete or missing information',
                        'Incorrect document type' => 'Incorrect document type',
                        'Missing signature or approval' => 'Missing signature or approval',
                        'Duplicate document' => 'Duplicate document',
                        'Incorrect recipient or office' => 'Incorrect recipient or office',
                        'Unreadable or corrupted file' => 'Unreadable or corrupted file',
                        'other' => 'Other',
                    ])
                    ->live()
                    ->required(),

                Textarea::make('custom_reason')
                    ->label('Specify Other Reason')
                    ->placeholder('Type the reason for rejecting this document')
                    ->visible(fn (Get $get): bool => $get('reason') === 'other')
                    ->required(fn (Get $get): bool => $get('reason') === 'other')
                    ->rows(4)
                    ->maxLength(5000),
            ])
            ->action(function (array $data, array $arguments, ?DocumentModel $record = null): void {
                $reason = $data['reason'] === 'other'
                    ? trim((string) ($data['custom_reason'] ?? ''))
                    : $data['reason'];

                $document = $this->resolveDocumentActionRecord($arguments, $record);

                $this->rejectDocument(
                    $document->document_id,
                    $reason,
                );
            });
    }

    public function rejectDocument(int $documentId, string $reason): void
    {
        $document = DocumentModel::with('user')->findOrFail($documentId);

        $rejection = DB::transaction(function () use ($document, $reason): RejectedDocument {
            $rejection = RejectedDocument::create([
                'document_id' => $document->document_id,
                'reason' => $reason,
            ]);

            $document->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
            ]);

            return $rejection;
        });

        if ($document->user) {
            $document->user->notify(
                new DocumentRejectedNotification($document, $rejection)
            );
        }

        $this->recordDocumentActivity(
            $document->document_id,
            'Document rejected',
            'Rejected the document: ' . $reason
        );

        $this->redirect(self::getUrl(['section' => 'rejected']));
    }

    public function returnDocumentAction(): Action
    {
        return Action::make('returnDocument')
            ->label('Return')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('success')
            ->tooltip('Return Document')
            ->extraAttributes([
                'class' => 'return-document-button',
            ])
            ->modalHeading('Return Document')
            ->modalDescription('Choose which document section this document should be returned to.')
            ->schema([
                Select::make('destination')
                    ->label('Return to')
                    ->options([
                        'incoming' => 'Incoming',
                        'outgoing' => 'Outgoing',
                    ])
                    ->required(),
            ])
            ->action(function (array $data, array $arguments, ?DocumentModel $record = null): void {
                $document = $this->resolveDocumentActionRecord($arguments, $record);
                $isOutgoing = $data['destination'] === 'outgoing';

                $document->update([
                    'status' => $isOutgoing ? 'outgoing' : 'in_progress',
                ]);

                $this->recordDocumentActivity(
                    $document->document_id,
                    'Document returned',
                    'Returned the document to ' . $data['destination'] . '.'
                );

                $this->redirect(self::getUrl([
                    'section' => $isOutgoing ? 'outgoing' : 'incoming',
                ]));
            });
    }

    public function viewRejectionReasonAction(): Action
    {
        return Action::make('viewRejectionReason')
            ->label('View reason')
            ->link()
            ->color('danger')
            ->modalHeading('Rejection Reason')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->fillForm(function (array $arguments): array {
                $rejection = RejectedDocument::findOrFail(
                    (int) $arguments['rejection']
                );

                $this->recordDocumentActivity(
                    $rejection->document_id,
                    'Rejection reason viewed',
                    'Viewed the document rejection reason.'
                );

                return [
                    'reason' => $rejection->reason,
                ];
            })
            ->schema([
                Textarea::make('reason')
                    ->label('Reason')
                    ->disabled()
                    ->rows(5)
                    ->dehydrated(false),
            ]);
    }

    public function completeDocument(int $documentId): void
    {
        $document = DocumentModel::with('user')->findOrFail($documentId);

        $document->update([
            'status' => 'completed',
        ]);

        if ($document->user) {
            $document->user->notify(
                new DocumentCompletedNotification($document)
            );
        }

        $this->recordDocumentActivity(
            $document->document_id,
            'Document completed',
            'Marked the document as completed.'
        );

        Notification::make()
            ->success()
            ->title('Document completed')
            ->body('The document was successfully marked as completed and moved to the Completed table.')
            ->send();

        $this->redirect(self::getUrl(['section' => 'completed']));
    }

    public function completeDocumentAction(): Action
    {
        return Action::make('completeDocument')
            ->label('Complete')
            ->color('gray')
            ->tooltip('Complete')
            ->modalHeading('Complete Document')
            ->modalDescription('Are you sure you want to mark this document as completed? It will be moved to the Completed table.')
            ->modalIcon('heroicon-o-check-circle')
            ->modalIconColor('gray')
            ->modalAlignment(Alignment::Center)
            ->modalFooterActionsAlignment(Alignment::Center)
            ->modalSubmitActionLabel('Complete document')
            ->modalCancelActionLabel('Cancel')
            ->extraAttributes([
                'class' => 'complete-document-button inline-flex items-center justify-center rounded-md px-3 py-2 text-xs font-semibold text-white transition',
            ])
            ->action(function (array $arguments, ?DocumentModel $record = null): void {
                $document = $this->resolveDocumentActionRecord($arguments, $record);

                $this->completeDocument($document->document_id);
            });
    }

    public function archiveDocumentAction(): Action
    {
        return Action::make('archiveDocument')
            ->label('Archive')
            ->icon('heroicon-o-archive-box')
            ->color('gray')
            ->modalHeading('Archive Document')
            ->modalDescription('Are you sure you want to archive this document? It will be marked as archived.')
            ->modalIcon('heroicon-o-archive-box')
            ->modalIconColor('gray')
            ->modalAlignment(Alignment::Center)
            ->modalFooterActionsAlignment(Alignment::Center)
            ->modalSubmitActionLabel('Archive document')
            ->modalCancelActionLabel('Cancel')
            ->action(function (array $arguments, ?DocumentModel $record = null): void {
                $document = $this->resolveDocumentActionRecord($arguments, $record);

                $this->archiveDocument($document->document_id);
            });
    }

    public function archiveDocument(int $documentId): void
    {
        $document = DocumentModel::findOrFail($documentId);

        if (! in_array($document->status, ['in_progress', 'outgoing', 'completed'], true)) {
            Notification::make()
                ->danger()
                ->title('Document cannot be archived')
                ->body('Only incoming, outgoing, and completed documents can be archived.')
                ->send();

            return;
        }

        $document->update([
            'status' => 'archived',
            'archived_at' => now(),
        ]);

        $this->recordDocumentActivity(
            $document->document_id,
            'Document archived',
            'Marked the document as archived.'
        );

        Notification::make()
            ->success()
            ->title('Document archived')
            ->body('The document was successfully marked as archived.')
            ->send();

        $this->redirect(self::getUrl(['section' => 'archived']));
    }

    public function returnArchivedDocumentAction(): Action
    {
        return Action::make('returnArchivedDocument')
            ->label('Return')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('success')
            ->tooltip('Return Document')
            ->extraAttributes([
                'class' => 'return-document-button',
            ])
            ->requiresConfirmation()
            ->modalHeading('Return Archived Document')
            ->modalDescription('This will restore the document to the Completed section.')
            ->modalSubmitActionLabel('Return document')
            ->action(function (array $arguments, ?DocumentModel $record = null): void {
                $document = $this->resolveDocumentActionRecord($arguments, $record);

                $document->update([
                    'status' => 'completed',
                    'archived_at' => null,
                ]);

                $this->recordDocumentActivity(
                    $document->document_id,
                    'Document returned from archive',
                    'Restored the document to the Completed section.'
                );

                Notification::make()
                    ->success()
                    ->title('Document returned')
                    ->body('The document was restored to the Completed section.')
                    ->send();

                $this->redirect(self::getUrl(['section' => 'archived']));
            });
    }


    public function messageDocument(int $documentId): void
    {
        $document = DocumentModel::findOrFail($documentId);

        $this->recordDocumentActivity(
            $document->document_id,
            'Message opened',
            'Opened the document conversation.'
        );

        $this->redirect(
            route('filament.admin.pages.messages', [
                'document' => $document->document_id,
            ])
        );
    }

    protected function recordDocumentActivity(
        int $documentId,
        string $actionType,
        string $actionDetails = '',
        ?string $oldValue = null,
        ?string $newValue = null
    ): void {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'document_id' => $documentId,
            'action_type' => $actionType,
            'action_details' => $actionDetails !== ''
                ? $actionDetails
                : $actionType,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ]);
    }
    
    
    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'sent_date' => 'date',
            'date_returned' => 'date',
            'outgoing_date' => 'date',
        ];
    }

}
