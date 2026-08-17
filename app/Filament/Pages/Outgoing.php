<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use App\Models\Document;

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use App\Models\Document;

class Outgoing extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-paper-airplane';
    protected string $view = 'filament.pages.outgoing';

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
                Document::query()
                    ->where('status', 'outgoing')
            )
            ->columns([
                TextColumn::make('document_id')->label('NO.')->alignCenter()->searchable()->sortable(),
                TextColumn::make('type_id')->label('TYPE OF DOCUMENT')->searchable()->sortable(),
                TextColumn::make('status')->label('STATUS')->badge()->color('warning'),
                TextColumn::make('sent_to')->label('SENT TO')->searchable()->sortable(),
                TextColumn::make('sent_date')->label('SENT DATE')->wrap()->searchable(),
                TextColumn::make('returned_from')->label('RETURNED FROM')->date('F d, Y')->sortable(),
                TextColumn::make('date_returned')->label('RETURNED DATE')->date('F d, Y')->sortable(),
                TextColumn::make('outgoing_date')->label('OUTGOING DATE')->date('F d, Y')->sortable(),
            ])
        ->recordActions([
                Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->tooltip('View'),

                Action::make('edit')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->tooltip('Edit'),

                Action::make('archive')
                    ->icon('heroicon-o-archive-box')
                    ->color('gray')
                    ->tooltip('Archive')
                    ->requiresConfirmation()
                    ->modalHeading('Archive Document')
                    ->modalDescription('Are you sure you want to archive this document?')
                    ->modalSubmitActionLabel('Yes, Archive'),
            ])
            ->actionsColumnLabel('ACTION')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}