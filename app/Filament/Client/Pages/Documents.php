<?php

namespace App\Filament\Client\Pages;

use App\Filament\Client\Pages\Messages as ClientMessages;
use App\Filament\Client\Pages\ViewDocument;
use App\Models\Document;
use App\Models\DocumentRequest;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Illuminate\Database\Eloquent\Builder;

class Documents extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Documents';
    protected static ?string $title = 'Documents';
    protected static ?string $slug = 'documents';

    protected string $view = 'filament.client.pages.documents'; 

    public string $activeTab = 'all'; 
    public ?int $highlightedDocumentId = null;
    public string $documentSearch = '';
    public string $documentType = '';
    public string $documentStatus = '';

    public function getHeading(): string
    {
        return '';
    }

    public function getAllDocumentsCount(): int
    {
        return Document::query()
            ->where('user_id', auth()->id())
            ->whereDoesntHave(
                'documentRequests',
                fn (Builder $requestQuery) => $requestQuery
                    ->where('user_id', auth()->id())
            )
            ->count();
    }

    public function mount(): void
    {
        $tab = request()->query('tab', 'all');
        $document = request()->query('document');

        $this->activeTab = in_array($tab, [
            'all',
            'pending',
            'in_progress',
            'completed',
            'rejected',
            'requested',
        ], true) ? $tab : 'all';

        $this->highlightedDocumentId = filled($document)
            ? Document::query()
                ->where('public_id', $document)
                ->where(function (Builder $query): void {
                    $query
                        ->where('user_id', auth()->id())
                        ->orWhereHas(
                            'documentRequests',
                            fn (Builder $requestQuery) => $requestQuery
                                ->where('user_id', auth()->id())
                        );
                })
                ->value('document_id')
            : null;
    }

    // This method sets the tab AND instantly refreshes the table data
    public function updateTab($tab)
    {
        if ($tab !== $this->activeTab) {
            $this->documentType = '';
        }

        $this->activeTab = $tab;

        if ($tab !== 'requested') {
            $this->documentStatus = '';
        }

        $this->resetTable(); 
    }

    public function updatedDocumentSearch(): void
    {
        $this->resetTable();
    }

    public function updatedDocumentType(): void
    {
        $this->resetTable();
    }

    public function updatedDocumentStatus(): void
    {
        $this->resetTable();
    }

    public function clearSearch(): void
    {
        $this->documentSearch = '';
        $this->resetTable();
    }

    public function clearType(): void
    {
        $this->documentType = '';
        $this->resetTable();
    }

    public function clearStatus(): void
    {
        $this->documentStatus = '';
        $this->resetTable();
    }

    protected function documentsQuery(): Builder
    {
        $query = Document::query();

        if ($this->activeTab === 'all') {
            $query
                ->where('user_id', auth()->id())
                ->whereDoesntHave(
                    'documentRequests',
                    fn (Builder $requestQuery) => $requestQuery
                        ->where('user_id', auth()->id())
                );
        } else {
            $query->where('user_id', auth()->id());

            if ($this->activeTab === 'in_progress') {
                $query
                    ->whereIn('status', [
                        'in_progress',
                        'outgoing',
                    ])
                    ->whereDoesntHave(
                        'documentRequests',
                        fn (Builder $requestQuery) => $requestQuery
                            ->where('user_id', auth()->id())
                    );
            } elseif ($this->activeTab === 'completed') {
                $query->whereIn('status', [
                    'completed',
                    'archived',
                ]);
            } else {
                $query->where('status', $this->activeTab);
            }

            if ($this->activeTab === 'rejected') {
                $query->with([
                    'rejections' => fn ($rejectionQuery) => $rejectionQuery
                        ->latest('created_at')
                        ->latest('rejected_id'),
                ]);
            }
        }

        return $query
            ->when(
                $this->documentType !== '',
                fn ($query) => $query->where(
                    'document_type',
                    $this->documentType
                )
            )
            ->when(
                trim($this->documentSearch) !== '',
                function ($query) {
                    $search = trim($this->documentSearch);

                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('particulars', 'like', "%{$search}%")
                            ->orWhere('office_unit', 'like', "%{$search}%")
                            ->orWhere('lao_number', 'like', "%{$search}%");
                    });
                }
            );
    }

    protected function requestedDocumentsQuery(): Builder
    {
        return DocumentRequest::query()
            ->where('user_id', auth()->id())
            ->with([
                'document.latestVersion',
            ])
            ->when(
                $this->documentStatus !== '',
                fn (Builder $query) => $query->where(
                    'status',
                    $this->documentStatus
                )
            )
            ->when(
                $this->documentType !== '',
                fn (Builder $query) => $query->where(
                    'copy_type',
                    $this->documentType
                )
            )
            ->when(
                trim($this->documentSearch) !== '',
                function (Builder $query): void {
                    $search = trim($this->documentSearch);

                    $query->where(function (Builder $query) use ($search): void {
                        $query
                            ->where('purpose', 'like', "%{$search}%")
                            ->orWhere('purpose_details', 'like', "%{$search}%")
                            ->orWhereHas('document', function (Builder $documentQuery) use ($search): void {
                                $documentQuery
                                    ->where('particulars', 'like', "%{$search}%")
                                    ->orWhere('office_unit', 'like', "%{$search}%")
                                    ->orWhere('lao_number', 'like', "%{$search}%");
                            });
                    });
                }
            )
            ->latest('date_of_request')
            ->latest('request_id');
    }

    protected function hasDocumentsForCurrentTable(): bool
    {
        return $this->documentsQuery()->exists();
    }

    protected function clientCanAccessFile(Document $record): bool
    {
        if ((int) $record->user_id === (int) auth()->id()) {
            return true;
        }

        return $record->documentRequests()
            ->where('user_id', auth()->id())
            ->where('status', 'accepted')
            ->exists();
    }

    public function table(Table $table): Table
    {
        if ($this->activeTab === 'requested') {
            return $this->requestedTable($table);
        }

        return $this->documentsTable($table);
    }

    protected function requestedTable(Table $table): Table
    {
        return $table
            ->query($this->requestedDocumentsQuery())
            ->recordUrl(
                fn (DocumentRequest $record): ?string => $record->status === 'accepted' &&
                    $record->copy_type === 'soft_copy' &&
                    filled($record->document?->latestVersion?->file_path)
                    ? ViewDocument::getUrl([
                        'document' => $record->document?->getPublicRouteKey() ?: $record->document_id,
                        'from' => 'documents',
                        'tab' => 'requested',
                    ])
                    : null
            )
            ->recordClasses(
                fn (DocumentRequest $record): string => $this->highlightedDocumentId !== null &&
                    (int) $record->document_id === $this->highlightedDocumentId
                    ? 'document-highlighted'
                    : ''
            )
            ->columns([
                ViewColumn::make('document_icon')
                    ->label('')
                    ->view('filament.tables.columns.request-document-icon')
                    ->alignCenter()
                    ->width('5rem')
                    ->extraHeaderAttributes(['class' => 'w-20']),

                ViewColumn::make('document_details')
                    ->label('PURPOSE')
                    ->view('filament.tables.columns.request-document-purpose')
                    ->width('13rem')
                    ->alignLeft()
                    ->extraCellAttributes(['class' => 'text-left'])
                    ->extraHeaderAttributes(['class' => 'min-w-[170px]']),

                TextColumn::make('purpose_details')
                    ->label('DETAILS')
                    ->placeholder('—')
                    ->width('26rem')
                    ->extraHeaderAttributes(['class' => 'min-w-[320px]'])
                    ->wrap(),

                TextColumn::make('copy_type')
                    ->label('TYPE')
                    ->formatStateUsing(
                        fn (?string $state): string => match ($state) {
                            'original' => 'Original',
                            'soft_copy' => 'Soft copy',
                            default => '—',
                        }
                    )
                    ->alignLeft(),

                TextColumn::make('pickup_at')
                    ->label('PICKUP')
                    ->state(
                        fn (DocumentRequest $record): string =>
                            $record->pickup_at
                                ? $record->pickup_at->format('M d, Y|g:i A')
                                : '—'
                    )
                    ->formatStateUsing(
                        fn (?string $state): string => str_replace('|', '<br>', e($state ?? '—'))
                    )
                    ->html()
                    ->alignCenter()
                    ->width('12rem'),

                TextColumn::make('date_of_request')
                    ->label('DATE OF REQUEST')
                    ->date('M d, Y')
                    ->placeholder('—')
                    ->alignCenter(),

                TextColumn::make('status')
                    ->label('STATUS')
                    ->formatStateUsing(
                        fn (?string $state): string => blank($state)
                            ? '—'
                            : ucfirst((string) $state)
                    )
                    ->color(
                        fn (string $state): string => match (strtolower($state)) {
                            'pending' => 'warning',
                            'accepted' => 'success',
                            'rejected' => 'danger',
                            default => 'gray',
                        }
                    ),

                TextColumn::make('rejection_reason')
                    ->label('REASON')
                    ->placeholder('—')
                    ->width('14rem')
                    ->wrap(),

                TextColumn::make('empty_actions_placeholder')
                    ->label('ACTIONS')
                    ->state('')
                    ->alignEnd()
                    ->visible(fn (): bool => ! $this->requestedDocumentsQuery()->exists()),
            ])
            ->striped()
            ->recordActionsAlignment('end')
            ->recordActions([
                Action::make('print')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->iconButton()
                    ->extraAttributes(['class' => 'documents-table-action'])
                    ->tooltip('Print')
                    ->url(
                        fn (DocumentRequest $record): string => route(
                            'client.document.preview',
                            ['document' => $record->document?->getPublicRouteKey() ?: $record->document_id]
                        )
                    )
                    ->visible(
                        fn (DocumentRequest $record): bool => $record->status === 'accepted'
                            && $record->copy_type === 'soft_copy'
                            && filled($record->document?->latestVersion?->file_path)
                    )
                    ->openUrlInNewTab(),

                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->iconButton()
                    ->extraAttributes(['class' => 'documents-table-action'])
                    ->tooltip('Download')
                    ->url(
                        fn (DocumentRequest $record): string => route(
                            'client.document.download',
                            ['document' => $record->document?->getPublicRouteKey() ?: $record->document_id]
                        )
                    )
                    ->visible(
                        fn (DocumentRequest $record): bool => $record->status === 'accepted'
                            && $record->copy_type === 'soft_copy'
                            && filled($record->document?->latestVersion?->file_path)
                    )
                    ->openUrlInNewTab(),

                Action::make('message')
                    ->label('Message')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('gray')
                    ->iconButton()
                    ->extraAttributes(['class' => 'documents-table-action'])
                    ->tooltip('Message')
                    ->url(
                        fn (DocumentRequest $record): string => ClientMessages::getUrl([
                            'request' => $record->request_id,
                        ])
                    ),
            ])
            ->recordActionsColumnLabel('ACTIONS');
    }

    protected function documentsTable(Table $table): Table
    {
        return $table
            ->query(
                $this->documentsQuery()
                    ->latest()
            )
            ->recordUrl(
                fn (Document $record): string => ViewDocument::getUrl([
                    'document' => $record->getPublicRouteKey(),
                    'from' => 'documents',
                    'tab' => $this->activeTab,
                ])
            )
            ->recordClasses(
                fn (Document $record): string => $this->highlightedDocumentId !== null &&
                    (int) $record->document_id === $this->highlightedDocumentId
                    ? 'document-highlighted'
                    : ''
            )
            ->columns([
                TextColumn::make('lao_number')
                    ->label('LAO #')
                    ->visible(fn (): bool => $this->activeTab !== 'rejected')
                    ->state(
                        fn (Document $record): string => filled($record->lao_number)
                            ? (string) $record->lao_number
                            : '—'
                    )
                    ->alignCenter(),

                TextColumn::make('document_type')
                    ->label('TYPE')
                    ->placeholder('—')
                    ->alignLeft(),

                TextColumn::make('particulars')
                    ->label('DOCUMENT DESCRIPTION')
                    ->state(fn (Document $record): string => (string) (
                        $record->particulars ?: $record->description ?: '—'
                    ))
                    ->alignStart(),

                TextColumn::make('created_at')
                    ->label('DATE SUBMITTED')
                    ->date('M d, Y')
                    ->placeholder('—')
                    ->alignCenter(),

                TextColumn::make('status')
                    ->label('STATUS')
                    ->state(
                        fn (Document $record): string => $this->activeTab === 'requested'
                            ? (string) ($record->documentRequests->first()?->status ?? '')
                            : (string) $record->status
                    )
                    ->color(
                        fn (string $state): string => match (strtolower($state)) {
                            'pending',
                            'for filing' => 'warning',

                            'accepted' => 'success',

                            'completed',
                            'archived' => 'success',

                            'rejected' => 'danger',

                            'active',
                            'in_progress',
                            'outgoing' => 'info',

                            default => 'gray',
                        }
                    )
                    ->formatStateUsing(
                        fn (?string $state): string => blank($state)
                            ? '—'
                            : match (strtolower($state)) {
                            'archived' => 'Completed',
                            'outgoing' => 'In Progress',
                            'accepted' => 'Accepted',
                            default => ucwords(str_replace('_', ' ', (string) $state)),
                        }
                    )
                    ->alignCenter(),

                TextColumn::make('rejection_reason')
                    ->label('REASON')
                    ->visible(fn (): bool => $this->activeTab === 'rejected')
                    ->state(
                        fn (Document $record): string => $record->rejections->first()?->reason
                            ?? $record->rejection_reason
                            ?? '—'
                    )
                    ->wrap()
                    ->alignStart(),

                // Filament hides record actions when there are no rows.
                // Keep the empty table header aligned with populated tables.
                TextColumn::make('empty_actions_placeholder')
                    ->label('ACTIONS')
                    ->state('')
                    ->alignEnd()
                    ->visible(fn (): bool => ! $this->hasDocumentsForCurrentTable()),

            ])
            ->striped()
            ->recordActionsAlignment('end')
            ->recordActions([
                Action::make('message')
                    ->label('Message')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('gray')
                    ->iconButton()
                    ->extraAttributes(['class' => 'documents-table-action'])
                    ->tooltip(
                        fn (Document $record): string =>
                            ! $record->isAvailableForMessaging()
                                ? 'Messaging unavailable for rejected documents.'
                                : 'Message'
                    )
                    ->disabled(
                        fn (Document $record): bool => ! $record->isAvailableForMessaging()
                    )
                    ->visible(fn (Document $record): bool => $this->activeTab !== 'rejected')
                    ->url(
                        fn (Document $record): ?string =>
                            $record->isAvailableForMessaging()
                                ? ClientMessages::getUrl([
                                    'document' => $record->getPublicRouteKey(),
                                ])
                                : null
                    ),

                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->iconButton()
                    ->extraAttributes(['class' => 'documents-table-action'])
                    ->tooltip('Download')
                    ->url(
                        fn (Document $record): string => route(
                            'client.document.download',
                            ['document' => $record->getPublicRouteKey()]
                        )
                    )
                    ->visible(fn (Document $record): bool =>
                        $this->clientCanAccessFile($record) && filled($record->file_path)
                    )
                    ->openUrlInNewTab(),

                Action::make('print')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->iconButton()
                    ->extraAttributes(['class' => 'documents-table-action'])
                    ->tooltip('Print')
                    ->url(
                        fn (Document $record): string => route(
                            'client.document.preview',
                            ['document' => $record->getPublicRouteKey()]
                        )
                    )
                    ->visible(fn (Document $record): bool =>
                        $this->clientCanAccessFile($record) && filled($record->file_path)
                    )
                    ->openUrlInNewTab(),
            ])
            ->recordActionsColumnLabel('ACTIONS');
    }
}
