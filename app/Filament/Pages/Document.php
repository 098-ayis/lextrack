<?php

namespace App\Filament\Pages;

use App\Models\Document as DocumentModel;
use App\Models\DocumentVersion;
use App\Models\DocumentTransmittal;
use App\Models\RejectedDocument;
use Carbon\Carbon;
use App\Notifications\DocumentRejectedNotification;
use App\Notifications\DocumentAcceptedNotification;
use App\Notifications\DocumentPendingNotification;
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
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use App\Models\ActionType;
use App\Models\DocumentType;
use App\Models\ActivityLog;
use App\Models\OfficeUnit;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use chillerlan\QRCode\QRCode;
use UnitEnum;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QROutputInterface;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\DocumentDownloadService;
use App\Services\DocumentQrToken;
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
use Illuminate\Support\Facades\URL;
// use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class Document extends Page implements HasTable
{
    private const string OTHER_DOCUMENT_TYPE = '__other_document_type__';

    private const string OTHER_OFFICE_UNIT = '__other_office_unit__';

    private const string OTHER_ACTION_TYPE = '__other_action_type__';

    private const string OTHER_SENT_TO = '__other_sent_to__';

    private const string OTHER_RETURNED_FROM = '__other_returned_from__';

    use InteractsWithTable;
    // use HasPageShield;

    protected static ?string $slug = 'incoming';

    protected static ?int $navigationSort = 1;

    protected static string|UnitEnum|null $navigationGroup = 'MANAGEMENT';

    protected static ?string $navigationLabel = 'Documents';

    protected static ?string $title = 'Documents';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox';

    protected string $view = 'filament.pages.document-filament';

    public string $search = '';

    public string $typeFilter = '';

    public string $actionTypeFilter = '';

    public string $officeUnitFilter = '';

    public string $dateFilter = '';

    public string $activeSection = 'pending';

    public ?int $highlightedDocumentId = null;

    public bool $showAcceptedModal = false;

    public ?string $acceptedDocumentUploader = null;

    public ?int $qrCodeDocumentId = null;

    public ?string $qrCodeSvg = null;

    public static function getNavigationBadge(): ?string
    {
        $count = DocumentModel::query()
            ->where('status', 'pending')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public function mount(): void
    {
        $section = request()->query('section', 'pending');
        $document = request()->query('document');

        $this->activeSection = in_array($section, [
            'pending',
            'incoming',
            'outgoing',
            'completed',
            'rejected',
            'archived',
        ], true) ? $section : 'pending';

        $this->highlightedDocumentId = filled($document)
            ? DocumentModel::findForRoute($document)->document_id
            : null;

        $this->initializeDocumentNavigationViewState();
        $this->markDocumentSectionAsViewed($this->activeSection);
    }

    protected function initializeDocumentNavigationViewState(): void
    {
        if (! session()->has('admin.documents.navigation_started_at')) {
            session()->put(
                'admin.documents.navigation_started_at',
                now()->toIso8601String()
            );
        }
    }

    protected function markDocumentSectionAsViewed(string $section): void
    {
        session()->put(
            "admin.documents.sections.{$section}.viewed_at",
            now()->toIso8601String()
        );
    }

    public function getNewStatusSections(): array
    {
        $sections = [
            'pending' => 'pending',
            'incoming' => 'in_progress',
            'outgoing' => 'outgoing',
            'completed' => 'completed',
            'rejected' => 'rejected',
            'archived' => 'archived',
        ];
        $navigationStartedAt = session('admin.documents.navigation_started_at');

        if (! $navigationStartedAt) {
            return [];
        }

        return collect($sections)
            ->filter(function (string $status, string $section) use ($navigationStartedAt): bool {
                $viewedAt = session("admin.documents.sections.{$section}.viewed_at")
                    ?? $navigationStartedAt;

                return DocumentModel::query()
                    ->where('status', $status)
                    ->where('updated_at', '>', Carbon::parse($viewedAt))
                    ->exists();
            })
            ->keys()
            ->all();
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
            ->whereDoesntHave('documentRequests')
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

    public function acceptDocument(
        int $documentId,
        ?string $particulars = null,
        ?string $actionType = null,
    ): void
    {
        $result = DB::transaction(function () use ($documentId, $particulars, $actionType): array {

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
            | 1. Keep the existing LAO number, or assign one to a new request
            |--------------------------------------------------------------------------
            */

            /*
            |--------------------------------------------------------------------------
            | 2. Accept document
            |--------------------------------------------------------------------------
            */

            $document->update([
                // A revised document is the same request, so never replace an
                // LAO number that was already assigned to it.
                'lao_number' => $document->lao_number
                    ?: DocumentModel::generateLaoNumber($document->created_at),
                'status' => 'in_progress',
                'deadline' => DocumentModel::deadlineForType($document->document_type),
                'particulars' => $particulars !== null
                    ? trim($particulars)
                    : $document->particulars,
                'action_type' => $actionType !== null
                    ? trim($actionType)
                    : $document->action_type,
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

        if ($result['accepted']) {
            Notification::make()
                ->success()
                ->title('Document accepted')
                ->body('The document was accepted and moved to Incoming. LAO Number: ' . $document->lao_number)
                ->send();
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Send acceptance notification
        |--------------------------------------------------------------------------
        */

        if ($result['accepted'] && $document->user) {
            Notification::make()
                ->title($document->notificationLabel())
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

        if ($result['accepted']) {
            $this->redirect(self::getUrl(['section' => 'incoming']));
        }

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
            // Requests are managed on the Document Requests page. Once a
            // request is fulfilled it is linked through document_requests,
            // so it must not also appear in the regular Documents tables.
            ->whereDoesntHave('documentRequests')
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
            ->when($this->actionTypeFilter !== '', function (Builder $query): void {
                $query->where('action_type', $this->actionTypeFilter);
            })
            ->when($this->officeUnitFilter !== '', function (Builder $query): void {
                $query->where('office_unit', $this->officeUnitFilter);
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
            ->recordActionsAlignment('fi-align-center')
            ->recordUrl(fn (DocumentModel $record): string => ViewDocument::getUrl([
                'document' => $record->getPublicRouteKey(),
                'return_to' => static::getUrl(['section' => $this->activeSection]),
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
                ->alignStart()
                ->width('18rem')
                ->extraHeaderAttributes(['class' => 'min-w-[200px]'])
                ->extraCellAttributes(['class' => 'align-middle']);
        }

        $columns[] =
            ViewColumn::make('document_type')
                ->label('DOCUMENT TYPE')
                ->view('filament.tables.columns.document-type')
                ->alignLeft()
                ->width('9rem')
                ->extraHeaderAttributes(['class' => 'min-w-[140px]']);

        if ($this->activeSection === 'pending') {
            $columns[] = TextColumn::make('description')
                ->label('DESCRIPTION')
                ->placeholder('No description')
                ->limit(25)
                ->tooltip(fn (DocumentModel $record): ?string => filled($record->description)
                    ? $record->description
                    : null)
                ->wrap()
                ->extraHeaderAttributes(['class' => 'min-w-[180px]'])
                ->extraCellAttributes(['class' => 'align-middle']);

            $columns[] = ViewColumn::make('transmittal')
                ->label('TRANSMITTAL')
                ->view('filament.tables.columns.transmittal')
                ->alignCenter()
                ->width('8rem')
                ->extraHeaderAttributes(['class' => 'min-w-[180px]'])
                ->extraCellAttributes(['class' => 'align-middle']);

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
                ->disabledClick()
                ->alignLeft()
                ->extraCellAttributes(['class' => 'rejection-reason-cell']);
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
                ->width('13rem')
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
                    'document' => $record->getPublicRouteKey(),
                    'return_to' => static::getUrl(['section' => $this->activeSection]),
                ])),
            Action::make('downloadDocument')
                ->label('Download')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn (DocumentModel $record): string => route('admin.documents.download', [
                    'document' => $record->getPublicRouteKey(),
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

    public function updateSection(string $section): void
    {
        if (! in_array($section, [
            'pending',
            'incoming',
            'outgoing',
            'completed',
            'rejected',
            'archived',
        ], true)) {
            return;
        }

        if ($this->activeSection === $section) {
            return;
        }

        $this->activeSection = $section;
        $this->highlightedDocumentId = null;
        $this->resetTable();
        $this->markDocumentSectionAsViewed($section);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedActionTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedOfficeUnitFilter(): void
    {
        $this->resetPage();
    }

    public function clearDocumentFilters(): void
    {
        $this->typeFilter = '';
        $this->actionTypeFilter = '';
        $this->officeUnitFilter = '';
        $this->resetPage();
    }

    public function applyDocumentFilters(): void
    {
        $this->resetPage();
    }

    public function clearTypeFilter(): void
    {
        $this->typeFilter = '';
    }

    public function updatedDateFilter(): void
    {
        $this->resetPage();
    }

    public function getDocumentTypeFilterOptions(): array
    {
        return DocumentType::query()
            ->orderBy('type_name')
            ->pluck('type_name', 'type_name')
            ->all();
    }

    public function getActionTypeFilterOptions(): array
    {
        return ActionType::query()
            ->orderBy('action_name')
            ->pluck('action_name', 'action_name')
            ->all();
    }

    public function getOfficeUnitFilterOptions(): array
    {
        return OfficeUnit::query()
            ->orderBy('name')
            ->pluck('name', 'name')
            ->all();
    }

    public function openQrCode(int $documentId): void
    {
        try {
            $document = DocumentModel::findOrFail($documentId);
            $qrPayload = DocumentQrToken::encode($document);

            $this->qrCodeDocumentId = $documentId;
            $this->qrCodeSvg = (new QRCode(new QROptions([
                'outputType' => QROutputInterface::MARKUP_SVG,
                'outputBase64' => false,
                'scale' => 5,
            ])))->render($qrPayload);
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
    }

    public function addDocumentAction(): Action
    {
        return Action::make('addDocument')
            ->label('Add Document')
            ->icon('heroicon-o-plus')
            ->size('xs')
            ->modalHeading('Add New Document')
            ->modalSubmitAction(fn (Action $action): Action => $this->styleDocumentPrimarySubmitAction($action))
            ->modalFooterActions(fn (Action $action): array => [
                $action->getModalSubmitAction(),
                $action->getModalCancelAction(),
            ])
            ->modalFooterActionsAlignment(Alignment::End)
            ->extraAttributes([
                'class' => 'add-document-button',
            ])

            ->schema([
                TextInput::make('lao_number')
                    ->label('LAO Number')
                    ->default(fn (): string => DocumentModel::generateLaoNumber(now()))
                    ->readOnly(),

                Hidden::make('document_type_mode')
                    ->default('select')
                    ->dehydrated(false),

                Hidden::make('action_type_mode')
                    ->default('select')
                    ->dehydrated(false),

                Hidden::make('office_unit_mode')
                    ->default('select')
                    ->dehydrated(false),

                Grid::make(2)
                    ->schema([
                        Select::make('document_type')
                            ->label('Document Type')
                            ->placeholder('Select document type')
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
                            ->afterStateUpdated(function ($state, Set $set): void {
                                if ($state === self::OTHER_DOCUMENT_TYPE) {
                                    $set('document_type_mode', self::OTHER_DOCUMENT_TYPE);
                                    $set('document_type', null);
                                    $set('deadline', null);

                                    return;
                                }

                                $set('document_type_mode', 'select');
                                $set('deadline', DocumentModel::deadlineForType(
                                    filled($state) ? (string) $state : null,
                                ));
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

                        DatePicker::make('deadline')
                            ->label('Deadline'),
                    ]),

                Grid::make(2)
                    ->schema([
                        Select::make('action_type')
                            ->label('Action Taken')
                            ->options(fn () => ActionType::query()
                                ->orderBy('action_name')
                                ->pluck('action_name', 'action_name')
                                ->prepend('Others', self::OTHER_ACTION_TYPE)
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->visible(fn (Get $get): bool => $get('action_type_mode') !== self::OTHER_ACTION_TYPE)
                            ->dehydrated(fn (Get $get): bool => $get('action_type_mode') !== self::OTHER_ACTION_TYPE)
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                if ($state === self::OTHER_ACTION_TYPE) {
                                    $set('action_type_mode', self::OTHER_ACTION_TYPE);
                                    $set('action_type', null);

                                    return;
                                }

                                $set('action_type_mode', 'select');
                            })
                            ->nullable(),

                        TextInput::make('action_type')
                            ->label('Action Taken')
                            ->placeholder('Enter the action taken')
                            ->maxLength(255)
                            ->suffixAction(
                                Action::make('chooseListedActionType')
                                    ->icon(Heroicon::ChevronDown)
                                    ->tooltip('Choose from listed actions')
                                    ->action(function (Set $set): void {
                                        $set('action_type_mode', 'select');
                                        $set('action_type', null);
                                    }),
                            )
                            ->visible(fn (Get $get): bool => $get('action_type_mode') === self::OTHER_ACTION_TYPE)
                            ->dehydrated(fn (Get $get): bool => $get('action_type_mode') === self::OTHER_ACTION_TYPE)
                            ->required(fn (Get $get): bool => $get('action_type_mode') === self::OTHER_ACTION_TYPE),
                    
                        Select::make('office_unit')
                            ->label('Office / Unit')
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
                            ->label('Office / Unit')
                            ->placeholder('Enter the office/unit name')
                            ->maxLength(255)
                            ->suffixAction(
                                Action::make('chooseListedOfficeUnit')
                                    ->icon(Heroicon::ChevronDown)
                                    ->tooltip('Choose from listed offices/units')
                                    ->action(function (Set $set): void {
                                        $set('office_unit_mode', 'select');
                                        $set('office_unit', null);
                                    }),
                            )
                            ->visible(fn (Get $get): bool => $get('office_unit_mode') === self::OTHER_OFFICE_UNIT)
                            ->dehydrated(fn (Get $get): bool => $get('office_unit_mode') === self::OTHER_OFFICE_UNIT)
                            ->required(fn (Get $get): bool => $get('office_unit_mode') === self::OTHER_OFFICE_UNIT),
                    ]),

                Textarea::make('particulars')
                    ->label('Particulars')
                    ->required(),

                FileUpload::make('transmittal')
                    ->label('Transmittal / Endorsement')
                    ->disk('local')
                    ->directory('documents/transmittals')
                    ->multiple()
                    ->appendFiles()
                    ->panelLayout('integrated')
                    ->maxSize(5120)
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    ])
                    ->rules(['mimes:pdf,docx'])
                    ->helperText('Optional. PDF or DOCX only, up to 5 MB each.')
                    ->preserveFilenames()
                    ->extraAttributes(['class' => 'admin-document-upload-files'])
                    ->columnSpanFull(),

                FileUpload::make('file_path')
                    ->label('Document File(s)')
                    ->multiple()
                    ->appendFiles()
                    ->panelLayout('integrated')
                    ->disk('local')
                    ->directory('documents')
                    ->maxSize(5120)
                    ->acceptedFileTypes([
                        'application/pdf',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    ])
                    ->rules(['mimes:pdf,docx'])
                    ->helperText('Select one or more PDF or DOCX files. Maximum file size: 5 MB each; each file is added as a revision.')
                    ->preserveFilenames()
                    ->extraAttributes(['class' => 'admin-document-upload-files'])
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set): void {
                        $set('document_name', $this->uploadedDocumentName($state));
                    }),
            ])
            ->action(function (array $data) {
                $filePaths = array_values(array_filter(
                    (array) ($data['file_path'] ?? []),
                    static fn (mixed $path): bool => is_string($path) && filled($path),
                ));
                $transmittalPaths = array_values(array_filter(
                    (array) ($data['transmittal'] ?? []),
                    static fn (mixed $path): bool => is_string($path) && filled($path),
                ));
                $cleanupUploads = function () use ($filePaths, $transmittalPaths): void {
                    foreach ([...$filePaths, ...$transmittalPaths] as $path) {
                        if (is_string($path) && filled($path)) {
                            DocumentVersion::removeUnreferencedUpload($path);
                        }
                    }
                };

                $fileHashes = array_map(
                    static fn (string $path): ?string => DocumentVersion::hashForUpload($path),
                    $filePaths,
                );

                if ($filePaths === [] || in_array(null, $fileHashes, true)) {
                    $cleanupUploads();

                    Notification::make()
                        ->danger()
                        ->title('Upload could not be verified')
                        ->body('One or more document files could not be read. Please select the files again and try again.')
                        ->send();

                    return;
                }

                $hasDuplicateFiles = count($fileHashes) !== count(array_unique($fileHashes));

                foreach ($fileHashes as $fileHash) {
                    if (DocumentVersion::existsForDocumentOrUserHash(
                        0,
                        $fileHash,
                        auth()->id(),
                    )) {
                        $hasDuplicateFiles = true;

                        break;
                    }
                }

                if ($hasDuplicateFiles) {
                    $cleanupUploads();

                    Notification::make()
                        ->danger()
                        ->title('Duplicate document detected')
                        ->body('One or more selected files have already been uploaded. Please remove duplicates and try again.')
                        ->send();

                    return;
                }

                $transmittalHashes = array_map(
                    static fn (string $path): ?string => DocumentVersion::hashForUpload($path),
                    $transmittalPaths,
                );

                if (
                    in_array(null, $transmittalHashes, true)
                    || count($transmittalHashes) !== count(array_unique($transmittalHashes))
                    || ($transmittalHashes !== [] && DocumentTransmittal::query()
                        ->whereIn('file_hash', $transmittalHashes)
                        ->exists())
                ) {
                    $cleanupUploads();

                    Notification::make()
                        ->danger()
                        ->title('Transmittal upload could not be verified')
                        ->body('One or more transmittal/endorsement files could not be read or are duplicated. Please select the files again and try again.')
                        ->send();

                    return;
                }

                unset($data['file_path'], $data['transmittal']);

                $data['user_id'] = auth()->id();
                $data['transmittal'] = $transmittalPaths[0] ?? null;
                $data['deadline'] ??= DocumentModel::deadlineForType($data['document_type'] ?? null);
                $data['document_name'] = $this->uploadedDocumentName($filePaths[0])
                    ?? ($data['document_name'] ?? null);
                $targetStatus = match ($this->activeSection) {
                    'pending' => 'pending',
                    'incoming' => 'in_progress',
                    'outgoing' => 'outgoing',
                    'completed' => 'completed',
                    'rejected' => 'rejected',
                    'archived' => 'archived',
                    default => 'in_progress',
                };

                $document = DB::transaction(function () use ($data, $filePaths, $fileHashes, $transmittalPaths, $transmittalHashes, $targetStatus): DocumentModel {
                    // Generate again at save time so the number is always the
                    // latest available one, even if the form stayed open.
                    $data['lao_number'] = DocumentModel::generateLaoNumber(now());
                    $data['status'] = $targetStatus;

                    if ($targetStatus === 'archived') {
                        $data['archived_at'] = now();
                    }

                    $document = DocumentModel::create($data);

                    foreach ($transmittalPaths as $index => $transmittalPath) {
                        DocumentTransmittal::create([
                            'document_id' => $document->document_id,
                            'user_id' => auth()->id(),
                            'file_path' => $transmittalPath,
                            'file_hash' => $transmittalHashes[$index],
                        ]);
                    }

                    foreach ($filePaths as $index => $filePath) {
                        DocumentVersion::create([
                            'document_id' => $document->document_id,
                            'user_id' => auth()->id(),
                            'version_number' => (string) ($index + 1),
                            'file_path' => $filePath,
                            'file_hash' => $fileHashes[$index],
                            'source' => 'admin',
                        ]);
                    }

                    return $document;
                });

                $this->recordDocumentActivity(
                    $document->document_id,
                    'Document created',
                    'Created a new document.'
                );

                $this->markDocumentSectionAsViewed($this->activeSection);
            });
    }

    protected function resolveDocumentActionRecord(array $arguments, ?DocumentModel $record = null): DocumentModel
    {
        return $record ?? DocumentModel::findOrFail($arguments['document'] ?? null);
    }

    protected function styleDocumentPrimarySubmitAction(Action $action): Action
    {
        return $action->extraAttributes([
            'style' => 'background-color: #6366F1; border-color: #6366F1; color: #ffffff;',
        ]);
    }



    public function editDocumentAction(bool $asMenuItem = false): Action
    {
        return Action::make('editDocument')
            ->label($asMenuItem ? 'Edit' : '')
            ->icon('heroicon-o-pencil-square')
            ->tooltip('Edit')
            ->modalSubmitAction(fn (Action $action): Action => $this->styleDocumentPrimarySubmitAction($action))
            ->extraAttributes($asMenuItem ? [] : [
                'class' => 'edit-document-button',
            ])

            ->schema(function (array $arguments, ?DocumentModel $record = null): array {
                $document = $record ?? DocumentModel::find($arguments['document'] ?? null);

                if ($document?->status === 'outgoing') {
                    return [
                        Hidden::make('sent_to_mode')
                            ->default('select')
                            ->dehydrated(false),

                        Hidden::make('returned_from_mode')
                            ->default('select')
                            ->dehydrated(false),

                        TextInput::make('document_name')
                            ->label('Document Name')
                            ->maxLength(255),

                        Grid::make(2)
                        ->schema([
                            Select::make('document_type')
                                ->label('Document Type')
                                ->placeholder('Select document type')
                                ->options(fn () => DocumentType::query()
                                    ->orderBy('type_name')
                                    ->pluck('type_name', 'type_name'))
                                ->searchable()
                                ->preload()
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



                        DatePicker::make('outgoing_date')
                            ->label('Outgoing Date')
                            ->default(now()->toDateString()),

                        Grid::make(2)
                            ->schema([
                                Select::make('sent_to')
                                    ->label('Sent To')
                                    ->options(fn () => OfficeUnit::query()
                                        ->orderBy('name')
                                        ->pluck('name', 'name')
                                        ->prepend('Others', self::OTHER_SENT_TO)
                                        ->toArray())
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->visible(fn (Get $get): bool => $get('sent_to_mode') !== self::OTHER_SENT_TO)
                                    ->dehydrated(fn (Get $get): bool => $get('sent_to_mode') !== self::OTHER_SENT_TO)
                                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                                        if ($state === self::OTHER_SENT_TO) {
                                            $set('sent_to_mode', self::OTHER_SENT_TO);
                                            $set('sent_to', null);
                                            $set('returned_from_mode', self::OTHER_RETURNED_FROM);
                                            $set('returned_from', null);

                                            return;
                                        }

                                        $set('sent_to_mode', 'select');
                                        $set('returned_from_mode', 'select');
                                        $set('returned_from', $state);
                                    })
                                    ->required(),

                                TextInput::make('sent_to')
                                    ->label('Sent To')
                                    ->placeholder('Enter the destination')
                                    ->maxLength(255)
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                                        $set('returned_from_mode', self::OTHER_RETURNED_FROM);
                                        $set('returned_from', $state);
                                    })
                                    ->suffixAction(
                                        Action::make('chooseListedSentTo')
                                            ->icon(Heroicon::ChevronDown)
                                            ->tooltip('Choose from listed offices/units')
                                            ->action(function (Set $set): void {
                                                $set('sent_to_mode', 'select');
                                                $set('sent_to', null);
                                                $set('returned_from_mode', 'select');
                                                $set('returned_from', null);
                                            }),
                                    )
                                    ->visible(fn (Get $get): bool => $get('sent_to_mode') === self::OTHER_SENT_TO)
                                    ->dehydrated(fn (Get $get): bool => $get('sent_to_mode') === self::OTHER_SENT_TO)
                                    ->required(fn (Get $get): bool => $get('sent_to_mode') === self::OTHER_SENT_TO),

                                DatePicker::make('sent_date')
                                    ->label('Sent Date')
                                    ->default(now()->toDateString()),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('returned_from')
                                    ->label('Returned From')
                                    ->options(fn () => OfficeUnit::query()
                                        ->orderBy('name')
                                        ->pluck('name', 'name')
                                        ->prepend('Others', self::OTHER_RETURNED_FROM)
                                        ->toArray())
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->disabled()
                                    ->visible(fn (Get $get): bool => $get('returned_from_mode') !== self::OTHER_RETURNED_FROM)
                                    ->dehydrated(fn (Get $get): bool => $get('returned_from_mode') !== self::OTHER_RETURNED_FROM)
                                    ->required(),

                                TextInput::make('returned_from')
                                    ->label('Returned From')
                                    ->placeholder('Matches Sent To')
                                    ->maxLength(255)
                                    ->readOnly()
                                    ->visible(fn (Get $get): bool => $get('returned_from_mode') === self::OTHER_RETURNED_FROM)
                                    ->dehydrated(fn (Get $get): bool => $get('returned_from_mode') === self::OTHER_RETURNED_FROM)
                                    ->required(fn (Get $get): bool => $get('returned_from_mode') === self::OTHER_RETURNED_FROM),

                                DatePicker::make('date_returned')
                                    ->label('Returned Date'),
                            ]),
                    ];
                }

                return [
                    Grid::make(2)
                        ->schema([
                            TextInput::make('lao_number')
                                ->label('LAO Number')
                                ->required(),

                            TextInput::make('document_name')
                                ->label('Document Name')
                                ->maxLength(255),

                            Select::make('document_type')
                                ->label('Document Type')
                                ->placeholder('Select document type')
                                ->options(fn () => DocumentType::query()
                                    ->orderBy('type_name')
                                    ->pluck('type_name', 'type_name'))
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function ($state, Set $set): void {
                                    $deadline = DocumentModel::deadlineForType($state);

                                    if (filled($deadline)) {
                                        $set('deadline', $deadline);
                                    }
                                })
                                ->required(),

                            Select::make('action_type')
                                ->label('Action Taken')
                                ->placeholder('Select action')
                                ->options(fn () => ActionType::query()
                                    ->orderBy('action_name')
                                    ->pluck('action_name', 'action_name'))
                                ->searchable()
                                ->preload()
                                ->nullable(),

                            Select::make('office_unit')
                                ->label('Office / Unit')
                                ->options(fn () => OfficeUnit::query()
                                    ->orderBy('name')
                                    ->pluck('name', 'name'))
                                ->searchable()
                                ->preload()
                                ->required(),

                            Select::make('status')
                                ->label('Status')
                                ->options([
                                    'pending' => 'Pending',
                                    'in_progress' => 'Incoming',
                                    'completed' => 'Completed',
                                    'returned' => 'Returned',
                                    'outgoing' => 'Outgoing',
                                ])
                                ->required(),

                            DatePicker::make('deadline')
                                ->label('Deadline')
                                ->default(now()->toDateString()),

                            Textarea::make('particulars')
                                ->label('Particulars')
                                ->required(),
                        ]),

                    FileUpload::make('file_path')
                        ->label('Upload New Revision')
                        ->disk('local')
                        ->directory('documents/versions')
                        ->preserveFilenames()
                        ->multiple()
                        ->appendFiles()
                        ->acceptedFileTypes([
                            'application/pdf',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        ])
                        ->columnSpanFull(),
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

                    'sent_to_mode' => filled($document->sent_to) && ! OfficeUnit::query()
                        ->where('name', $document->sent_to)
                        ->exists()
                        ? self::OTHER_SENT_TO
                        : 'select',

                    'returned_from_mode' => filled($document->sent_to) && ! OfficeUnit::query()
                        ->where('name', $document->sent_to)
                        ->exists()
                        ? self::OTHER_RETURNED_FROM
                        : 'select',

                    // Important: preload current Action Taken
                    'action_type' => $document->action_type,

                    'office_unit' => $document->office_unit,
                    'particulars' => $document->particulars,
                    'deadline' => $document->deadline ?? now()->toDateString(),
                    'status' => $document->status,
                    'outgoing_date' => $document->outgoing_date,
                    'sent_to' => $document->sent_to,
                    'sent_date' => $document->sent_date,
                    'returned_from' => $document->sent_to,
                    'date_returned' => $document->date_returned,
                ];
            })
            ->action(function (array $data, array $arguments, ?DocumentModel $record = null): void {
                $document = $this->resolveDocumentActionRecord($arguments, $record);

                if ($document->status === 'outgoing') {
                    $data['returned_from'] = $data['sent_to'] ?? null;
                }

                $filePaths = array_values(array_filter(
                    (array) ($data['file_path'] ?? []),
                    static fn (mixed $path): bool => is_string($path) && filled($path),
                ));
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

                $uploadedNames = [];
                $duplicateNames = [];
                $unreadableNames = [];
                $acceptedHashes = [];

                foreach ($filePaths as $filePath) {
                    $fileHash = DocumentVersion::hashForUpload($filePath);

                    if ($fileHash === null) {
                        DocumentVersion::removeUnreferencedUpload($filePath);
                        $unreadableNames[] = basename($filePath);

                        continue;
                    }

                    if (
                        isset($acceptedHashes[$fileHash])
                        || DocumentVersion::existsForDocumentOrUserHash(
                            $document->document_id,
                            $fileHash,
                            auth()->id(),
                        )
                    ) {
                        DocumentVersion::removeUnreferencedUpload($filePath);
                        $duplicateNames[] = basename($filePath);

                        continue;
                    }

                    $acceptedHashes[$fileHash] = $filePath;
                }

                foreach ($acceptedHashes as $fileHash => $filePath) {
                    DocumentVersion::create([
                        'document_id' => $document->document_id,
                        'user_id' => auth()->id(),
                        'version_number' => (string) $this->getNextVersionNumber($document),
                        'file_path' => $filePath,
                        'file_hash' => $fileHash,
                        'source' => 'admin',
                    ]);

                    $uploadedNames[] = basename($filePath);
                }

                if ($uploadedNames !== []) {
                    $updatedFields[] = 'Document File';
                }

                $updatedSummary = $updatedFields !== []
                    ? implode(', ', $updatedFields) . ' changed'
                    : 'No fields changed';

                $this->recordDocumentActivity(
                    $document->document_id,
                    'Document updated',
                    $updatedSummary . ($uploadedNames !== []
                        ? ': ' . implode(', ', $uploadedNames)
                        : ''),
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
        // Only Legal Staff can download original documents.
        $user = auth()->user();

        abort_unless(
            $user
            && $user->hasRole('Admin')
            && ! $user->hasRole('Super Admin'),
            403,
            'You are not authorized to download this document.'
        );

        $document = DocumentModel::with(['user', 'latestVersion'])
            ->findOrFail($documentId);

        $version = $document->latestVersion;

        abort_unless(
            $version?->file_path &&
            $version->storageDisk()->exists($version->file_path),
            404
        );

        $this->recordDocumentActivity(
            $document->document_id,
            'Document downloaded',
            'Downloaded ' . basename((string) $version->file_path) . '.'
        );

        return app(DocumentDownloadService::class)
            ->download($document, $version);
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
        $this->showAcceptedModal = false;
        $this->acceptedDocumentUploader = null;
    }

    public function acceptDocumentAction(): Action
    {
        return Action::make('acceptDocument')
            ->label('Accept')
            ->color('success')
            ->size('xs')
            ->modalHeading('Accept Document')
            
            ->schema(function (array $arguments, ?DocumentModel $record = null): array {
                $document = $record ?? DocumentModel::find($arguments['document'] ?? null);

                return [
                    Grid::make(2)
                        ->schema([
                            Placeholder::make('document_description')
                                ->label('Document Description')
                                ->content($document?->description ?: 'No description provided')
                                ->extraAttributes([
                                    'class' => 'h-full min-h-[6.75rem] rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800',
                                ]),

                            Textarea::make('particulars')
                                ->label('Particulars')
                                ->rows(4)
                                ->autosize()
                                ->maxLength(65535)
                                ->required(),

                        ]),
                        
                         Select::make('action_type')
                                ->label('Action Taken')
                                ->options(fn () => ActionType::query()
                                    ->orderBy('action_name')
                                    ->pluck('action_name', 'action_name')
                                    ->toArray())
                                ->default($document?->action_type)
                                ->searchable()
                                ->preload()
                                ->required(),
                ];
            })
            ->modalContent(function (DocumentModel $record) {
                return view(
                    'filament.actions.review-document',
                    [
                        'document' => $record,
                        'assignedLaoNumber' => $record->lao_number
                            ?: DocumentModel::generateLaoNumber($record->created_at),
                    ]
                );
            })
            ->modalContentFooter(fn (DocumentModel $record) => view(
                'filament.actions.transmittal-preview',
                ['document' => $record]
            ))
            ->modalIcon('heroicon-o-check-circle')
            ->modalIconColor('success')
            ->modalAlignment(Alignment::Center)
            ->modalFooterActionsAlignment(Alignment::Center)
            ->modalSubmitActionLabel('Accept document')
            ->modalCancelActionLabel('Cancel')
            ->extraAttributes([
                'class' => 'inline-flex h-9 items-center justify-center rounded-md border !border-green-200 !bg-green-100 px-3 text-xs font-semibold !text-green-800 transition hover:!bg-green-200 dark:!border-green-800 dark:!bg-green-900/30 dark:!text-green-300 dark:hover:!bg-green-900/50',
            ])
            ->action(function (array $arguments, array $data, ?DocumentModel $record = null): void {
                $document = $this->resolveDocumentActionRecord($arguments, $record);

                $this->acceptDocument(
                    $document->document_id,
                    (string) $data['particulars'],
                    (string) $data['action_type'],
                );
            });
    }

    public function markAsOutgoing(int $documentId, string $sentDate, string $sentTo): void
    {
        $document = DocumentModel::findOrFail($documentId);
        $sentDate = Carbon::parse($sentDate)->toDateString();

        $document->update([
            'status' => 'outgoing',
            'sent_date' => $sentDate,
            'outgoing_date' => $sentDate,
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

    }

    public function markAsOutgoingAction(): Action
    {
        return Action::make('markAsOutgoing')
            ->label('Outgoing')
            ->color('gray')
            ->modalHeading('Add Document to Outgoing')
            ->modalDescription('Provide the destination and sent date for this document.')
            ->modalWidth(Width::Small)
            ->modalAlignment(Alignment::Center)
            ->modalFooterActionsAlignment(Alignment::Center)
            ->modalSubmitAction(fn (Action $action): Action => $this->styleDocumentPrimarySubmitAction($action))
            ->modalSubmitActionLabel('Add to outgoing')
            ->modalCancelActionLabel('Cancel')
            ->extraAttributes([
                'class' => 'outgoing-document-button',
            ])
            ->schema([
                Hidden::make('sent_to_mode')
                    ->default('select')
                    ->dehydrated(false),

                Select::make('sent_to')
                    ->label('Sent To')
                    ->options(fn () => OfficeUnit::query()
                        ->orderBy('name')
                        ->pluck('name', 'name')
                        ->prepend('Others', self::OTHER_SENT_TO)
                        ->toArray())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->visible(fn (Get $get): bool => $get('sent_to_mode') !== self::OTHER_SENT_TO)
                    ->dehydrated(fn (Get $get): bool => $get('sent_to_mode') !== self::OTHER_SENT_TO)
                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                        if ($state === self::OTHER_SENT_TO) {
                            $set('sent_to_mode', self::OTHER_SENT_TO);
                            $set('sent_to', null);

                            return;
                        }

                        $set('sent_to_mode', 'select');
                    })
                    ->required(),

                TextInput::make('sent_to')
                    ->label('Sent To')
                    ->placeholder('Enter the destination')
                    ->maxLength(255)
                    ->suffixAction(
                        Action::make('chooseListedSentTo')
                            ->icon(Heroicon::ChevronDown)
                            ->tooltip('Choose from listed offices/units')
                            ->action(function (Set $set): void {
                                $set('sent_to_mode', 'select');
                                $set('sent_to', null);
                            }),
                    )
                    ->visible(fn (Get $get): bool => $get('sent_to_mode') === self::OTHER_SENT_TO)
                    ->dehydrated(fn (Get $get): bool => $get('sent_to_mode') === self::OTHER_SENT_TO)
                    ->required(fn (Get $get): bool => $get('sent_to_mode') === self::OTHER_SENT_TO),

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
            ->modalWidth(Width::Small)
            ->modalAlignment(Alignment::Center)
            ->modalFooterActionsAlignment(Alignment::Center)
            ->modalSubmitActionLabel('Reject document')
            ->modalCancelActionLabel('Cancel')
            ->extraAttributes([
                'class' => 'inline-flex h-9 items-center justify-center rounded-md border !border-red-200 !bg-red-100 px-3 text-xs font-semibold !text-red-800 transition hover:!bg-red-200 dark:!border-red-800 dark:!bg-red-900/30 dark:!text-red-300 dark:hover:!bg-red-900/50',
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

        Notification::make()
            ->success()
            ->title('Document rejected')
            ->body('The document was rejected and remains on the current table.')
            ->send();
    }

    public function returnDocumentAction(): Action
    {
        return Action::make('returnDocument')
            ->label('Return')
            ->color('success')
            ->tooltip('Return Document')
            ->extraAttributes([
                'class' => 'return-document-button',
            ])
            ->requiresConfirmation()
            ->modalHeading('Return Document')
            ->modalDescription(fn (): string => $this->activeSection === 'rejected'
                ? 'This document will be returned to Pending for validation.'
                : 'Choose which document status this document should be returned to.')
            ->modalWidth(Width::Small)
            ->modalSubmitActionLabel('Return document')
            ->schema(function (array $arguments, ?DocumentModel $record = null): array {
                if ($this->activeSection === 'rejected') {
                    return [];
                }

                $document = $record ?? DocumentModel::find($arguments['document'] ?? null);
                $options = blank($document?->lao_number)
                    ? ['pending' => 'Pending']
                    : [
                        'incoming' => 'Incoming',
                        'outgoing' => 'Outgoing',
                    ];

                return [
                    Select::make('destination')
                        ->label('Return to')
                        ->options($options)
                        ->default(blank($document?->lao_number) ? 'pending' : null)
                        ->required(),
                ];
            })
            ->action(function (array $data, array $arguments, ?DocumentModel $record = null): void {
                $document = $this->resolveDocumentActionRecord($arguments, $record);
                $destination = $this->activeSection === 'rejected'
                    ? 'pending'
                    : ($data['destination'] ?? 'incoming');
                $status = match ($destination) {
                    'pending' => 'pending',
                    'outgoing' => 'outgoing',
                    default => 'in_progress',
                };

                $wasReturnedFromRejected = $this->activeSection === 'rejected'
                    && $destination === 'pending';

                $document->loadMissing('user');

                $document->update([
                    'status' => $status,
                    ...($wasReturnedFromRejected
                        ? ['rejection_reason' => null]
                        : []),
                ]);

                if ($wasReturnedFromRejected && $document->user) {
                    $document->user->notify(
                        new DocumentPendingNotification($document)
                    );
                }

                $this->recordDocumentActivity(
                    $document->document_id,
                    'Document returned',
                    'Returned the document to ' . $destination . '.'
                );

                Notification::make()
                    ->success()
                    ->title('Document returned')
                    ->body('The document was returned successfully.')
                    ->send();
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

    }

    public function completeDocumentAction(): Action
    {
        return Action::make('completeDocument')
            ->label('Complete')
            ->color('gray')
            ->tooltip('Complete')
            ->modalHeading('Complete Document')
            ->modalDescription('Are you sure you want to mark this document as completed? It will be moved to the Completed table.')
            ->modalWidth(Width::Small)
            ->modalIcon('heroicon-o-check-circle')
            ->modalIconColor('gray')
            ->modalAlignment(Alignment::Center)
            ->modalFooterActionsAlignment(Alignment::Center)
            ->modalSubmitAction(fn (Action $action): Action => $this->styleDocumentPrimarySubmitAction($action))
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
            ->modalSubmitAction(fn (Action $action): Action => $this->styleDocumentPrimarySubmitAction($action))
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

    }

    public function returnArchivedDocumentAction(): Action
    {
        return Action::make('returnArchivedDocument')
            ->label('Return')
            ->color('success')
            ->tooltip('Return Document')
            ->extraAttributes([
                'class' => 'return-document-button',
            ])
            ->requiresConfirmation()
            ->modalHeading('Return Archived Document')
            ->modalDescription('Choose which document status this document should be returned to.')
            ->modalWidth(Width::Small)
            ->modalSubmitActionLabel('Return document')
            ->schema(function (array $arguments, ?DocumentModel $record = null): array {
                $document = $record ?? DocumentModel::find($arguments['document'] ?? null);
                $options = blank($document?->lao_number)
                    ? ['pending' => 'Pending']
                    : [
                        'incoming' => 'Incoming',
                        'outgoing' => 'Outgoing',
                        'completed' => 'Completed',
                    ];

                return [
                    Select::make('destination')
                        ->label('Return to')
                        ->options($options)
                        ->default(blank($document?->lao_number) ? 'pending' : null)
                        ->required(),
                ];
            })
            ->action(function (array $data, array $arguments, ?DocumentModel $record = null): void {
                $document = $this->resolveDocumentActionRecord($arguments, $record);
                $destination = $data['destination'];
                $status = match ($destination) {
                    'pending' => 'pending',
                    'incoming' => 'in_progress',
                    'outgoing' => 'outgoing',
                    default => 'completed',
                };

                $document->update([
                    'status' => $status,
                    'archived_at' => null,
                ]);

                $this->recordDocumentActivity(
                    $document->document_id,
                    'Document returned from archive',
                    'Restored the document to the ' . ucfirst($destination) . ' section.'
                );

                Notification::make()
                    ->success()
                    ->title('Document returned')
                    ->body('The document was restored to the ' . ucfirst($destination) . ' section.')
                    ->send();

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
                'document' => $document->getPublicRouteKey(),
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

    protected function uploadedDocumentName(mixed $file): ?string
    {
        if (is_array($file)) {
            return $this->uploadedDocumentName(reset($file) ?: null);
        }

        if ($file instanceof TemporaryUploadedFile || $file instanceof UploadedFile) {
            $originalName = basename($file->getClientOriginalName());

            return $originalName !== '' ? $originalName : null;
        }

        return is_string($file) && filled($file)
            ? basename($file)
            : null;
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
