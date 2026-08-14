<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Outgoing extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-paper-airplane';
    protected string $view = 'filament.pages.outgoing';
}
