<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('profile_photo_url')
                    ->label('')
                    ->imageSize(32)
                    ->circular()
                    ->extraAttributes(['class' => 'users-avatar-column']),
                TextColumn::make('name')
                    ->label('Full Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? ucfirst($state) : 'Unknown')
                    ->color(fn (?string $state): string => match (strtolower((string) $state)) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        'banned' => 'danger',
                        'pending' => 'info',
                        'suspended' => 'warning',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role_name')
                    ->label('Role')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? ucfirst($state) : '—')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('join_date')
                    ->label('Joined Date')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('last_login')
                    ->label('Last Active')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->searchable()
            ->searchPlaceholder('Search')
            ->filters([
                SelectFilter::make('role_name')
                    ->label('Role')
                    ->options(fn (): array => \App\Models\User::query()
                        ->whereNotNull('role_name')
                        ->where('role_name', '!=', '')
                        ->distinct()
                        ->orderBy('role_name')
                        ->pluck('role_name', 'role_name')
                        ->all()),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(User::STATUS_OPTIONS),
                SelectFilter::make('join_date')
                    ->label('Date')
                    ->options(fn (): array => \App\Models\User::query()
                        ->whereNotNull('join_date')
                        ->where('join_date', '!=', '')
                        ->distinct()
                        ->orderByDesc('join_date')
                        ->pluck('join_date', 'join_date')
                        ->all()),
            ], FiltersLayout::Hidden)
            ->deferFilters(false)
            ->defaultSort('name')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->toolbarActions([
                Action::make('export')
                    ->label('Export')
                    ->icon(Heroicon::ArrowUpTray)
                    ->url(fn (): string => route('admin.users.export'))
                    ->openUrlInNewTab()
                    ->extraAttributes(['class' => 'users-export-button']),
                CreateAction::make()
                    ->label('Add User')
                    ->icon(Heroicon::Plus)
                    ->extraAttributes(['class' => 'users-add-button']),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit user')
                    ->extraAttributes(['class' => 'users-edit-action']),
                DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Delete user')
                    ->extraAttributes(['class' => 'users-delete-action']),
            ])
            ->recordActionsColumnLabel('Actions');
    }
}
