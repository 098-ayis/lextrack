<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('cabinet_recycle_bin', function (Blueprint $table) {
            $table->unsignedBigInteger('document_id')->primary();
            $table->foreign('document_id')->references('document_id')->on('documents')->cascadeOnDelete();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('cabinet_recycle_bin'); }
};
