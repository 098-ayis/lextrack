<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use App\Models\PendingDocument;


class Pending extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected string $view = 'filament.pages.pending';

    public string $search = '';

    public string $documentType = '';

    public string $priority = '';

    public string $dateFilter = '';

    public function resetFilters(): void
    {
        $this->search = '';
        $this->documentType = '';
        $this->priority = '';
        $this->dateFilter = '';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PendingDocument::query()
            )
            ->columns([
                TextColumn::make('id')
                    ->label('NO.')
                    ->alignCenter(),

                TextColumn::make('document_type')
                    ->label('TYPE OF DOCUMENT')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('office_unit')
                    ->label('OFFICE / UNIT')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('particulars')
                    ->label('PARTICULARS')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('status')
                    ->label('STATUS')
                    ->badge()
                    ->color('warning'),

                TextColumn::make('date_received')
                    ->label('DATE RECEIVED')
                    ->date('F d, Y')
                    ->sortable(),
            ])
            ->actions([
                Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->tooltip('View'),

                Action::make('forward')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->tooltip('Forward')
                    ->requiresConfirmation(),

                Action::make('delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->tooltip('Delete')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Document')
                    ->modalDescription('Are you sure you want to delete this document?')
                    ->modalSubmitActionLabel('Yes, Delete'),
            ])
            ->actionsColumnLabel('ACTION')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}