<?php

namespace App\Filament\Client\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Document;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Client\Pages\Upload; 



class DashboardDocumentTable extends BaseWidget
{
   
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = ''; 

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Document::query()->where('user_id', auth()->id())->latest()
            )

            ->recordUrl(
                fn (Document $record): string =>
                    \App\Filament\Client\Pages\ViewDocument::getUrl([
                        'document' => $record->document_id,
                    ])
            )

            ->columns([
                TextColumn::make('lao_number')
                        ->label('LAO #')
                        ->formatStateUsing(fn ($state) => $state ?? '')
                        ->searchable(),

                TextColumn::make('type_id')
                    ->label('TYPE')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
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
                    })
                    ->searchable(),

                TextColumn::make('particulars')
                    ->label('PARTICULARS')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('DATE SUBMITTED')
                    ->date('M d, Y') 
                    ->sortable(),

                TextColumn::make('status')
                    ->label('STATUS')
                    ->color(fn (string $state): string => match (strtolower($state)) {
                        'pending', 'for filing' => 'warning',
                        'completed' => 'success', 
                        'active' => 'info', 
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucwords($state)),
            ])
            ->filters([
                SelectFilter::make('type_id')
                    ->label('Type')
                    ->options([
                        1 => 'MOA',
                        2 => 'Correspondence',
                        3 => 'Contract',
                        4 => 'Proposal',
                        5 => 'PROCUREMENT',
                        6 => 'REFERENCE SLIP',
                        7 => 'Clearance',
                        8 => 'MOU',
                        9 => 'NDA',
                        10 => 'DOD',
                        11 => 'GBA',
                        12 => 'Others',
                    ]),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'For Filing' => 'For Filing',
                        'completed' => 'Completed',
                    ]),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('submit_document')
                    ->label('Submit New Document')
                    ->icon('heroicon-o-plus')
                    ->url(fn (): string => Upload::getUrl()) 
                    ->color('primary'),
            ]);
    }
}