<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $legacy = DB::table('roles')
            ->where('name', 'super_admin')
            ->where('guard_name', 'web')
            ->first();

        if (! $legacy) {
            return;
        }

        if (DB::table('roles')
            ->where('name', 'Super Admin')
            ->where('guard_name', 'web')
            ->exists()) {
            throw new \RuntimeException(
                'Both "super_admin" and "Super Admin" roles exist. Resolve this role-name conflict before migrating.'
            );
        }

        try {
            DB::table('roles')
                ->where('id', $legacy->id)
                ->update([
                    'name' => 'Super Admin',
                    'updated_at' => now(),
                ]);
        } catch (QueryException $exception) {
            throw new \RuntimeException('Unable to normalize the legacy super_admin role safely.', 0, $exception);
        }
    }

    public function down(): void
    {
        // Do not rename a production authorization role during rollback.
    }
};
