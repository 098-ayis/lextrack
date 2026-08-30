<?php

namespace App\Filament\Client\Widgets;

use App\Models\Document;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
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
            'total' => $this->accessibleDocumentsQuery($userId)->count(),

            'pending' => $this->accessibleDocumentsQuery($userId)
                ->where('status', 'pending')
                ->count(),

            'active' => $this->accessibleDocumentsQuery($userId)
                ->whereIn('status', ['in_progress', 'outgoing'])
                ->count(),

            'completed' => $this->accessibleDocumentsQuery($userId)
                ->whereIn('status', ['completed', 'archived'])
                ->count(),
        ];
    }

    protected function accessibleDocumentsQuery(int $userId): Builder
    {
        return Document::query()
            ->where(function (Builder $query) use ($userId): void {
                $query
                    ->where('user_id', $userId)
                    ->orWhereHas(
                        'documentRequests',
                        fn (Builder $requestQuery) => $requestQuery
                            ->where('user_id', $userId)
                    );
            });
    }
}
