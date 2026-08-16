<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DocumentStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Documents', '125'),
            Stat::make('Incoming Documents', '18'),
            Stat::make('Pending Documents', '12'),
            Stat::make('Completed Documents', '95'),
        ];
    }
}
