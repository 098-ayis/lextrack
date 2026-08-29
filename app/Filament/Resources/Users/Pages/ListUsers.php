<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->gap(false)
            ->components([
                View::make('filament.resources.users.pages.toolbar')
                    ->columnSpanFull(),
                EmbeddedTable::make(),
            ]);
    }
}
