<?php

namespace App\Filament\Pages;

use App\Models\Document;
use App\Models\DocumentRequest;
use App\Notifications\DocumentAcceptedNotification;
use App\Notifications\DocumentRejectedNotification;
use Filament\Actions\Action;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
// use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class DocumentRequests extends Page implements HasTable
{
    use InteractsWithTable;
   // use HasPageShield;

    protected static ?int $navigationSort = 3;

    protected static string|\BackedEnum|null $navigationIcon =
        'heroicon-o-document-plus';

    protected static ?string $navigationLabel = 'Requests';

    protected static ?string $title = 'Document Requests';

    protected string $view = 'filament.pages.document-requests';

    public string $activeSection = 'pending';

    public string $search = '';

    public string $typeFilter = '';

    public string $dateFilter = '';

    public function mount(): void
    {
        $section = request()->query('section', 'pending');

        $this->activeSection = in_array(
            $section,
            [
                'pending',
                'accepted',
                'rejected',
            ],
            true
        ) ? $section : 'pending';
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
            'pending' => (int) ($counts['pending'] ?? 0),
            'accepted' => (int) ($counts['accepted'] ?? 0),
            'rejected' => (int) ($counts['rejected'] ?? 0),
        ];
    }

    protected function getDocumentRequestTableQuery(): Builder
    {
        $status = match ($this->activeSection) {
            'accepted' => 'accepted',
            'rejected' => 'rejected',
            default => 'pending',
        };

        return DocumentRequest::query()
            ->with([
                'document.user',
                'document.latestVersion',
                'user',
            ])
            ->where('status', $status)
            ->when(trim($this->search) !== '', function (Builder $query): void {
                $search = '%' . trim($this->search) . '%';

                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->whereHas('document', function (Builder $query) use ($search): void {
                            $query
                                ->where('lao_number', 'like', $search)
                                ->orWhere('office_unit', 'like', $search)
                                ->orWhere('particulars', 'like', $search);
                        })
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
            ->recordUrl(fn (DocumentRequest $record): string => ViewDocument::getUrl([
                'document' => $record->document_id,
            ]))
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
                ->label('DOCUMENT')
                ->view('filament.tables.columns.request-document-details')
                ->width('20rem')
                ->extraHeaderAttributes(['class' => 'min-w-[240px]']),

            TextColumn::make('purpose')
                ->label('PURPOSE')
                ->placeholder('—')
                ->wrap()
                ->extraHeaderAttributes(['class' => 'min-w-[220px]']),

            ViewColumn::make('document_type')
                ->label('DOCUMENT TYPE')
                ->view('filament.tables.columns.request-document-type')
                ->alignCenter()
                ->width('11rem')
                ->extraHeaderAttributes(['class' => 'min-w-[160px]']),

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

        if ($this->activeSection !== 'pending') {
            $columns[] = TextColumn::make('date_processed')
                ->label('DATE ' . strtoupper($this->activeSection))
                ->date('F d, Y')
                ->placeholder('Unknown date')
                ->alignCenter()
                ->extraHeaderAttributes(['class' => 'min-w-[150px]']);
        }

        return $columns;
    }

    protected function getDocumentRequestTableActions(): array
    {
        if ($this->activeSection === 'pending') {
            return [
                $this->acceptRequestAction(),
                $this->rejectRequestAction(),
            ];
        }

        return [
            $this->returnRequestAction()->button(),
        ];
    }

    public function acceptRequestAction(): Action
    {
        return Action::make('acceptRequest')
            ->label('Accept')
            ->color('success')
            ->button()
            ->requiresConfirmation()
            ->modalHeading('Accept Document Request')
            ->modalIcon('heroicon-o-check-circle')
            ->modalIconColor('success')
            ->modalDescription('Are you sure you want to accept this document request? The requester will be granted access to view the document.')
            ->modalAlignment(\Filament\Support\Enums\Alignment::Center)
            ->modalFooterActionsAlignment(\Filament\Support\Enums\Alignment::Center)
            ->modalSubmitActionLabel('Accept')
            ->modalCancelActionLabel('Cancel')
            ->extraAttributes([
                'class' => 'inline-flex h-9 items-center justify-center rounded-md bg-green-600 px-3 text-xs font-semibold text-white transition hover:bg-green-700',
            ])
            ->action(function (array $arguments, ?DocumentRequest $record = null): void {
                $requestId = $record?->request_id ?? ($arguments['request'] ?? null);

                if ($requestId !== null) {
                    $this->acceptRequest((int) $requestId);
                }
            });
    }

    /**
     * ACCEPT REQUEST
     */
    public function acceptRequest(int $requestId): void
    {
        $result = DB::transaction(function () use ($requestId) {

            $request = DocumentRequest::query()
                ->with([
                    'document.user',
                    'user',
                ])
                ->lockForUpdate()
                ->findOrFail($requestId);

            if ($request->status !== 'pending') {
                return null;
            }

            $document = $request->document;

            if (!$document) {
                return null;
            }

            /*
             * Only generate an LAO number if this document
             * doesn't already have one.
             */
            if (!$document->lao_number) {
                $document->lao_number = Document::generateLaoNumber();
            }

            $document->status = 'in_progress';
            $document->deadline = Document::deadlineForType($document->document_type);
            $document->save();

            $request->update([
                'status' => 'accepted',
                'date_processed' => now()->toDateString(),
            ]);

            return [
                'request' => $request->fresh([
                    'user',
                    'document.user',
                ]),
                'document' => $document->fresh('user'),
            ];
        });

        if (!$result) {
            Notification::make()
                ->title('Unable to accept request')
                ->danger()
                ->send();

            return;
        }

        $request = $result['request'];
        $document = $result['document'];

        /*
         * Prefer the user attached to the request.
         * Fall back to the document owner.
         */
        $client = $request->user ?? $document->user;
        $emailFailed = false;

        if ($client) {

            // EMAIL
            try {
                $client->notify(
                    new DocumentAcceptedNotification($document)
                );
            } catch (TransportExceptionInterface $exception) {
                report($exception);
                $emailFailed = true;
            }

            // CLIENT FILAMENT BELL
            Notification::make()
                ->title('Document Accepted')
                ->body(
                    'Your requested document has been accepted. Your document QR code is ready. Open it below and scan it to track the document status.'
                )
                ->success()
                ->actions([
                    Action::make('viewDocumentQrCode')
                        ->label('View QR code')
                        ->icon('heroicon-o-qr-code')
                        ->url(\Illuminate\Support\Facades\URL::signedRoute('documents.qr', [
                            'document' => $document->document_id,
                        ]))
                        ->openUrlInNewTab()
                        ->button(),
                    Action::make('viewAcceptedDocument')
                        ->label('View document')
                        ->url(
                            \App\Filament\Client\Pages\ViewDocument::getUrl([
                                'document' => $document->document_id,
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
            ->title($emailFailed ? 'Document accepted, but email failed' : 'Document accepted')
            ->body(
                'Assigned LAO Number: ' .
                $document->lao_number .
                ($emailFailed ? '. The email notification could not be sent. Please contact your administrator to check the mail server connection.' : '')
            );

        if ($emailFailed) {
            $notification->warning();
        } else {
            $notification->success();
        }

        $notification->send();

        $this->redirect(
            self::getUrl([
                'section' => 'accepted',
            ])
        );
    }

    /**
     * REJECT REQUEST WITH REASON
     */
    public function rejectRequestAction(): Action
    {
        return Action::make('rejectRequest')
            ->label('Reject')
            ->color('danger')
            ->button()
            ->size('xs')
            ->extraAttributes([
                'class' => 'inline-flex h-9 items-center justify-center rounded-md bg-red-600 px-3 text-xs font-semibold text-white transition hover:bg-red-700',
            ])
            ->modalHeading('Reject Document Request')
            ->modalIcon('heroicon-o-x-circle')
            ->modalIconColor('danger')
            ->modalDescription(
                'Please provide the reason why this document is being rejected.'
            )
            ->modalAlignment(\Filament\Support\Enums\Alignment::Center)
            ->modalFooterActionsAlignment(\Filament\Support\Enums\Alignment::Center)
            ->modalSubmitActionLabel('Reject request')
            ->modalCancelActionLabel('Cancel')
            ->schema([
                Textarea::make('rejection_reason')
                    ->label('Reason for Rejection')
                    ->placeholder(
                        'e.g., Incomplete supporting documents, incorrect document type...'
                    )
                    ->rows(5)
                    ->required()
                    ->maxLength(1000),
            ])
            ->action(function (
                array $data,
                array $arguments,
                ?DocumentRequest $record = null
            ): void {

                $request = ($record ?? DocumentRequest::query()
                    ->with([
                        'document.user',
                        'user',
                    ])
                    ->findOrFail(
                        $arguments['request'] ?? null
                    ))->load([
                        'document.user',
                        'user',
                    ]);

                $document = $request->document;

                if (!$document) {
                    Notification::make()
                        ->title('Document not found')
                        ->danger()
                        ->send();

                    return;
                }

                DB::transaction(function () use (
                    $request,
                    $document,
                    $data
                ) {
                    $document->update([
                        'status' => 'rejected',
                        'rejection_reason' =>
                            $data['rejection_reason'],
                    ]);

                    $request->update([
                        'status' => 'rejected',
                        'date_processed' =>
                            now()->toDateString(),
                    ]);
                });

                $client =
                    $request->user ??
                    $document->user;

                if ($client) {

                    // EMAIL
                    $client->notify(
                        new DocumentRejectedNotification(
                            $document
                        )
                    );
                }

                // ADMIN TOAST
                Notification::make()
                    ->title('Document rejected')
                    ->body(
                        'The client has been notified by email and in-app notification.'
                    )
                    ->success()
                    ->send();

                $this->redirect(
                    self::getUrl([
                        'section' => 'rejected',
                    ])
                );
            });
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

    public function updatedDateFilter(): void
    {
        $this->resetPage();
    }

}
