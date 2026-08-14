<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Messages extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';
    protected string $view = 'filament.pages.messages';
}
