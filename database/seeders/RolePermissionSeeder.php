<?php

namespace Database\Seeders;

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
            'name' => 'Client',
            'guard_name' => 'web',
        ]);

        $admin = Role::firstOrCreate([
            'name' => 'Admin',
            'guard_name' => 'web',
        ]);

        $superAdmin = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Role permissions
        |--------------------------------------------------------------------------
        */

        $client->syncPermissions([
            'view_shared_messages',
            'reply_shared_messages',
        ]);

        $admin->syncPermissions([
            'view_shared_messages',
            'reply_shared_messages',
            'close_conversations',
        ]);

        $superAdmin->syncPermissions(
            Permission::all()
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
