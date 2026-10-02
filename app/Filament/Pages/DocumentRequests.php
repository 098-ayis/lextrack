<?php

namespace App\Filament\Pages;

use App\Models\ActionType;
use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\OfficeUnit;
use App\Notifications\DocumentAcceptedNotification;
use App\Notifications\DocumentRejectedNotification;
use App\Models\Calendar as CalendarModel;
use App\Models\DocumentVersion;
use App\Models\Conversation;
use App\Notifications\DocumentRequestRejectedNotification;
use App\Notifications\DocumentRequestFulfilledNotification;
use App\Notifications\DocumentRequestAcceptedNotification;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use UnitEnum;
// use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class DocumentRequests extends Page implements HasTable
{
    use InteractsWithTable;
   // use HasPageShield;

    protected static ?int $navigationSort = 2;

    protected static string|UnitEnum|null $navigationGroup = 'MANAGEMENT';

    protected static string|\BackedEnum|null $navigationIcon =
        'heroicon-o-document-plus';

    protected static ?string $navigationLabel = 'Requests';

    protected static ?string $title = 'Document Requests';

    protected string $view = 'filament.pages.document-requests';

    public string $activeSection = 'pending';

    public string $search = '';

    public string $typeFilter = '';

    public string $actionTypeFilter = '';

    public string $officeUnitFilter = '';

    public string $dateFilter = '';

    public ?string $latestRequestMarker = null;

    public static function getNavigationBadge(): ?string
    {
        $count = DocumentRequest::query()
            ->whereIn('status', ['pending', 'for_release'])
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

        $this->activeSection = in_array(
            $section,
            [
                'pending',
                'ready_for_pickup',
                'completed',
                'rejected',
            ],
            true
        ) ? $section : 'pending';

        $this->latestRequestMarker = $this->getLatestRequestMarker();
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function getStatusCounts(): array
    {
        $counts = DocumentRequest::query()
            ->select('status')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            'pending' => (int) ($counts['pending'] ?? 0) + (int) ($counts['for_release'] ?? 0),
            'ready_for_pickup' => (int) ($counts['ready_for_pickup'] ?? 0),
            'completed' => (int) ($counts['completed'] ?? 0),
            'rejected' => (int) ($counts['rejected'] ?? 0),
        ];
    }

    public function refreshRequests(): void
    {
        $latestRequestMarker = $this->getLatestRequestMarker();

        if ($latestRequestMarker === $this->latestRequestMarker) {
            // The lightweight poll is only a change check. Do not re-render
            // the admin page when no request arrived.
            $this->skipRender();

            return;
        }

        $this->latestRequestMarker = $latestRequestMarker;

        // Clear Filament's cached records only after a new request arrives,
        // without resetting staff filters or pagination.
        $this->flushCachedTableRecords();
    }

    protected function getLatestRequestMarker(): ?string
    {
        $request = DocumentRequest::query()
            ->select(['request_id', 'created_at'])
            ->latest('created_at')
            ->latest('request_id')
            ->first();

        return $request
            ? $request->created_at->format('Y-m-d H:i:s.u') . '|' . $request->request_id
            : null;
    }

    protected function getDocumentRequestTableQuery(): Builder
    {
        $statuses = match ($this->activeSection) {
            'ready_for_pickup' => ['ready_for_pickup'],
            'completed' => ['completed'],
            'rejected' => ['rejected'],
            default => ['pending', 'for_release'],
        };

        return DocumentRequest::query()
            ->with([
                'document.user',
                'document.latestVersion',
                'user',
            ])
            ->whereIn('status', $statuses)
            ->when(trim($this->search) !== '', function (Builder $query): void {
                $search = '%' . trim($this->search) . '%';

                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->whereHas('document', function (Builder $query) use ($search): void {
                            $query
                                ->where('office_unit', 'like', $search)
                                ->orWhere('particulars', 'like', $search);
                        })
                        ->orWhere('purpose', 'like', $search)
                        ->orWhere('purpose_details', 'like', $search)
                        ->orWhereHas('user', function (Builder $query) use ($search): void {
                            $query
                                ->where('name', 'like', $search)
                                ->orWhere('email', 'like', $search);
                        });
                });
            })
            ->when($this->typeFilter !== '', function (Builder $query): void {
                $query->whereHas('document', function (Builder $query): void {
                    $query->where('document_type', $this->typeFilter);
                });
            })
            ->when($this->actionTypeFilter !== '', function (Builder $query): void {
                $query->whereHas('document', function (Builder $query): void {
                    $query->where('action_type', $this->actionTypeFilter);
                });
            })
            ->when($this->officeUnitFilter !== '', function (Builder $query): void {
                $query->whereHas('document', function (Builder $query): void {
                    $query->where('office_unit', $this->officeUnitFilter);
                });
            })
            ->when($this->dateFilter !== '', function (Builder $query): void {
                $query->whereDate('date_of_request', $this->dateFilter);
            })
            ->latest('date_of_request')
            ->latest('request_id');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->getDocumentRequestTableQuery())
            ->columns($this->getDocumentRequestTableColumns())
            ->recordActions($this->getDocumentRequestTableActions())
            ->recordActionsColumnLabel('ACTION')
            ->recordActionsAlignment('end')
            ->groups([
                Group::make('date_of_request')
                    ->date()
                    ->label('Requested')
                    ->titlePrefixedWithLabel(false)
                    ->getTitleFromRecordUsing(
                        fn (DocumentRequest $record): Htmlable =>
                            new \Illuminate\Support\HtmlString(
                                'Requested ' . $record->date_of_request->format('F d, Y')
                            )
                    ),
            ])
            ->defaultGroup('date_of_request')
            ->groupingSettingsHidden()
            ->recordActionsAlignment('center')
            ->defaultSort('date_of_request', 'desc')
            ->paginationPageOptions([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->searchable(false)
            ->striped()
            ->extraAttributes([
                'class' => 'admin-document-requests-filament-table',
            ]);
    }

    protected function getDocumentRequestTableColumns(): array
    {
        $columns = [
            ViewColumn::make('document_details')
                ->label('PURPOSE')
                ->view('filament.tables.columns.request-document-purpose')
                ->alignLeft()
                ->width('14rem')
                ->extraHeaderAttributes(['class' => 'min-w-[200px]']),

            TextColumn::make('purpose_details')
                ->label('DETAILS')
                ->placeholder('—')
                ->alignLeft()
                ->width('30rem')
                ->extraHeaderAttributes(['class' => 'min-w-[320px]'])
                ->wrap(),

            TextColumn::make('copy_type')
                ->label('TYPE')
                ->visible(fn (): bool => $this->activeSection !== 'ready_for_pickup')
                ->formatStateUsing(
                    fn (?string $state): string => match ($state) {
                        'original' => 'Original',
                        'soft_copy' => 'Soft copy',
                        default => '—',
                    }
                )
                ->alignLeft()
                ->extraHeaderAttributes(['class' => 'min-w-[140px]']),


            ViewColumn::make('requested_by')
                ->label('REQUESTED BY')
                ->view('filament.tables.columns.requested-by')
                ->alignCenter()
                ->width('5rem')
                ->extraHeaderAttributes(['class' => 'min-w-[80px]']),

            TextColumn::make('date_of_request')
                ->label('DATE OF REQUEST')
                ->date('F d, Y')
                ->alignCenter()
                ->extraHeaderAttributes(['class' => 'min-w-[150px]']),
        ];

        if ($this->activeSection === 'ready_for_pickup') {
            $columns[] = TextColumn::make('pickup_at')
                ->label('PICKUP STATUS')
                ->state(fn (DocumentRequest $record): string => $this->pickupState($record))
                ->badge()
                ->color(fn (DocumentRequest $record): string => $this->isPickupOverdue($record) ? 'danger' : 'success')
                ->alignCenter()
                ->extraHeaderAttributes(['class' => 'min-w-[150px]']);
        } elseif ($this->activeSection !== 'pending') {
            $columns[] = TextColumn::make('date_processed')
                ->label('PICKUP DATE')
                ->date('F d, Y')
                ->placeholder('Unknown date')
                ->alignCenter()
                ->extraHeaderAttributes(['class' => 'min-w-[150px]']);
        }

        if ($this->activeSection === 'rejected') {
            $columns[] = TextColumn::make('rejection_reason')
                ->label('REASON FOR REJECTION')
                ->state(
                    fn (DocumentRequest $record): string => filled($record->rejection_reason)
                        ? (string) $record->rejection_reason
                        : '—'
                )
                ->wrap()
                ->width('20rem')
                ->extraHeaderAttributes(['class' => 'min-w-[220px]']);
        }

        return $columns;
    }

    protected function isPickupOverdue(DocumentRequest $record): bool
    {
        return $record->status === 'ready_for_pickup'
            && $record->copy_type === 'original'
            && $record->claimed_at === null
            && $record->pickup_at?->isPast() === true;
    }

    protected function pickupState(DocumentRequest $record): string
    {
        if (! $this->isPickupOverdue($record)) {
            return 'Ready for Pickup';
        }

        $hasNoShow = ActivityLog::query()
            ->where('request_id', $record->request_id)
            ->where('action_type', 'pickup_no_show')
            ->where('old_value', $record->pickup_at?->toDateTimeString())
            ->exists();

        return $hasNoShow ? 'No-Show' : 'Pickup Overdue';
    }

    protected function getDocumentRequestTableActions(): array
    {
        if ($this->activeSection === 'pending') {
            return [

                $this->acceptTransitionAction(),
                $this->rejectRequestAction(),
                $this->requestOptionsAction(),
            ];
        }

        return match ($this->activeSection) {
            'ready_for_pickup' => [
                $this->markClaimedAction(),
                $this->requestOptionsAction($this->reschedulePickupAction(), $this->markNoShowAction()),
            ],
            'completed' => [
                $this->replaceFileAction(),
                $this->requestOptionsAction(),
            ],
            'rejected' => [
                $this->restoreRequestAction(),
                $this->requestOptionsAction(),
            ],
            default => [$this->requestOptionsAction()],
        };
    }

    protected function pickupTimeOptions(): array
    {
        $options = [];

        for ($minutes = 8 * 60; $minutes <= 17 * 60; $minutes += 30) {
            $time = sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);

            $options[$time] = \Carbon\Carbon::createFromFormat('H:i', $time)
                ->format('g:i A');
        }

        return $options;
    }

    public function updateSection(string $section): void
    {
        if (! in_array($section, [
            'pending',
            'ready_for_pickup',
            'completed',
            'rejected',
        ], true)) {
            return;
        }

        if ($this->activeSection === $section) {
            return;
        }

        $this->activeSection = $section;
        $this->resetTable();
    }

    /* public function acceptRequestAction(): Action
    {
        return Action::make('acceptRequest')
            ->label('Accept')
            ->color('success')
            ->button()
            ->modalHeading(
                fn (DocumentRequest $record): string =>
                    $record->copy_type === 'soft_copy'
                        ? 'Accept Soft Copy Request'
                        : 'Accept Original Copy Request'
            )
            ->modalIcon('heroicon-o-check-circle')
            ->modalIconColor('success')
            ->modalDescription(
                fn (DocumentRequest $record): string =>
                    $record->copy_type === 'soft_copy'
                        ? 'Upload the document that will be delivered to the requester.'
                        : 'Set the pickup schedule and add it to your calendar.'
            )
            ->modalAlignment(\Filament\Support\Enums\Alignment::Center)
            ->modalFooterActionsAlignment(\Filament\Support\Enums\Alignment::Center)
            ->modalSubmitActionLabel(
                fn (DocumentRequest $record): string =>
                    $record->copy_type === 'soft_copy'
                        ? 'Upload and accept'
                        : 'Accept and schedule'
            )
            ->modalCancelAction(false)
            ->closeModalByClickingAway(false)
            ->closeModalByEscaping(false)
            ->schema(
                fn (DocumentRequest $record): array => $record->copy_type === 'soft_copy'
                    ? [
                        FileUpload::make('file_path')
                            ->label('Requested document')
                            ->multiple()
                            ->appendFiles()
                            ->panelLayout('compact')
                            ->disk('local')
                            ->directory('documents/requested')
                            ->preserveFilenames()
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            ])
                            ->maxSize(5120)
                            ->helperText('Accepted files: PDF or DOCX. Maximum file size: 5 MB.')
                            ->required(),
                    ]
                    : [
                        DatePicker::make('pickup_date')
                            ->label('Pickup date')
                            ->native(false)
                            ->displayFormat('M d, Y')
                            ->minDate(today())
                            ->default(today()->addDay()->toDateString())
                            ->required(),
                        Select::make('pickup_time')
                            ->label('Pickup time')
                            ->options(fn (): array => $this->pickupTimeOptions())
                            ->native(false)
                            ->searchable()
                            ->required(),
                    ]
            )
            ->extraAttributes([
                'class' => 'inline-flex h-9 w-24 items-center justify-center rounded-md border !border-green-200 !bg-green-100 px-3 text-xs font-semibold !text-green-800 transition hover:!bg-green-200 dark:!border-green-800 dark:!bg-green-900/30 dark:!text-green-300 dark:hover:!bg-green-900/50',
            ])
            ->action(function (array $data, ?DocumentRequest $record = null): void {
                if ($record) {
                    $filePath = null;
                    $pickupAt = null;

                    if ($record->copy_type === 'soft_copy') {
                        $filePath = $data['file_path'] ?? [];
                    } else {
                        $pickupAt = \Carbon\Carbon::createFromFormat(
    */
    public function acceptTransitionAction(): Action
    {
        return Action::make('acceptTransition')->label('Accept')->color('success')->button()->size('xs')
            ->extraAttributes(['class' => 'request-review-action inline-flex h-9 items-center justify-center rounded-md border !border-green-200 !bg-green-100 px-3 text-xs font-semibold !text-green-800 transition hover:!bg-green-200 dark:!border-green-800 dark:!bg-green-900/30 dark:!text-green-300 dark:hover:!bg-green-900/50'])
            ->modalHeading(fn (DocumentRequest $record): string => $record->copy_type === 'soft_copy' ? 'Accept Soft Copy Request' : 'Accept Hard Copy Request')
            ->modalDescription(fn (DocumentRequest $record): string => $record->copy_type === 'soft_copy' ? 'Upload the file to complete this soft-copy request.' : 'Set the pickup schedule to make this request ready for pickup.')
            ->modalSubmitActionLabel(fn (DocumentRequest $record): string => $record->copy_type === 'soft_copy' ? 'Upload and Complete' : 'Accept and Schedule')
            ->schema(fn (DocumentRequest $record): array => $record->copy_type === 'soft_copy'
                ? [FileUpload::make('file_path')->label('Requested document')->multiple()->appendFiles()->panelLayout('compact')->disk('local')->directory('documents/requested')->preserveFilenames()->acceptedFileTypes(['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])->maxSize(5120)->required()]
                : $this->pickupScheduleSchema())
            ->action(function (array $data, DocumentRequest $record): void {
                $pickupAt = $record->copy_type === 'soft_copy' ? null : $this->pickupDateTime($data);
                if ($pickupAt?->isPast()) { Notification::make()->title('Invalid pickup schedule')->body('The pickup date and time must be in the future.')->danger()->send(); return; }
                $this->fulfillRequest($record->request_id, $record->copy_type === 'soft_copy' ? ($data['file_path'] ?? null) : null, $pickupAt?->toDateTimeString());
            });
    }

    public function uploadFileAction(): Action
    {
        return Action::make('uploadFile')->label('Upload File')->color('success')->button()
            ->visible(fn (DocumentRequest $record): bool => $record->copy_type === 'soft_copy')
            ->schema([FileUpload::make('file_path')->label('File')->multiple()->appendFiles()->panelLayout('compact')->disk('local')->directory('documents/requested')->preserveFilenames()->acceptedFileTypes(['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])->maxSize(5120)->required()])
            ->action(fn (array $data, DocumentRequest $record) => $this->fulfillRequest($record->request_id, $data['file_path'] ?? null));
    }

    public function schedulePickupAction(): Action
    {
        return Action::make('schedulePickup')->label('Set Schedule')->color('primary')->button()
            ->visible(fn (DocumentRequest $record): bool => $record->copy_type === 'original')
            ->schema($this->pickupScheduleSchema())
            ->action(function (array $data, DocumentRequest $record): void {
                $pickupAt = $this->pickupDateTime($data);
                if ($pickupAt?->isPast()) { Notification::make()->title('Invalid pickup schedule')->body('The pickup date and time must be in the future.')->danger()->send(); return; }
                $this->fulfillRequest($record->request_id, null, $pickupAt?->toDateTimeString());
            });
    }

    public function markClaimedAction(): Action
    {
        return Action::make('markClaimed')->label('Mark as Claimed')->color('success')->button()->requiresConfirmation()
            ->size('sm')
            ->extraAttributes(['class' => 'w-[130px] !h-9 !min-h-9 justify-center rounded-md', 'style' => 'width: 130px; min-width: 130px; height: 36px; min-height: 36px; padding-left: 16px; padding-right: 16px; border-radius: 6px; box-sizing: border-box;'])
            ->visible(fn (DocumentRequest $record): bool => $record->copy_type === 'original')
            ->action(fn (DocumentRequest $record) => $this->markClaimed($record->request_id));
    }

    public function reschedulePickupAction(): Action
    {
        return Action::make('reschedulePickup')->label('Reschedule')->icon('heroicon-o-calendar-days')->color('gray')
            ->visible(fn (DocumentRequest $record): bool => $record->copy_type === 'original')
            ->schema($this->pickupScheduleSchema())
            ->action(function (array $data, DocumentRequest $record): void {
                $pickupAt = $this->pickupDateTime($data);
                if ($pickupAt?->isPast()) { Notification::make()->title('Invalid pickup schedule')->body('The pickup date and time must be in the future.')->danger()->send(); return; }
                $this->reschedulePickup($record->request_id, $pickupAt?->toDateTimeString());
            });
    }

    public function markNoShowAction(): Action
    {
        return Action::make('markNoShow')->label('Mark as No-Show')->icon('heroicon-o-user-minus')->color('gray')
            ->visible(fn (DocumentRequest $record): bool => $this->isPickupOverdue($record))
            ->requiresConfirmation()
            ->modalHeading('Mark Pickup as No-Show')
            ->modalDescription('Confirm that the client did not appear for the scheduled pickup.')
            ->action(fn (DocumentRequest $record) => $this->markNoShow($record->request_id));
    }

    public function replaceFileAction(): Action
    {
        return Action::make('replaceFile')->label('Replace File')->color('warning')->button()
            ->size('sm')
            ->extraAttributes(['class' => 'w-[80px] !h-9 !min-h-9 justify-center rounded-md', 'style' => 'width: 80px; min-width: 80px; height: 36px; min-height: 36px; padding-left: 16px; padding-right: 16px; border-radius: 6px; box-sizing: border-box;'])
            ->visible(fn (DocumentRequest $record): bool => $record->copy_type === 'soft_copy')
            ->schema([FileUpload::make('file_path')->label('Replacement file')->multiple()->appendFiles()->panelLayout('compact')->disk('local')->directory('documents/requested')->preserveFilenames()->acceptedFileTypes(['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])->maxSize(5120)->required()])
            ->action(fn (array $data, DocumentRequest $record) => $this->fulfillRequest($record->request_id, $data['file_path'] ?? null));
    }

    public function restoreRequestAction(): Action
    {
        return Action::make('restoreRequest')->label('Restore Request')->color('success')->button()->requiresConfirmation()
            ->size('sm')
            ->extraAttributes(['class' => 'w-[130px] !h-9 !min-h-9 justify-center rounded-md', 'style' => 'width: 130px; min-width: 130px; height: 36px; min-height: 36px; padding-left: 16px; padding-right: 16px; border-radius: 6px; box-sizing: border-box;'])
            ->action(fn (DocumentRequest $record) => $this->restoreRequest($record->request_id));
    }

    public function historyAction(): Action
    {
        return Action::make('history')->label('History')->icon('heroicon-o-clock')->color('gray')
            ->modalHeading('Request History')->modalSubmitAction(false)->modalCancelActionLabel('Close')
            ->modalContent(fn (DocumentRequest $record) => view('filament.pages.request-history', ['logs' => ActivityLog::with('user')->where('request_id', $record->request_id)->latest()->get()]));
    }
    public function requestOptionsAction(?Action $additionalAction = null, ?Action $secondAdditionalAction = null): ActionGroup
    {
        return ActionGroup::make(array_filter([
            $additionalAction,
            $secondAdditionalAction,
            $this->messageRequestAction(),
            $this->historyAction(),
        ]))->icon('heroicon-m-ellipsis-vertical')->tooltip('More options')->color('gray');
    }


    protected function pickupScheduleSchema(): array
    {
        return [DatePicker::make('pickup_date')->label('Pickup date')->native(false)->displayFormat('M d, Y')->minDate(today())->default(today()->addDay()->toDateString())->required(), Select::make('pickup_time')->label('Pickup time')->options(fn (): array => $this->pickupTimeOptions())->native(false)->searchable()->required()];
    }

    protected function pickupDateTime(array $data): ?\Carbon\Carbon
    {
        return filled($data['pickup_date'] ?? null) && filled($data['pickup_time'] ?? null) ? \Carbon\Carbon::createFromFormat('Y-m-d H:i', $data['pickup_date'] . ' ' . $data['pickup_time']) : null;
    }

    public function acceptRequest(int $requestId): void
    {
        $request = DB::transaction(function () use ($requestId): ?DocumentRequest {
            $request = DocumentRequest::with('user')->lockForUpdate()->findOrFail($requestId);
            if ($request->status !== 'pending') return null;
            $request->update(['status' => 'for_release', 'date_processed' => now()->toDateString()]);
            $this->recordRequestActivity($request, 'request_accepted', 'Accepted the document request.', 'pending', 'for_release');
            return $request->fresh('user');
        });
        if (! $request) return;
        $this->notifyRequester($request, new DocumentRequestAcceptedNotification($request), 'Request accepted', 'The requester has been notified.');
        $this->redirect(self::getUrl(['section' => 'for_release']));
    }

    public function markClaimed(int $requestId): void
    {
        $request = DB::transaction(function () use ($requestId): ?DocumentRequest {
            $request = DocumentRequest::lockForUpdate()->findOrFail($requestId);
            if ($request->status !== 'ready_for_pickup') return null;
            $request->update(['status' => 'completed', 'claimed_at' => now()]);
            $this->recordRequestActivity($request, 'request_claimed', 'Marked the hard copy request as claimed.', 'ready_for_pickup', 'completed');
            return $request;
        });
        if ($request) { Notification::make()->title('Request claimed')->success()->send(); $this->resetTable(); }
    }

    public function reschedulePickup(int $requestId, string $pickupAt): void
    {
        $request = DB::transaction(function () use ($requestId, $pickupAt): ?DocumentRequest {
            $request = DocumentRequest::with(['user', 'document'])->lockForUpdate()->findOrFail($requestId);
            if ($request->status !== 'ready_for_pickup') return null;
            $old = $request->pickup_at?->toDateTimeString();
            $request->update(['pickup_at' => $pickupAt]);
            $this->recordRequestActivity($request, 'pickup_rescheduled', 'Rescheduled the pickup time.', $old, $pickupAt);
            return $request->fresh(['user', 'document']);
        });
        if ($request) { $this->notifyRequester($request, new DocumentRequestFulfilledNotification($request, $request->document), 'Pickup rescheduled', 'The requester has been notified of the new pickup schedule.'); $this->resetTable(); }
    }

    public function markNoShow(int $requestId): void
    {
        $request = DB::transaction(function () use ($requestId): ?DocumentRequest {
            $request = DocumentRequest::lockForUpdate()->findOrFail($requestId);

            if (! $this->isPickupOverdue($request)) {
                return null;
            }

            $pickupAt = $request->pickup_at->toDateTimeString();
            $this->recordRequestActivity(
                $request,
                'pickup_no_show',
                'Client did not appear for the scheduled pickup.',
                $pickupAt,
                null,
            );

            return $request;
        });

        if ($request) {
            Notification::make()->title('Pickup marked as no-show')->success()->send();
            $this->resetTable();
        }
    }

    public function restoreRequest(int $requestId): void
    {
        $request = DB::transaction(function () use ($requestId): ?DocumentRequest {
            $request = DocumentRequest::lockForUpdate()->findOrFail($requestId);
            if ($request->status !== 'rejected') return null;
            $request->update(['status' => 'pending', 'date_processed' => null, 'rejection_reason' => null]);
            $this->recordRequestActivity($request, 'request_restored', 'Restored the request to Pending.', 'rejected', 'pending');
            return $request;
        });
        if ($request) $this->redirect(self::getUrl(['section' => 'pending']));
    }

    protected function recordRequestActivity(DocumentRequest $request, string $actionType, string $details, ?string $old = null, ?string $new = null): void
    {
        ActivityLog::create(['user_id' => auth()->id(), 'document_id' => $request->document_id, 'request_id' => $request->request_id, 'action_type' => $actionType, 'action_details' => $details, 'old_value' => $old, 'new_value' => $new]);
    }

    protected function notifyRequester(DocumentRequest $request, object $notification, string $title, string $body): void
    {
        try { $request->user?->notify($notification); } catch (TransportExceptionInterface $exception) { report($exception); }
        Notification::make()->title($title)->body($body)->success()->send();
    }

    /*
                            'Y-m-d H:i',
                            $data['pickup_date'] . ' ' . $data['pickup_time']
                        );

                        if ($pickupAt->isPast()) {
                            Notification::make()
                                ->title('Invalid pickup schedule')
                                ->body('The pickup date and time must be in the future.')
                                ->danger()
                                ->send();

                            return;
                        }
                    }

                    $this->fulfillRequest($record->request_id, $filePath, $pickupAt?->toDateTimeString());
                }
            });
    }

    */
    public function rejectRequestAction(): Action
    {
        return Action::make('rejectRequest')
            ->label('Reject')
            ->color('danger')
            ->button()
            ->size('xs')
            ->extraAttributes(['class' => 'request-review-action inline-flex h-9 items-center justify-center rounded-md border !border-red-200 !bg-red-100 px-3 text-xs font-semibold !text-red-800 transition hover:!bg-red-200 dark:!border-red-800 dark:!bg-red-900/30 dark:!text-red-300 dark:hover:!bg-red-900/50'])
            ->modalHeading('Reject Document Request')
            ->modalIcon('heroicon-o-x-circle')
            ->modalIconColor('danger')
            ->modalDescription('Please state the reason for rejecting this request.')
            ->modalAlignment(\Filament\Support\Enums\Alignment::Center)
            ->modalFooterActionsAlignment(\Filament\Support\Enums\Alignment::Center)
            ->modalSubmitActionLabel('Reject request')
            ->modalCancelAction(false)
            ->closeModalByClickingAway(false)
            ->closeModalByEscaping(false)
            ->schema([
                Textarea::make('rejection_reason')
                    ->label('Reason for rejection')
                    ->placeholder('Explain why this request cannot be fulfilled...')
                    ->rows(5)
                    ->required()
                    ->maxLength(1000),
            ])
            ->extraAttributes([
                'class' => 'inline-flex h-9 w-24 items-center justify-center rounded-md border !border-red-200 !bg-red-100 px-3 text-xs font-semibold !text-red-800 transition hover:!bg-red-200 dark:!border-red-800 dark:!bg-red-900/30 dark:!text-red-300 dark:hover:!bg-red-900/50',
            ])
            ->action(function (array $data, ?DocumentRequest $record = null): void {
                if ($record) {
                    $this->rejectRequest($record->request_id, trim($data['rejection_reason']));
                }
            });
    }

    public function messageRequestAction(): Action
    {
        return Action::make('messageRequest')
            ->label('Message')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->color('gray')
            ->tooltip('Message requester')
            ->url(
                fn (DocumentRequest $record): string => Messages::getUrl([
                    'request' => $record->request_id,
                ])
            )
            ->extraAttributes(['class' => 'documents-table-action mx-auto']);
    }

    public function fulfillRequest(
        int $requestId,
        string|array|null $filePath = null,
        ?string $pickupAt = null
    ): void
    {
        $filePaths = array_values(array_filter(
            is_array($filePath) ? $filePath : [$filePath],
            static fn (mixed $path): bool => is_string($path) && filled($path),
        ));
        $fileHashes = array_map(
            static fn (string $path): ?string => DocumentVersion::hashForUpload($path),
            $filePaths,
        );

        if ($filePaths !== [] && (
            in_array(null, $fileHashes, true)
            || count($fileHashes) !== count(array_unique($fileHashes))
        )) {
            foreach ($filePaths as $path) {
                DocumentVersion::removeUnreferencedUpload($path);
            }

            Notification::make()
                ->title('Upload could not be verified')
                ->body('One or more selected files could not be read or are duplicates. Please remove the invalid files and try again.')
                ->danger()
                ->send();

            return;
        }

        $duplicateUpload = false;
        $result = DB::transaction(function () use ($requestId, $filePaths, $fileHashes, $pickupAt, &$duplicateUpload): ?array {
            $request = DocumentRequest::query()
                ->with(['document', 'user'])
                ->lockForUpdate()
                ->findOrFail($requestId);

            if (! in_array($request->status, ['pending', 'for_release', 'completed'], true)) {
                return null;
            }

            if ($request->copy_type === 'soft_copy' && $filePaths === []) {
                return null;
            }

            if ($request->copy_type !== 'soft_copy' && blank($pickupAt)) {
                return null;
            }

            if ($request->copy_type === 'soft_copy' && DocumentVersion::query()
                ->whereIn('file_hash', $fileHashes)
                ->where('user_id', auth()->id())
                ->exists()) {
                $duplicateUpload = true;

                return null;
            }

            $document = $request->document;

            if (! $document) {
                $document = Document::create([
                    'user_id' => $request->user_id,
                    'document_type' => $request->purpose,
                    'description' => $request->purpose_details,
                    'particulars' => $request->purpose_details ?: $request->purpose,
                    'status' => 'pending',
                ]);

                $request->update([
                    'document_id' => $document->document_id,
                ]);
            }

            if ($request->copy_type === 'soft_copy' && DocumentVersion::query()
                ->whereIn('file_hash', $fileHashes)
                ->where('document_id', $document->document_id)
                ->exists()) {
                $duplicateUpload = true;

                return null;
            }

            $document->status = 'in_progress';
            $document->deadline = Document::deadlineForType($document->document_type);

            if ($filePaths !== []) {
                $document->document_name = basename($filePaths[0]);
            }

            $document->save();

            if ($request->copy_type === 'soft_copy') {
                $highestVersion = $document->versions()
                    ->get()
                    ->map(fn (DocumentVersion $version): int => (int) $version->version_number)
                    ->max() ?? 0;

                foreach ($filePaths as $index => $path) {
                    DocumentVersion::create([
                        'document_id' => $document->document_id,
                        'user_id' => auth()->id(),
                        'version_number' => (string) ($highestVersion + $index + 1),
                        'file_path' => $path,
                        'file_hash' => $fileHashes[$index],
                        'source' => 'admin',
                    ]);
                }
            }

            $oldStatus = $request->status;
            $request->update([
                'status' => $request->copy_type === 'soft_copy' ? 'completed' : 'ready_for_pickup',
                'date_processed' => now()->toDateString(),
                'pickup_at' => $request->copy_type !== 'soft_copy'
                    ? $pickupAt
                    : null,
                'attachment_path' => $request->copy_type === 'soft_copy' ? json_encode($filePaths) : $request->attachment_path,
            ]);
            if ($oldStatus === 'pending') {
                $this->recordRequestActivity($request, 'request_accepted', 'Accepted the document request.', 'pending', $request->status);
            }
            $this->recordRequestActivity(
                $request,
                $request->copy_type === 'soft_copy' ? ($oldStatus === 'completed' ? 'soft_copy_replaced' : 'soft_copy_uploaded') : 'pickup_scheduled',
                $request->copy_type === 'soft_copy' ? 'Uploaded a soft copy file.' : 'Scheduled the pickup.',
                $oldStatus,
                $request->status,
            );

            if ($request->copy_type !== 'soft_copy' && $pickupAt) {
                $pickupDateTime = \Carbon\Carbon::parse($pickupAt);
                $requesterName = $request->user?->name ?? 'Client';
                $pickupPurpose = strtolower(trim((string) $request->purpose));
                $pickupPurpose = match ($pickupPurpose) {
                    'certificate', 'certificate_request' => 'Certificate',
                    'template', 'template_request' => 'Template',
                    'document', 'document_request' => 'Document',
                    default => $pickupPurpose !== ''
                        ? ucwords(str_replace(['_', '-'], ' ', $pickupPurpose))
                        : 'Document',
                };
                $details = 'Document pickup for ' . $requesterName . '.';

                if (filled($request->purpose_details)) {
                    $details .= "\nDetails: " . $request->purpose_details;
                }

                CalendarModel::create([
                    'user_id' => auth()->id(),
                    'document_request_id' => $request->request_id,
                    'date' => $pickupDateTime->toDateString(),
                    'time' => $pickupDateTime->format('H:i:s'),
                    'event' => 'Document pickup: ' . $pickupPurpose,
                    'category' => 'pickup',
                    'details' => $details,
                ]);
            }

            Conversation::query()
                ->where('document_request_id', $request->request_id)
                ->update([
                    'document_id' => $document->document_id,
                ]);

            return [
                'request' => $request->fresh([
                    'user',
                    'document.user',
                ]),
                'document' => $document->fresh('user'),
            ];
        });

        if (! $result) {
            foreach ($filePaths as $path) {
                DocumentVersion::removeUnreferencedUpload($path);
            }

            if ($duplicateUpload) {
                Notification::make()
                    ->title('Duplicate document detected')
                    ->body('One or more selected files have already been uploaded. Please choose different files.')
                    ->danger()
                    ->send();

                return;
            }

            Notification::make()
                ->title('Unable to fulfill request')
                ->danger()
                ->send();

            return;
        }

        $request = $result['request'];
        $document = $result['document'];
        $pickupMessage = $request->pickup_at
            ? 'Your requested original document has been scheduled for pickup on ' . $request->pickup_at->format('F d, Y g:i A') . '.'
            : 'Your requested original document has a pickup date scheduled.';

        // Request notifications must always go to the requester.
        $client = $request->user;
        $emailFailed = false;

        if ($client) {
            try {
                $client->notify(
                    new DocumentRequestFulfilledNotification($request, $document)
                );
            } catch (TransportExceptionInterface $exception) {
                report($exception);
                $emailFailed = true;
            }

            Notification::make()
                ->title($request->copy_type === 'soft_copy'
                    ? 'Requested document is ready'
                    : 'Original document pickup scheduled')
                ->body($request->copy_type === 'soft_copy'
                    ? 'The requested soft copy is ready to view and download.'
                    : $pickupMessage)
                ->success()
                ->actions([
                    Action::make('viewRequestedDocument')
                        ->label('View request')
                        ->url(
                            \App\Filament\Client\Pages\ViewDocument::getUrl([
                                'document' => $document->public_id,
                                'from' => 'documents',
                                'tab' => 'requested',
                            ])
                        )
                        ->button(),
                ])
                ->sendToDatabase($client);
        }

        // ADMIN TOAST
        $notification = Notification::make()
            ->title($emailFailed ? 'Request fulfilled, but email failed' : 'Request fulfilled')
            ->body($emailFailed
                ? 'The request was fulfilled, but the email notification could not be sent. Please check the mail server connection.'
                : ($request->copy_type === 'soft_copy'
                    ? 'The requester has been notified.'
                    : 'The requester has been notified and the pickup was added to your calendar.'));

        if ($emailFailed) {
            $notification->warning();
        } else {
            $notification->success();
        }

        $notification->send();
        $this->resetTable();
    }

    public function rejectRequest(int $requestId, string $reason): void
    {
        $result = DB::transaction(function () use ($requestId, $reason): ?DocumentRequest {
            $request = DocumentRequest::query()
                ->with('user')
                ->lockForUpdate()
                ->findOrFail($requestId);

            if ($request->status !== 'pending') {
                return null;
            }

            $request->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'date_processed' => now()->toDateString(),
            ]);
            $this->recordRequestActivity($request, 'request_rejected', 'Rejected the document request.', 'pending', 'rejected');

            return $request->fresh('user');
        });

        if (! $result) {
            Notification::make()
                ->title('Unable to reject request')
                ->danger()
                ->send();

            return;
        }

        $emailFailed = false;

        if ($result->user) {
            try {
                $result->user->notify(
                    new DocumentRequestRejectedNotification($result, $reason)
                );
            } catch (TransportExceptionInterface $exception) {
                report($exception);
                $emailFailed = true;
            }
        }

        $notification = Notification::make()
            ->title($emailFailed ? 'Request rejected, but email failed' : 'Request rejected')
            ->body($emailFailed
                ? 'The request was rejected, but the email notification could not be sent.'
                : 'The requester has been notified with the rejection reason.');

        $emailFailed
            ? $notification->warning()
            : $notification->success();

        $notification->send();

        $this->redirect(
            self::getUrl([
                'section' => 'rejected',
            ])
        );
    }

    public function returnRequestAction(): Action
    {
        return Action::make('returnRequest')
            ->label('Return')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('success')
            ->tooltip('Return Document')
            ->requiresConfirmation()
            ->modalHeading('Return Document Request')
            ->modalDescription('Are you sure you want to return this request to pending?')
            ->modalSubmitActionLabel('Return')
            ->modalCancelActionLabel('Cancel')
            ->extraAttributes([
                'class' => 'return-document-button',
            ])
            ->action(function (array $arguments, ?DocumentRequest $record = null): void {
                $requestId = $record?->request_id ?? ($arguments['request'] ?? null);

                if ($requestId !== null) {
                    $this->returnRequest((int) $requestId);
                }
            });
    }

    /**
     * RETURN REQUEST TO PENDING
     */
    public function returnRequest(int $requestId): void
    {
        $request = DocumentRequest::with('document')
            ->findOrFail($requestId);

        $request->update([
            'status' => 'pending',
            'date_processed' => null,
            'rejection_reason' => null,
        ]);

        /*
         * Optional:
         * If a rejected request is returned to pending,
         * reset its document status too.
         */
        if (
            $request->document &&
            $request->document->status === 'rejected'
        ) {
            $request->document->update([
                'status' => 'pending',
                'rejection_reason' => null,
            ]);
        }

        $this->redirect(
            self::getUrl([
                'section' => 'pending',
            ])
        );
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

    public function clearRequestFilters(): void
    {
        $this->typeFilter = '';
        $this->actionTypeFilter = '';
        $this->officeUnitFilter = '';
        $this->resetPage();
    }

    public function applyRequestFilters(): void
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

}
