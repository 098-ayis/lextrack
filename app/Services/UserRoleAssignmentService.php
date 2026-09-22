<?php

namespace App\Services;

use App\Models\User;
use App\Support\RoleSecurity;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserRoleAssignmentService
{
    public function validateSelection(mixed $actor, ?User $target, mixed $selection): Collection
    {
        abort_unless($actor instanceof User && $actor->hasRole(RoleSecurity::SUPER_ADMIN), 403);

        $roleIds = $this->normalizeSelection($selection);
        $roleModel = config('permission.models.role', Role::class);
        $roles = $roleIds->isEmpty()
            ? collect()
            : $roleModel::query()
                ->where('guard_name', config('auth.defaults.guard', 'web'))
                ->whereKey($roleIds)
                ->get();

        if ($roles->count() !== $roleIds->count()) {
            $this->reject('Choose valid roles for this user.');
        }

        $currentRoleIds = $target ? $this->currentRoleIds($target) : [];
        $requestedRoleIds = $roleIds->all();

        if ($target?->is($actor) && $this->sortedIds($requestedRoleIds) !== $this->sortedIds($currentRoleIds)) {
            $this->reject('You cannot change your own roles.');
        }

        foreach ($roles as $role) {
            if (
                RoleSecurity::isSuperAdminRole($role)
                && ! in_array((int) $role->getKey(), $currentRoleIds, true)
            ) {
                $this->reject('The Super Admin role cannot be assigned through user management.');
            }
        }

        return $roles;
    }

    public function syncSelection(mixed $actor, User $target, mixed $selection): void
    {
        $roles = $this->validateSelection($actor, $target, $selection);

        if ($target->is($actor)) {
            return;
        }

        $target->syncRoles($roles);
    }

    private function normalizeSelection(mixed $selection): Collection
    {
        if ($selection === null || $selection === '') {
            return collect();
        }

        if (! is_array($selection)) {
            $selection = [$selection];
        }

        $ids = [];

        foreach ($selection as $value) {
            if (! is_int($value) && ! is_string($value)) {
                $this->reject('Choose valid roles for this user.');
            }

            $validated = filter_var($value, FILTER_VALIDATE_INT);

            if ($validated === false || $validated < 1) {
                $this->reject('Choose valid roles for this user.');
            }

            $ids[] = $validated;
        }

        return collect($ids)->unique()->values();
    }

    private function currentRoleIds(User $user): array
    {
        $roles = $user->roles();

        return $roles
            ->pluck($roles->getRelated()->getQualifiedKeyName())
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    private function sortedIds(array $ids): array
    {
        sort($ids);

        return $ids;
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages([
            'data.roles' => $message,
        ]);
    }
}
