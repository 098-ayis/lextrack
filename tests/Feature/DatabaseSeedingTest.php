<?php

namespace Tests\Feature;

use App\Models\ActionType;
use App\Models\DocumentType;
use App\Models\User;
use Database\Seeders\ActionTypeSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DocumentTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DatabaseSeedingTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_does_not_create_predictable_users(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_reference_seeders_are_repeatable_and_preserve_custom_data(): void
    {
        $customPermission = Permission::create(['name' => 'custom.permission', 'guard_name' => 'web']);
        $customRole = Role::create(['name' => 'Custom Role', 'guard_name' => 'web']);
        $customRole->givePermissionTo($customPermission);
        $customDocumentType = DocumentType::create([
            'type_name' => 'Custom Type',
            'type_desc' => 'Custom description',
        ]);
        $customActionType = ActionType::create([
            'action_name' => 'FOR REVIEW',
            'color' => '#CUSTOM',
        ]);

        foreach (range(1, 2) as $_) {
            app(RolePermissionSeeder::class)->run();
            app(DocumentTypeSeeder::class)->run();
            app(ActionTypeSeeder::class)->run();
        }

        $this->assertSame(1, Role::where('name', 'Super Admin')->count());
        $this->assertTrue($customRole->fresh()->hasPermissionTo($customPermission));
        $this->assertSame($customDocumentType->getKey(), DocumentType::where('type_name', 'Custom Type')->value('type_id'));
        $this->assertSame('#CUSTOM', $customActionType->fresh()->color);
        $this->assertSame(1, ActionType::where('action_name', 'FOR REVIEW')->count());
    }

    public function test_admin_provisioning_creates_no_password_and_is_repeatable(): void
    {
        $this->artisan('admin:provision', ['email' => 'first.admin@bicol-u.edu.ph', '--name' => 'First Admin'])
            ->assertExitCode(0);

        $user = User::where('email', 'first.admin@bicol-u.edu.ph')->firstOrFail();
        $id = $user->getKey();

        $this->artisan('admin:provision', ['email' => 'first.admin@bicol-u.edu.ph'])
            ->assertExitCode(0);

        $this->assertSame($id, User::where('email', 'first.admin@bicol-u.edu.ph')->value('id'));
        $this->assertNull($user->fresh()->password);
        $this->assertTrue($user->fresh()->hasRole('Super Admin'));
    }

    public function test_admin_provisioning_preserves_existing_user_data(): void
    {
        $user = User::factory()->create([
            'email' => 'existing.admin@bicol-u.edu.ph',
            'name' => 'Existing Name',
            'password' => 'existing-secret',
            'status' => 'Inactive',
        ]);
        $existingPasswordHash = $user->password;

        Artisan::call('admin:provision', ['email' => $user->email]);

        $user->refresh();
        $this->assertSame('Existing Name', $user->name);
        $this->assertSame($existingPasswordHash, $user->password);
        $this->assertSame(User::DEFAULT_STATUS, $user->status);
        $this->assertTrue($user->hasRole('Super Admin'));
    }

    public function test_admin_provisioning_rejects_an_unauthorized_email_domain(): void
    {
        $this->artisan('admin:provision', ['email' => 'admin@example.com'])
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 0);
    }
}
