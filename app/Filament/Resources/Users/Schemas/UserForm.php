<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use App\Services\UserRoleAssignmentService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('user_id')
                    ->label('User ID')
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn (?User $record): bool => $record !== null),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->rules(fn (?User $record): array => [
                        Rule::unique('users', 'email')->ignore($record?->getKey()),
                    ]),
                TextInput::make('google_id')
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn (?User $record): bool => $record !== null),
                TextInput::make('provider')
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn (?User $record): bool => $record !== null),
                TextInput::make('join_date')
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn (?User $record): bool => $record !== null),
                TextInput::make('last_login')
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn (?User $record): bool => $record !== null),
                TextInput::make('phone_number')
                    ->tel()
                    ->maxLength(255),
                Select::make('status')
                    ->options(User::STATUS_OPTIONS)
                    ->required()
                    ->default(User::DEFAULT_STATUS),
                Select::make('roles')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->label('Role')
                    ->searchable()
                    ->saveRelationshipsUsing(function (Select $component): void {
                        $record = $component->getRecord();

                        abort_unless($record instanceof User, 403);

                        app(UserRoleAssignmentService::class)->syncSelection(
                            auth()->user(),
                            $record,
                            $component->getState(),
                        );
                    }),
            ]);
    }
}
