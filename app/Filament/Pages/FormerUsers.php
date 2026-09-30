<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Support\RoleSecurity;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class FormerUsers extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?int $navigationSort = 2;

    protected static string|UnitEnum|null $navigationGroup = 'ADMINISTRATION';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-minus';

    protected static ?string $navigationLabel = 'Former Users';

    protected static ?string $title = 'Former Users';

    protected string $view = 'filament.pages.former-users';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(RoleSecurity::SUPER_ADMIN) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => User::onlyTrashed()->with('roles'))
            ->columns([
                TextColumn::make('name')
                    ->label('Historical Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('email')
                    ->label('Historical Email')
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->label('Original Role')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Current Account Status')
                    ->formatStateUsing(fn (): string => User::FORMER_USER_LABEL)
                    ->badge()
                    ->color('warning'),
                TextColumn::make('deleted_at')
                    ->label('Deleted Date')
                    ->dateTime('M d, Y g:i A')
                    ->sortable(),
            ])
            ->searchable()
            ->searchPlaceholder('Search former users')
            ->defaultSort('deleted_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->recordActions([
                Action::make('viewHistory')
                    ->label('View history')
                    ->icon(Heroicon::Eye)
                    ->color('gray')
                    ->url(fn (User $record): string => FormerUserHistory::getUrl([
                        'user' => $record->getKey(),
                    ])),
                Action::make('restore')
                    ->label('Restore User')
                    ->icon(Heroicon::ArrowPath)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Restore user?')
                    ->modalDescription('This user will return to normal user management. Their historical records will continue to use the same name and identity.')
                    ->authorize(fn (User $record): bool => UserResource::canRestore($record))
                    ->action(function (User $record): void {
                        abort_unless(UserResource::canRestore($record), 403);

                        $record->restore();

                        Notification::make()
                            ->success()
                            ->title('User restored')
                            ->body($record->historical_name.' can now be managed again.')
                            ->send();
                    })
                    ->after(function (): void {
                        $this->flushCachedTableRecords();
                    }),
            ])
            ->recordActionsColumnLabel('Actions');
    }
}
