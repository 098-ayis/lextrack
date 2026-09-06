<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Document;
use App\Models\Document as DocumentModel;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DocumentStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Documents', DocumentModel::count())
                ->url(Document::getUrl()),
            Stat::make(
                'Incoming Documents',
                DocumentModel::where('status', 'in_progress')->count()
            )->url(Document::getUrl(['section' => 'incoming'])),
            Stat::make('Pending Documents', DocumentModel::where('status', 'pending')->count())
                ->url(Document::getUrl(['section' => 'pending'])),
            Stat::make('Completed Documents', DocumentModel::where('status', 'completed')->count())
                ->url(Document::getUrl(['section' => 'completed'])),
        ];
    }
}
