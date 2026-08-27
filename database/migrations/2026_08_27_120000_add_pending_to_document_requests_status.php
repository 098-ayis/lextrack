<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE document_requests
            MODIFY status ENUM('pending', 'accepted', 'rejected')
            NOT NULL
        ");
    }

    public function down(): void
    {
        DB::table('document_requests')
            ->where('status', 'pending')
            ->update(['status' => 'rejected']);

        DB::statement("
            ALTER TABLE document_requests
            MODIFY status ENUM('accepted', 'rejected')
            NOT NULL
        ");
    }
};
