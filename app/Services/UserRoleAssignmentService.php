<?php

namespace App\Services;

use App\Models\User;
use App\Support\RoleSecurity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserRoleAssignmentService
{
    public function isGrantingSuperAdmin(?User $target, mixed $selection): bool
    {
        $requestedRoles = $this->rolesForSelection($selection);

        if (! $this->containsSuperAdminRole($requestedRoles)) {
            return false;
        }

        return $target === null
            || ! $this->containsSuperAdminRole($target->roles()->get());
    }

    public function isLastSuperAdmin(User $user): bool
    {
        return $this->containsSuperAdminRole($user->roles()->get())
            && $this->superAdminCount() <= 1;
    }

    public function validateSelection(mixed $actor, ?User $target, mixed $selection): Collection
    {
        abort_unless($actor instanceof User && $actor->hasRole(RoleSecurity::SUPER_ADMIN), 403);

        $roles = $this->rolesForSelection($selection);
        $roleIds = $roles->map(fn (Role $role): int => (int) $role->getKey());

        $currentRoleIds = $target ? $this->currentRoleIds($target) : [];
        $requestedRoleIds = $roleIds->all();

        if (
            $target !== null
            && $this->containsSuperAdminRole($target->roles()->get())
            && ! $this->containsSuperAdminRole($roles)
            && $this->superAdminCount() <= 1
        ) {
            $this->reject('At least one Super Admin must remain.');
        }

        if ($target?->is($actor) && $this->sortedIds($requestedRoleIds) !== $this->sortedIds($currentRoleIds)) {
            $this->reject('You cannot change your own roles.');
        }

        return $roles;
    }

    public function syncSelection(mixed $actor, User $target, mixed $selection): void
    {
        DB::transaction(function () use ($actor, $target, $selection): void {
            // Serialize role changes so two concurrent requests cannot both
            // remove the final Super Admin before either request sees it.
            $this->lockRoleRows();

            $roles = $this->validateSelection($actor, $target, $selection);

            if ($target->is($actor)) {
                return;
            }

            $currentRoles = $target->roles()->get();
            $currentRoleIds = $currentRoles
                ->map(fn (Role $role): int => (int) $role->getKey())
                ->all();
            $target->syncRoles($roles);
            $requestedRoleIds = $roles
                ->map(fn (Role $role): int => (int) $role->getKey())
                ->all();

            $addedRoleIds = array_diff($requestedRoleIds, $currentRoleIds);
            $removedRoleIds = array_diff($currentRoleIds, $requestedRoleIds);

            if ($addedRoleIds === [] && $removedRoleIds === []) {
                return;
            }

            $addedRoles = $roles->filter(
                fn (Role $role): bool => in_array((int) $role->getKey(), $addedRoleIds, true),
            );
            $removedRoles = $currentRoles->filter(
                fn (Role $role): bool => in_array((int) $role->getKey(), $removedRoleIds, true),
            );
            $superAdminGranted = $this->containsSuperAdminRole($addedRoles);
            $superAdminRevoked = $this->containsSuperAdminRole($removedRoles);
            $actorName = $actor instanceof User ? $actor->name : 'Unknown user';

            $action = $superAdminGranted
                ? 'Super Admin privileges granted'
                : ($superAdminRevoked
                    ? 'Super Admin privileges revoked'
                    : ($addedRoleIds !== [] && $removedRoleIds !== []
                        ? 'Role assignment changed'
                        : ($addedRoleIds !== [] ? 'Role assigned' : 'Role revoked')));

            $details = $superAdminGranted
                ? sprintf('%s granted full Super Admin privileges to %s.', $actorName, $target->name)
                : ($superAdminRevoked
                    ? sprintf('%s revoked Super Admin privileges from %s.', $actorName, $target->name)
                    : sprintf('%s changed the roles assigned to %s.', $actorName, $target->name));

            app(AuditLogService::class)->record(
                $action,
                $details,
                $target,
            );
        });
    }

    private function rolesForSelection(mixed $selection): Collection
    {
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

        return $roles;
    }

    private function containsSuperAdminRole(Collection $roles): bool
    {
        return $roles->contains(fn (Role $role): bool => RoleSecurity::isSuperAdminRole($role));
    }

    private function superAdminCount(): int
    {
        $roleModel = config('permission.models.role', Role::class);
        $superAdminRoleIds = $roleModel::query()
            ->where('guard_name', config('auth.defaults.guard', 'web'))
            ->get()
            ->filter(fn (Role $role): bool => RoleSecurity::isSuperAdminRole($role))
            ->modelKeys();

        if ($superAdminRoleIds === []) {
            return 0;
        }

        return User::query()
            ->whereHas('roles', fn (Builder $query): Builder => $query->whereKey($superAdminRoleIds))
            ->count();
    }

    private function lockRoleRows(): void
    {
        $roleModel = config('permission.models.role', Role::class);

        $roleModel::query()
            ->where('guard_name', config('auth.defaults.guard', 'web'))
            ->lockForUpdate()
            ->get();
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
