<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_documents', function (Blueprint $table) {
            $table->id();

            $table->string('document_type');
            $table->string('office_unit');
            $table->text('particulars')->nullable();

            $table->string('status')->default('Pending');

            $table->date('date_received');

            $table->string('file_path')->nullable();

            $table->foreignId('submitted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_documents');
    }
};