<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

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
                TextInput::make('status'),
                TextInput::make('role_name'),
                TextInput::make('avatar'),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password(),
            ]);
    }
}
