<?php

namespace App\Filament\Pages;

use App\Models\Conversation;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;


class Messages extends Page
{
    protected static ?int $navigationSort = 5;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected string $view = 'filament.pages.messages';

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function getViewData(): array
    {
        return [
            'conversations' => Conversation::with([
                'document',
                'messages.sender',
                'messages.recipient',
            ])
            ->latest()
            ->get(),
        ];
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

                TextColumn::make('deadline')
                    ->label('DATE RECEIVED')
                    ->date('F d, Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('document_type')
                    ->options([
                        'MOA' => 'MOA',
                        'Correspondence' => 'Correspondence',
                        'Contract' => 'Contract',
                        'Proposal' => 'Proposal',
                        'UCMC' => 'UCMC',
                        'PROCUREMENT' => 'PROCUREMENT',
                        'REFERENCE SLIP' => 'REFERENCE SLIP',
                        'Clearance' => 'Clearance',
                        'MOU' => 'MOU',
                        'NDA' => 'NDA',
                        'DOD' => 'DOD',
                        'GBA' => 'GBA',
                    ]),
            ])

            ->actions([
                Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->tooltip('View')
                    ->url(fn (PendingDocument $record) =>
                        $record->file_path
                            ? asset('storage/' . $record->file_path)
                            : null
                    )
                    ->openUrlInNewTab(),

                Action::make('edit')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->tooltip('Edit')
                    ->url(fn (PendingDocument $record) =>
                        route('filament.admin.pages.pending.edit', $record)
                    ),

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