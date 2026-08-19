<?php

namespace App\Filament\Client\Widgets;

use Filament\Widgets\Widget;
// use App\Models\Document; // You will use this later to get real database counts

class DashboardStats extends Widget
{
    // CHANGED: Removed "static" from this line
    protected string $view = 'filament.client.widgets.dashboard-stats';
    
    // Makes the widget take up the full width of the dashboard
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