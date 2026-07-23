<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_versions', function (Blueprint $table) {
            $table->id('version_id');

            $table->foreigId('user_id')
                ->constrined()
                ->cascadeOnDelete();

            $table->foreignId('document_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('version_number');
            $table->file('file_path');

            $table->timestamps('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_versions');
    }
};
