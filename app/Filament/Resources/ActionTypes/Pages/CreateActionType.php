<?php

namespace App\Filament\Resources\ActionTypes\Pages;

use App\Filament\Resources\ActionTypes\ActionTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateActionType extends CreateRecord
{
    protected static string $resource = ActionTypeResource::class;
}
