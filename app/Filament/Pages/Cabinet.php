<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Cabinet extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected string $view = 'filament.pages.cabinet';
}
