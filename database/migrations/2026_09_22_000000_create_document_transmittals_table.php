<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_transmittals', function (Blueprint $table): void {
            $table->id('transmittal_id');
            $table->foreignId('document_id')
                ->constrained('documents', 'document_id')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('file_path');
            $table->char('file_hash', 64)->nullable()->index();
            $table->timestamps();

            $table->index(['document_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_transmittals');
    }
};
