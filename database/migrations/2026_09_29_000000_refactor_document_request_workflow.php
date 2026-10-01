<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Keep the legacy value available while existing rows are classified.
        DB::statement("\n            ALTER TABLE document_requests\n            MODIFY status ENUM('pending', 'accepted', 'for_release', 'ready_for_pickup', 'completed', 'rejected')\n            NOT NULL\n        ");

        DB::table('document_requests')
            ->where('status', 'accepted')
            ->where('copy_type', 'soft_copy')
            ->whereNotNull('attachment_path')
            ->where('attachment_path', '<>', '')
            ->update(['status' => 'completed']);

        DB::table('document_requests')
            ->where('status', 'accepted')
            ->where('copy_type', 'original')
            ->whereNotNull('pickup_at')
            ->update(['status' => 'ready_for_pickup']);

        DB::table('document_requests')
            ->where('status', 'accepted')
            ->update(['status' => 'for_release']);

        DB::statement("\n            ALTER TABLE document_requests\n            MODIFY status ENUM('pending', 'for_release', 'ready_for_pickup', 'completed', 'rejected')\n            NOT NULL\n        ");

        Schema::table('document_requests', function (Blueprint $table): void {
            $table->dateTime('claimed_at')->nullable()->after('pickup_at');
        });
    }

    public function down(): void
    {
        DB::statement("\n            ALTER TABLE document_requests\n            MODIFY status ENUM('pending', 'accepted', 'for_release', 'ready_for_pickup', 'completed', 'rejected')\n            NOT NULL\n        ");

        DB::table('document_requests')
            ->whereIn('status', ['for_release', 'ready_for_pickup', 'completed'])
            ->update(['status' => 'accepted']);

        DB::statement("\n            ALTER TABLE document_requests\n            MODIFY status ENUM('pending', 'accepted', 'rejected')\n            NOT NULL\n        ");

        Schema::table('document_requests', function (Blueprint $table): void {
            $table->dropColumn('claimed_at');
        });
    }
};
