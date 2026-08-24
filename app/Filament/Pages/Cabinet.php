<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Cabinet extends Page
{
    protected static ?int $navigationSort = 6;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Cabinet';

    protected static ?string $title = 'Cabinet';

    protected string $view = 'filament.pages.cabinet';

    public string $search = '';

    public string $sourceFilter = 'all';

    public string $sortBy = 'name';

    public string $viewMode = 'tiles';

    public bool $detailsPane = false;

    public bool $previewPane = false;

    public bool $showFileExtensions = true;

    public ?string $selectedItem = null;

    public string $currentType = '';

    public string $currentOffice = '';

    public function openType(string $type): void
    {
        $this->currentType = $type;
        $this->currentOffice = '';
        $this->selectedItem = null;
    }

    public function openOffice(string $office): void
    {
        $this->currentOffice = $office;
        $this->selectedItem = null;
    }

    public function goToRoot(): void
    {
        $this->currentType = '';
        $this->currentOffice = '';
        $this->selectedItem = null;
    }

    public function goToType(): void
    {
        $this->currentOffice = '';
        $this->selectedItem = null;
    }

    public function clearSearch(): void
    {
        $this->search = '';
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = $mode;
    }

    public function setSort(string $sort): void
    {
        $this->sortBy = $sort;
    }

    public function toggleDetailsPane(): void
    {
        $this->detailsPane = ! $this->detailsPane;

        if ($this->detailsPane) {
            $this->previewPane = false;
        }
    }

    public function togglePreviewPane(): void
    {
        $this->previewPane = ! $this->previewPane;

        if ($this->previewPane) {
            $this->detailsPane = false;
        }
    }

    public function toggleFileExtensions(): void
    {
        $this->showFileExtensions = ! $this->showFileExtensions;
    }

    public function selectItem(string $item): void
    {
        $this->selectedItem = $item;
    }
}