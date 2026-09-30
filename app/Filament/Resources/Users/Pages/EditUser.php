<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Services\UserRoleAssignmentService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->authorize(fn (): bool => UserResource::canDelete($this->getRecord()))
                ->modalHeading('Remove user?')
                ->modalDescription('This user will be removed from user management and can no longer sign in. Their historical name and activity records will be retained.'),
        ];
    }

    protected function beforeSave(): void
    {
        $record = $this->getRecord();

        abort_unless($record instanceof User, 403);

        if (array_key_exists('roles', $this->data ?? [])) {
            $selection = $this->data['roles'];
        } else {
            $roles = $record->roles();
            $selection = $roles->pluck($roles->getRelated()->getQualifiedKeyName())->all();
        }

        app(UserRoleAssignmentService::class)->validateSelection(
            auth()->user(),
            $record,
            $selection,
        );
    }
}
