<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Messages extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationLabel = 'Messages';
    protected static ?string $title = 'Messages';
    
    protected string $view = 'filament.pages.messages';
}
