<?php

namespace App\Filament\Pages;

use App\Models\Document;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class Incoming extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox';

    protected string $view = 'filament.pages.incoming';

    public string $search = '';

    public function getStats(): array
    {
        return [
            'total' => Document::count(),
            'pending' => Document::where('status', 'pending')->count(),
            'active' => Document::where('status', 'in_progress')->count(),
            'completed' => Document::where('status', 'completed')->count(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Document::query()
                    ->where('status', 'in_progress')
            )
            ->columns([
                TextColumn::make('document_id')
                    ->label('NO.')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('lao_number')
                    ->label('LAO NUMBER')
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
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'in_progress' => 'info',
                        'completed' => 'success',
                        'returned' => 'danger',
                        'archived' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('DATE RECEIVED')
                    ->date('F d, Y')
                    ->sortable(),
                
                TextColumn::make('deadline')
                    ->label('DATE RECEIVED')
                    ->date('F d, Y')
                    ->sortable(),
            ])

            

            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'returned' => 'Returned',
                        'archived' => 'Archived',
                    ]),
            ])

            ->searchPlaceholder('Search Document...')

            ->actions([
                Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->tooltip('View'),

                Action::make('edit')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->tooltip('Edit'),

                Action::make('message')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->tooltip('Message'),
            ])

            ->actionsColumnLabel('ACTION')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}