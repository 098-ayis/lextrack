<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Services\UserRoleAssignmentService;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->requiresConfirmation(fn (): bool => app(UserRoleAssignmentService::class)->isGrantingSuperAdmin(
                null,
                $this->data['roles'] ?? [],
            ))
            ->modalHeading('Grant Super Admin privileges?')
            ->modalDescription('You are about to grant full Super Admin privileges to this user. Continue?')
            ->modalSubmitActionLabel('Grant Super Admin');
    }

    protected function beforeCreate(): void
    {
        app(UserRoleAssignmentService::class)->validateSelection(
            auth()->user(),
            null,
            $this->data['roles'] ?? [],
        );
    }
}
