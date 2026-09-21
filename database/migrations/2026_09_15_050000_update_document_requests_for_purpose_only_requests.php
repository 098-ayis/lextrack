<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_requests', function (Blueprint $table): void {
            $table->foreignId('document_id')
                ->nullable()
                ->change();

            $table->text('purpose_details')
                ->nullable()
                ->after('purpose');
        });
    }

    public function down(): void
    {
        Schema::table('document_requests', function (Blueprint $table): void {
            $table->dropColumn('purpose_details');

            $table->foreignId('document_id')
                ->nullable(false)
                ->change();
        });
    }
};
