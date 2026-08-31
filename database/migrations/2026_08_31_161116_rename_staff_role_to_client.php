<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')
            ->where('name', 'Staff')
            ->where('guard_name', 'web')
            ->update([
                'name' => 'Client',
            ]);
    }

    public function down(): void
    {
        DB::table('roles')
            ->where('name', 'Client')
            ->where('guard_name', 'web')
            ->update([
                'name' => 'Staff',
            ]);
    }
};