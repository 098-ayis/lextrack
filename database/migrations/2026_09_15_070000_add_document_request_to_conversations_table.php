<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table): void {
            $table->foreignId('document_id')
                ->nullable()
                ->change();

            $table->foreignId('document_request_id')
                ->nullable()
                ->after('document_id')
                ->unique()
                ->constrained('document_requests', 'request_id')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table): void {
            $table->dropForeign(['document_request_id']);
            $table->dropUnique('conversations_document_request_id_unique');
            $table->dropColumn('document_request_id');

            $table->foreignId('document_id')
                ->nullable(false)
                ->change();
        });
    }
};
