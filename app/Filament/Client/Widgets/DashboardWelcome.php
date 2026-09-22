<?php

namespace App\Filament\Client\Widgets;

use Filament\Widgets\Widget;

class DashboardWelcome extends Widget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'filament.client.widgets.dashboard-welcome';
}
