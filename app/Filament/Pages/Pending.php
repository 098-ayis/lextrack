<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Pending extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';
    protected string $view = 'filament.pages.pending';
}
