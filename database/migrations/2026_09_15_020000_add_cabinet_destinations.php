<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        foreach (['cabinet_document_locations', 'cabinet_copies'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->unsignedBigInteger('folder_id')->nullable()->change();
                $table->string('cabinet_type')->nullable();
                $table->string('cabinet_office')->nullable();
            });
        }
    }
    public function down(): void {
        foreach (['cabinet_document_locations', 'cabinet_copies'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropColumn(['cabinet_type', 'cabinet_office']);
            });
        }
    }
};
