<?php

namespace Database\Seeders;

use App\Support\RoleSecurity;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /*
        |--------------------------------------------------------------------------
        | Permissions
        |--------------------------------------------------------------------------
        */

        $permissions = [
            'view_shared_messages',
            'reply_shared_messages',
            'close_conversations',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        */

        $client = Role::firstOrCreate([
            'name' => RoleSecurity::CLIENT,
            'guard_name' => 'web',
        ]);

        $admin = Role::firstOrCreate([
            'name' => RoleSecurity::LEGAL_STAFF,
            'guard_name' => 'web',
        ]);

        $superAdmin = Role::firstOrCreate([
            'name' => RoleSecurity::SUPER_ADMIN,
            'guard_name' => 'web',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Role permissions
        |--------------------------------------------------------------------------
        */

        $client->givePermissionTo([
            'view_shared_messages',
            'reply_shared_messages',
        ]);

        $admin->givePermissionTo([
            'view_shared_messages',
            'reply_shared_messages',
            'close_conversations',
        ]);

        $superAdmin->givePermissionTo(
            Permission::all()
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
