<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE documents MODIFY status ENUM('pending', 'in_progress', 'completed', 'returned', 'archived', 'outgoing') DEFAULT 'pending'");

        Schema::table('documents', function (Blueprint $table) {
            $table->string('status_other')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('status_other');
        });

        DB::statement("ALTER TABLE documents MODIFY status ENUM('pending', 'in_progress', 'completed', 'returned', 'archived') DEFAULT 'pending'");
    }
};