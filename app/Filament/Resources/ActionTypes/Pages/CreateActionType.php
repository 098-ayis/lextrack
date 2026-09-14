<?php

namespace App\Filament\Resources\ActionTypes\Pages;

use App\Filament\Resources\ActionTypes\ActionTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateActionType extends CreateRecord
{
    protected static string $resource = ActionTypeResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return ActionTypeResource::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Action type created successfully';
    }
}
