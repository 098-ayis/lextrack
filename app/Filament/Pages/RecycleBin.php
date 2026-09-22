<?php

namespace App\Filament\Pages;


class RecycleBin extends Cabinet
{
    protected static ?int $navigationSort = 4;
    protected static string|\UnitEnum|null $navigationGroup = 'OPERATIONS';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-trash';
    protected static ?string $navigationLabel = 'Recycle Bin';
    protected static ?string $title = 'Recycle Bin';
    protected static ?string $slug = 'recycle-bin';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public function goToRoot(): void
    {
        $this->redirect(Cabinet::getUrl(), navigate: true);
    }

    public function mount(): void
    {
        parent::mount();
        $this->currentPath = ['Recycle Bin'];
        $this->currentType = 'Recycle Bin';
        $this->currentOffice = '';
    }
}
