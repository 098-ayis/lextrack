<?php

namespace App\Filament\Resources\OfficeUnits\Pages;

use App\Filament\Resources\OfficeUnits\OfficeUnitResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOfficeUnit extends CreateRecord
{
    protected static string $resource = OfficeUnitResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return OfficeUnitResource::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Office/unit created successfully';
    }
}
