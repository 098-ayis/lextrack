<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_requests', function (Blueprint $table): void {
            $table->string('copy_type')->nullable()->after('purpose_details');
            $table->dateTime('pickup_at')->nullable()->after('copy_type');
        });
    }

    public function down(): void
    {
        Schema::table('document_requests', function (Blueprint $table): void {
            $table->dropColumn([
                'copy_type',
                'pickup_at',
            ]);
        });
    }
};
