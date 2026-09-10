<?php

namespace App\Filament\Client\Pages;

use App\Filament\Client\Pages\Messages as ClientMessages;
use App\Filament\Client\Pages\ViewDocument;
use App\Models\Document;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class Documents extends Page implements HasTable
{
    use InteractsWithTable;

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
            ->where(function (Builder $query): void {
                $query
                    ->where('user_id', auth()->id())
                    ->orWhereHas(
                        'documentRequests',
                        fn (Builder $requestQuery) => $requestQuery
                            ->where('user_id', auth()->id())
                    );
            })
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

        $this->highlightedDocumentId = is_numeric($document) && (int) $document > 0
            ? (int) $document
            : null;
    }

    // This method sets the tab AND instantly refreshes the table data
    public function updateTab($tab)
    {
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

        if ($this->activeTab === 'requested') {
            $query
                ->whereHas('documentRequests', function ($requestQuery) {
                    $requestQuery->where('user_id', auth()->id());
                })
                ->with([
                    'documentRequests' => function ($requestQuery) {
                        $requestQuery
                            ->where('user_id', auth()->id())
                            ->latest('created_at')
                            ->latest('request_id');
                    },
                ])
                ->when(
                    $this->documentStatus !== '',
                    fn ($documentQuery) => $documentQuery->whereHas(
                        'documentRequests',
                        fn ($requestQuery) => $requestQuery
                            ->where('user_id', auth()->id())
                            ->where('status', $this->documentStatus)
                    )
                );
        } else {
            if ($this->activeTab === 'all') {
                $query
                    ->where(function (Builder $documentQuery): void {
                        $documentQuery
                            ->where('user_id', auth()->id())
                            ->orWhereHas(
                                'documentRequests',
                                fn (Builder $requestQuery) => $requestQuery
                                    ->where('user_id', auth()->id())
                            );
                    })
                    ->with([
                        'documentRequests' => function ($requestQuery) {
                            $requestQuery
                                ->where('user_id', auth()->id())
                                ->latest('created_at')
                                ->latest('request_id');
                        },
                    ]);
            } else {
                $query->where('user_id', auth()->id());

                if ($this->activeTab === 'in_progress') {
                    $query->whereIn('status', [
                        'in_progress',
                        'outgoing',
                    ]);
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
        return $table
            ->query(
                $this->documentsQuery()
                    ->latest()
            )
            ->recordUrl(
                fn (Document $record): string => ViewDocument::getUrl([
                    'document' => $record->document_id,
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
                    ->formatStateUsing(fn ($state) => $state ?? ''),

                TextColumn::make('document_type')
                    ->label('TYPE'),

                TextColumn::make('particulars')
                    ->label('PARTICULARS'),

                TextColumn::make('source')
                    ->label('SOURCE')
                    ->visible(fn (): bool => $this->activeTab === 'all')
                    ->state(
                        fn (Document $record): string => (int) $record->user_id === (int) auth()->id()
                            ? 'Uploaded'
                            : 'Requested'
                    )
                    ->badge()
                    ->color(
                        fn (string $state): string => $state === 'Requested'
                            ? 'purple'
                            : 'gray'
                    ),

                TextColumn::make('created_at')
                    ->label('DATE SUBMITTED')
                    ->date('M d, Y'),

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
                        fn (?string $state): string => match (strtolower((string) $state)) {
                            'archived' => 'Completed',
                            'outgoing' => 'In Progress',
                            'accepted' => 'Accepted',
                            default => ucwords(str_replace('_', ' ', (string) $state)),
                        }
                    ),

                TextColumn::make('rejection_reason')
                    ->label('REASON')
                    ->visible(fn (): bool => $this->activeTab === 'rejected')
                    ->state(
                        fn (Document $record): string => $record->rejections->first()?->reason
                            ?? $record->rejection_reason
                            ?? 'No reason provided'
                    )
                    ->wrap(),

                // Filament hides record actions when there are no rows.
                // Keep the empty table header aligned with populated tables.
                TextColumn::make('empty_actions_placeholder')
                    ->label('ACTIONS')
                    ->state('')
                    ->alignEnd()
                    ->visible(fn (): bool => ! $this->hasDocumentsForCurrentTable()),

            ])
            ->recordActions([
                Action::make('message')
                    ->label('Message')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('gray')
                    ->iconButton()
                    ->extraAttributes(['class' => 'documents-table-action'])
                    ->tooltip(
                        fn (Document $record): string =>
                            $record->status === 'rejected'
                                ? 'Messaging is unavailable for rejected documents'
                                : 'Message'
                    )
                    ->disabled(
                        fn (Document $record): bool =>
                            $record->status === 'rejected'
                    )
                    ->url(
                        fn (Document $record): ?string =>
                            $record->status !== 'rejected'
                                ? ClientMessages::getUrl([
                                    'document' => $record->document_id,
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
                            ['document' => $record->document_id]
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
                            ['document' => $record->document_id]
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
