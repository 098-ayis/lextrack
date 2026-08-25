<?php

namespace App\Filament\Client\Widgets;

use App\Models\Document;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class DashboardStats extends Widget
{
    protected static ?int $sort = 1;

    protected string $view = 'filament.client.widgets.dashboard-stats';

    protected int | string | array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $userId = Auth::id();

        return [
            'total' => Document::where('user_id', $userId)->count(),

            'pending' => Document::where('user_id', $userId)
                ->where('status', 'pending')
                ->count(),

            'active' => Document::where('user_id', $userId)
                ->where('status', 'in_progress')
                ->count(),

            'completed' => Document::where('user_id', $userId)
                ->where('status', 'completed')
                ->count(),
        ];
    }
}