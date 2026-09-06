<?php

namespace App\Filament\Resources\OfficeUnits\Pages;

use App\Filament\Resources\OfficeUnits\OfficeUnitResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOfficeUnit extends EditRecord
{
    protected static string $resource = OfficeUnitResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
