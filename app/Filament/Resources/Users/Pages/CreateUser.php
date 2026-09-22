<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Services\UserRoleAssignmentService;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function beforeCreate(): void
    {
        app(UserRoleAssignmentService::class)->validateSelection(
            auth()->user(),
            null,
            $this->data['roles'] ?? [],
        );
    }
}
