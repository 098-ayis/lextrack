<?php

namespace App\Filament\Client\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getHeading(): string
    {
        return '';
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Client\Widgets\DashboardStats::class,
            \App\Filament\Client\Widgets\DashboardDocumentTable::class,
        ];
    }
}