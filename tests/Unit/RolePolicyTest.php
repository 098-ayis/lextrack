<?php

namespace Tests\Unit;

use App\Models\User;
use App\Policies\RolePolicy;
use App\Support\RoleSecurity;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePolicyTest extends TestCase
{
    public function test_super_admin_can_edit_admin_permissions_but_not_super_admin_permissions(): void
    {
        $superAdmin = Mockery::mock(User::class);
        $superAdmin->shouldReceive('hasRole')
            ->once()
            ->with(RoleSecurity::SUPER_ADMIN)
            ->andReturnTrue();

        $policy = new RolePolicy;

        $this->assertTrue($policy->update(
            $superAdmin,
            new Role(['name' => RoleSecurity::LEGAL_STAFF]),
        ));
        $this->assertFalse($policy->update(
            $superAdmin,
            new Role(['name' => RoleSecurity::SUPER_ADMIN]),
        ));
    }

    public function test_other_role_managers_cannot_edit_admin_permissions(): void
    {
        $roleManager = Mockery::mock(User::class);
        $roleManager->shouldReceive('hasRole')
            ->once()
            ->with(RoleSecurity::SUPER_ADMIN)
            ->andReturnFalse();
        $roleManager->shouldReceive('can')
            ->once()
            ->with('Update:Role')
            ->andReturnTrue();

        $this->assertFalse((new RolePolicy)->update(
            $roleManager,
            new Role(['name' => RoleSecurity::LEGAL_STAFF]),
        ));
        $this->assertTrue((new RolePolicy)->update(
            $roleManager,
            new Role(['name' => 'Staff']),
        ));
    }

    public function test_protected_role_names_remain_protected_after_a_rename_attempt(): void
    {
        $legalStaff = new Role(['name' => RoleSecurity::LEGAL_STAFF]);
        $legalStaff->syncOriginal();
        $legalStaff->name = 'Staff';

        $superAdmin = new Role(['name' => RoleSecurity::SUPER_ADMIN]);
        $superAdmin->syncOriginal();
        $superAdmin->name = 'Staff';

        $this->assertTrue(RoleSecurity::isProtectedRole($legalStaff));
        $this->assertTrue(RoleSecurity::isLegalStaffRole($legalStaff));
        $this->assertTrue(RoleSecurity::isProtectedRole($superAdmin));
        $this->assertTrue(RoleSecurity::isSuperAdminRole($superAdmin));
    }
}
