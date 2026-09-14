<?php

namespace App\Filament\Resources\OfficeUnits\Pages;

use App\Filament\Resources\OfficeUnits\OfficeUnitResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOfficeUnit extends EditRecord
{
    protected static string $resource = OfficeUnitResource::class;

    protected function getRedirectUrl(): string
    {
        return OfficeUnitResource::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Office/unit updated successfully';
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
