<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Ensure Google sign-in can assign the default client role on existing
     * deployments where the role seeder was run before the Staff->Client
     * rename was introduced.
     */
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $rolesTable = config('permission.table_names.roles', 'roles');

        if (DB::table($rolesTable)
            ->where('name', 'Client')
            ->where('guard_name', 'web')
            ->exists()) {
            return;
        }

        $staffRole = DB::table($rolesTable)
            ->where('name', 'Staff')
            ->where('guard_name', 'web')
            ->first();

        if ($staffRole) {
            // Preserve existing role assignments and permissions.
            DB::table($rolesTable)
                ->where('id', $staffRole->id)
                ->update([
                    'name' => 'Client',
                    'updated_at' => now(),
                ]);

            return;
        }

        DB::table($rolesTable)->insert([
            'name' => 'Client',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Do not remove or rename production roles during a rollback.
     */
    public function down(): void
    {
        // Intentionally left blank.
    }
};
