<?php

namespace App\Support;

use Spatie\Permission\Models\Role;

final class RoleSecurity
{
    public const SUPER_ADMIN = 'Super Admin';

    public const LEGAL_STAFF = 'Admin';

    public const UNASSIGNABLE_ROLE_NAMES = [
        'Super Admin',
        'super_admin',
    ];

    private const PROTECTED_ROLE_NAMES = [
        'super admin',
        'admin',
    ];

    private const ROLE_MUTATION_ABILITIES = [
        'update',
        'delete',
        'deleteAny',
        'forceDelete',
        'forceDeleteAny',
        'restore',
        'restoreAny',
    ];

    public static function isSuperAdminRole(Role $role): bool
    {
        return self::normalizeRoleName($role->name) === self::normalizeRoleName(self::SUPER_ADMIN)
            || self::normalizeRoleName((string) $role->getOriginal('name')) === self::normalizeRoleName(self::SUPER_ADMIN);
    }

    public static function isLegalStaffRole(Role $role): bool
    {
        return self::normalizeRoleName($role->name) === self::normalizeRoleName(self::LEGAL_STAFF)
            || self::normalizeRoleName((string) $role->getOriginal('name')) === self::normalizeRoleName(self::LEGAL_STAFF);
    }

    public static function isProtectedRole(Role $role): bool
    {
        return self::isProtectedRoleName($role->name)
            || self::isProtectedRoleName((string) $role->getOriginal('name'));
    }

    public static function isProtectedRoleName(string $name): bool
    {
        return in_array(self::normalizeRoleName($name), self::PROTECTED_ROLE_NAMES, true);
    }

    public static function shouldDeferRoleMutation(string $ability, array $arguments): bool
    {
        if (! in_array($ability, self::ROLE_MUTATION_ABILITIES, true)) {
            return false;
        }

        $roleModel = config('permission.models.role', Role::class);

        foreach ($arguments as $argument) {
            if ($argument instanceof Role && self::isProtectedRole($argument)) {
                return true;
            }

            if (
                in_array($ability, ['deleteAny', 'forceDeleteAny', 'restoreAny'], true)
                && is_string($argument)
                && class_exists($argument)
                && is_a($argument, $roleModel, true)
            ) {
                return true;
            }
        }

        return false;
    }

    private static function normalizeRoleName(string $name): string
    {
        return preg_replace('/\s+/', ' ', str_replace('_', ' ', strtolower(trim($name)))) ?? '';
    }
}
