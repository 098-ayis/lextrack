<?php

namespace App\Filament\Client\Widgets;

use App\Models\Document;
use App\Filament\Client\Pages\ViewDocument;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class DashboardDocumentTable extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'filament.client.widgets.dashboard-document-table';

    public string $documentSearch = '';

    public string $documentType = '';

    public string $documentStatus = '';

    /*
    |--------------------------------------------------------------------------
    | Livewire filter updates
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Table
    |--------------------------------------------------------------------------
    */

    public function table(Table $table): Table
    {
        return $table
            ->heading('')

            ->query(
                Document::query()

                    // Only documents submitted by the logged-in client
                    ->where('user_id', auth()->id())

                    // Document Type
                    ->when(
                        $this->documentType !== '',
                        fn ($query) =>
                            $query->where(
                                'type_id',
                                (int) $this->documentType
                            )
                    )

                    // Status
                    ->when(
                        $this->documentStatus !== '',
                        function ($query) {
                            if ($this->documentStatus === 'in_progress') {
                                $query->whereIn('status', [
                                    'in_progress',
                                    'outgoing',
                                ]);

                                return;
                            }

                            $query->where('status', $this->documentStatus);
                        }
                    )

                    // Search
                    ->when(
                        trim($this->documentSearch) !== '',
                        function ($query) {
                            $search = trim($this->documentSearch);

                            $query->where(function ($query) use ($search) {
                                $query
                                    ->where(
                                        'particulars',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'office_unit',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'lao_number',
                                        'like',
                                        "%{$search}%"
                                    );
                            });
                        }
                    )

                    // Newest documents first
                    ->orderByDesc('created_at')

                    // Only show the 10 most recent documents
                    ->limit(10)
            )

            /*
            |--------------------------------------------------------------------------
            | Click row → View Document
            |--------------------------------------------------------------------------
            */

            ->recordUrl(
                fn (Document $record): string =>
                    ViewDocument::getUrl([
                        'document' => $record->document_id,
                    ])
            )

            /*
            |--------------------------------------------------------------------------
            | Columns
            |--------------------------------------------------------------------------
            */

            ->columns([

                TextColumn::make('lao_number')
                    ->label('LAO #')
                    ->formatStateUsing(
                        fn ($state) => $state ?? ''
                    ),

                TextColumn::make('type_id')
                    ->label('TYPE')
                    ->formatStateUsing(
                        fn ($state): string => match ((string) $state) {
                            '1' => 'MOA',
                            '2' => 'Correspondence',
                            '3' => 'Contract',
                            '4' => 'Proposal',
                            '5' => 'PROCUREMENT',
                            '6' => 'REFERENCE SLIP',
                            '7' => 'Clearance',
                            '8' => 'MOU',
                            '9' => 'NDA',
                            '10' => 'DOD',
                            '11' => 'GBA',
                            '12' => 'Others',
                            default => 'Unknown',
                        }
                    ),

                TextColumn::make('particulars')
                    ->label('PARTICULARS'),

                TextColumn::make('created_at')
                    ->label('DATE SUBMITTED')
                    ->date('M d, Y'),

                TextColumn::make('status')
                    ->label('STATUS')
                    ->color(
                        fn (string $state): string => match (
                            strtolower($state)
                        ) {
                            'pending',
                            'for filing' => 'warning',

                            'completed',
                            'archived' => 'success',

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
                            default => ucwords(
                                str_replace('_', ' ', (string) $state)
                            ),
                        },
                    ),
            ])

            /*
            |--------------------------------------------------------------------------
            | Pagination
            |--------------------------------------------------------------------------
            |
            | Since this is "Recent Documents", pagination is unnecessary.
            |
            */

            ->paginated(false);
    }
}
