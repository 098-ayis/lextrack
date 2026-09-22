<?php

namespace Tests\Feature;

use App\Filament\Pages\Messages as AdminMessages;
use App\Filament\Resources\Users\UserResource;
use App\Http\Middleware\EnsureLegalStaff;
use App\Models\Conversation;
use App\Models\User;
use App\Policies\RolePolicy;
use App\Services\UserRoleAssignmentService;
use App\Support\RoleSecurity;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class UserRoleSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_super_admin_can_assign_legal_staff_role_to_another_user(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        $legalStaff = $this->makeRole(RoleSecurity::LEGAL_STAFF);
        $target = User::factory()->create(['status' => 'Active']);

        app(UserRoleAssignmentService::class)->syncSelection(
            $superAdmin,
            $target,
            [$legalStaff->getKey()],
        );

        $this->assertTrue($target->fresh()->hasRole(RoleSecurity::LEGAL_STAFF));
        $this->assertTrue($target->fresh()->canAccessPanel(Panel::make()->id('admin')));
    }

    public function test_super_admin_cannot_add_a_role_to_themselves(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        $legalStaff = $this->makeRole(RoleSecurity::LEGAL_STAFF);

        try {
            app(UserRoleAssignmentService::class)->syncSelection(
                $superAdmin,
                $superAdmin,
                [$this->roleId($superAdmin, 'Super Admin'), $legalStaff->getKey()],
            );
            $this->fail('Self role changes should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('data.roles', $exception->errors());
        }

        $this->assertFalse($superAdmin->fresh()->hasRole(RoleSecurity::LEGAL_STAFF));
        $this->assertTrue($superAdmin->fresh()->hasRole('Super Admin'));
    }

    public function test_super_admin_cannot_remove_their_own_super_admin_role(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        try {
            app(UserRoleAssignmentService::class)->syncSelection($superAdmin, $superAdmin, []);
            $this->fail('Removing the current Super Admin role should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('data.roles', $exception->errors());
        }

        $this->assertTrue($superAdmin->fresh()->hasRole('Super Admin'));
    }

    public function test_user_management_cannot_grant_super_admin(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        $target = User::factory()->create(['status' => 'Active']);
        $superAdminRole = $this->roleId($superAdmin, 'Super Admin');

        $this->expectException(ValidationException::class);

        app(UserRoleAssignmentService::class)->syncSelection($superAdmin, $target, [$superAdminRole]);
    }

    public function test_super_admin_can_assign_client_role_and_client_panel_access_remains_available(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        $clientRole = $this->makeRole('Client');
        $client = User::factory()->create(['status' => 'Active']);

        app(UserRoleAssignmentService::class)->syncSelection(
            $superAdmin,
            $client,
            [$clientRole->getKey()],
        );

        $this->assertTrue($client->fresh()->canAccessPanel(Panel::make()->id('client')));
    }

    public function test_super_admin_can_edit_legal_staff_permissions_but_not_protected_roles(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        $legalStaff = $this->makeRole(RoleSecurity::LEGAL_STAFF);
        $superAdminRole = $superAdmin->roles()->firstOrFail();
        $ordinaryRole = $this->makeRole('Staff');
        $permission = Permission::query()->firstOrCreate([
            'name' => 'documents.review',
            'guard_name' => 'web',
        ]);
        $this->actingAs($superAdmin);

        $this->assertInstanceOf(RolePolicy::class, Gate::getPolicyFor(Role::class));
        $this->assertTrue(Gate::allows('update', $legalStaff));
        $this->assertFalse(Gate::allows('delete', $legalStaff));
        $this->assertFalse(Gate::allows('update', $superAdminRole));
        $this->assertFalse(Gate::allows('deleteAny', Role::class));
        $this->assertTrue(Gate::allows('update', $ordinaryRole));
        $this->assertTrue(Gate::allows('unrestricted.application.ability'));

        Gate::authorize('update', $legalStaff);
        $legalStaff->syncPermissions([$permission]);

        $this->assertTrue($legalStaff->fresh()->hasPermissionTo($permission));
    }

    public function test_super_admin_cannot_see_shared_messages_even_with_explicit_permissions(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        $superAdminRole = $superAdmin->roles()->firstOrFail();
        $superAdminRole->givePermissionTo([
            'view_shared_messages',
            'reply_shared_messages',
            'close_conversations',
        ]);
        $this->actingAs($superAdmin);

        $this->assertFalse(AdminMessages::canAccess());
        $this->assertFalse(Gate::allows('view', new Conversation));
        $this->assertFalse(Gate::allows('sendMessage', new Conversation));
        $this->assertFalse(Gate::allows('close', new Conversation));
    }

    public function test_staff_with_shared_message_permission_can_still_access_the_inbox(): void
    {
        $permission = Permission::query()->firstOrCreate([
            'name' => 'view_shared_messages',
            'guard_name' => 'web',
        ]);
        $staffRole = $this->makeRole(RoleSecurity::LEGAL_STAFF);
        $staffRole->givePermissionTo($permission);
        $staff = User::factory()->create(['status' => 'Active']);
        $staff->assignRole($staffRole);
        $this->actingAs($staff);

        $this->assertTrue(AdminMessages::canAccess());
    }

    public function test_reserved_roles_cannot_be_renamed_or_deleted_through_model_persistence(): void
    {
        $legalStaff = $this->makeRole(RoleSecurity::LEGAL_STAFF);
        $superAdminRole = $this->makeRole(RoleSecurity::SUPER_ADMIN);

        foreach ([$legalStaff, $superAdminRole] as $role) {
            $protectedName = $role->name;
            $role->name = 'Ordinary Role';

            $this->assertFalse($role->save());
            $this->assertDatabaseHas('roles', [
                'id' => $role->getKey(),
                'name' => $protectedName,
            ]);
            $this->assertFalse($role->delete());
        }

        $legalStaff->refresh();
        $this->assertTrue($legalStaff->save());
        $this->assertFalse($legalStaff->delete());
        $this->assertDatabaseHas('roles', ['id' => $legalStaff->getKey(), 'name' => RoleSecurity::LEGAL_STAFF]);
    }

    public function test_granted_permissions_do_not_bypass_the_legal_staff_original_file_guard(): void
    {
        $permission = Permission::query()->firstOrCreate([
            'name' => 'admin.documents.download',
            'guard_name' => 'web',
        ]);
        $staffRole = $this->makeRole('Staff');
        $staffRole->givePermissionTo($permission);
        $staff = User::factory()->create(['status' => 'Active']);
        $staff->assignRole($staffRole);

        $superAdmin = $this->makeSuperAdmin();
        $superAdmin->assignRole($this->makeRole(RoleSecurity::LEGAL_STAFF));

        $this->assertTrue($staff->can('admin.documents.download'));
        $this->assertTrue($superAdmin->can('admin.documents.download'));

        foreach ([$staff, $superAdmin] as $user) {
            $request = Request::create('/admin/documents/1/download');
            $request->setUserResolver(fn (): User => $user);

            try {
                app(EnsureLegalStaff::class)->handle($request, fn () => response('allowed'));
                $this->fail('Permissions must not replace the Legal Staff role check.');
            } catch (HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
            }
        }
    }

    public function test_super_admin_cannot_edit_or_delete_their_own_user_record(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        $otherUser = User::factory()->create();
        $this->actingAs($superAdmin);

        $this->assertFalse(UserResource::canEdit($superAdmin));
        $this->assertFalse(UserResource::canDelete($superAdmin));
        $this->assertTrue(UserResource::canEdit($otherUser));
        $this->assertTrue(UserResource::canDelete($otherUser));
    }

    private function makeSuperAdmin(): User
    {
        $user = User::factory()->create(['status' => 'Active']);
        $user->assignRole($this->makeRole('Super Admin'));

        return $user;
    }

    private function makeRole(string $name): Role
    {
        return Role::query()->firstOrCreate([
            'name' => $name,
            'guard_name' => 'web',
        ]);
    }

    private function roleId(User $user, string $name): int
    {
        return (int) $user->roles()->where('name', $name)->value('roles.id');
    }
}
