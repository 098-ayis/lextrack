<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table): void {
            $table->unsignedBigInteger('document_id')->nullable()->change();
            $table->foreignId('request_id')
                ->nullable()
                ->after('document_id')
                ->constrained('document_requests', 'request_id')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table): void {
            $table->dropForeign(['request_id']);
            $table->dropColumn('request_id');
            $table->unsignedBigInteger('document_id')->nullable(false)->change();
        });
    }
};
