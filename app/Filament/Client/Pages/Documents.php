<?php

namespace App\Filament\Client\Pages;

use Filament\Pages\Page;

class Documents extends Page
{
    protected static ?string $navigationLabel = 'Documents';

    protected static ?string $title = 'Documents';

    protected static ?string $slug = 'documents';

    protected string $view = 'filament.client.pages.documents';
}