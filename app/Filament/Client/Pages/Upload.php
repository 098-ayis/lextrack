<?php

namespace App\Filament\Client\Pages;

use Filament\Pages\Page;

class Upload extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-up-tray';
    protected string $view = 'filament.client.pages.upload';
}
