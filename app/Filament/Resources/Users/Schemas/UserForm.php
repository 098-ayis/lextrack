<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('user_id'),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('google_id'),
                TextInput::make('provider'),
                Textarea::make('profile_photo_url')
                    ->columnSpanFull(),
                TextInput::make('join_date'),
                TextInput::make('last_login'),
                TextInput::make('phone_number')
                    ->tel(),
                Select::make('status')
                    ->options(User::STATUS_OPTIONS)
                    ->default(User::DEFAULT_STATUS),
                Select::make('roles')
                    ->relationship('roles', 'name')
                    ->options(Role::query()->pluck('name', 'id'))
                    ->label('Role')
                    ->searchable(),
                TextInput::make('avatar'),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password(),
            ]);
    }
}
