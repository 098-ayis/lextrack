<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Incoming extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox';
    protected string $view = 'filament.pages.incoming';
}
