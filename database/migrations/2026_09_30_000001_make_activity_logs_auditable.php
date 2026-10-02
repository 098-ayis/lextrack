<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table): void {
            $table->dropForeign(['document_id']);
            $table->unsignedBigInteger('document_id')->nullable()->change();
            $table->foreign('document_id')
                ->references('document_id')
                ->on('documents')
                ->cascadeOnDelete();

            $table->string('subject_type')->nullable()->after('document_id');
            $table->unsignedBigInteger('subject_id')->nullable()->after('subject_type');
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table): void {
            $table->dropIndex(['subject_type', 'subject_id']);
            $table->dropColumn(['subject_type', 'subject_id']);
            $table->dropForeign(['document_id']);
            $table->unsignedBigInteger('document_id')->nullable(false)->change();
            $table->foreign('document_id')
                ->references('document_id')
                ->on('documents')
                ->cascadeOnDelete();
        });
    }
};
