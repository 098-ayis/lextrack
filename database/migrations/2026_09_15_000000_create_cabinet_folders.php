<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cabinet_folders', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });
        Schema::create('cabinet_document_locations', function (Blueprint $table) {
            $table->unsignedBigInteger('document_id')->primary();
            $table->foreign('document_id')->references('document_id')->on('documents')->cascadeOnDelete();
            $table->foreignId('folder_id')->constrained('cabinet_folders')->cascadeOnDelete();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('cabinet_document_locations');
        Schema::dropIfExists('cabinet_folders');
    }
};
