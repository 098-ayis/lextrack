<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendars', function (Blueprint $table): void {
            $table->foreignId('document_request_id')
                ->nullable()
                ->after('user_id')
                ->constrained('document_requests', 'request_id')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('calendars', function (Blueprint $table): void {
            $table->dropForeign(['document_request_id']);
            $table->dropColumn('document_request_id');
        });
    }
};
