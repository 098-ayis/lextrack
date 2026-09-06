<?php

namespace App\Filament\Resources\ActionTypes\Pages;

use App\Filament\Resources\ActionTypes\ActionTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditActionType extends EditRecord
{
    protected static string $resource = ActionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
