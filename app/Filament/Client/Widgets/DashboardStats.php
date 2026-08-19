<?php

namespace App\Filament\Client\Widgets;

use Filament\Widgets\Widget;

class DashboardStats extends Widget
{
    protected string $view = 'filament.client.widgets.dashboard-stats';
    
    protected int | string | array $columnSpan = 'full'; 

    protected function getViewData(): array
    {
        // For now, we use dummy data. 
        // Later, replace with real queries like: Document::where('status', 'Pending')->count()
        return [
            'total' => 20,
            'pending' => 2,
            'active' => 10,
            'completed' => 8,
        ];
    }
}