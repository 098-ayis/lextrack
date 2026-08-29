<?php

namespace App\Filament\Client\Pages;

use App\Models\Document;
use Filament\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class Documents extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationLabel = 'Documents';
    protected static ?string $title = 'Documents';
    protected static ?string $slug = 'documents';

    protected string $view = 'filament.client.pages.documents'; 

    public string $activeTab = 'all'; 

    // This method sets the tab AND instantly refreshes the table data
    public function updateTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetTable(); 
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Document::query()
                    ->where('user_id', auth()->id())
                    ->when($this->activeTab !== 'all', function ($query) {
                        if ($this->activeTab === 'in_progress') {
                            $query->whereIn('status', [
                                'in_progress',
                                'outgoing',
                            ]);

                            return;
                        }

                        if ($this->activeTab === 'completed') {
                            $query->whereIn('status', [
                                'completed',
                                'archived',
                            ]);

                            return;
                        }

                        $query->where('status', $this->activeTab);
                    })
                    ->latest()
            )
            ->columns([
                TextColumn::make('id')->label('LAO #'),
                TextColumn::make('particulars')->label('PARTICULARS'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string => match (strtolower((string) $state)) {
                            'archived' => 'Completed',
                            'outgoing' => 'In Progress',
                            default => ucwords(str_replace('_', ' ', (string) $state)),
                        }
                    ),
            ]);
    }
}
