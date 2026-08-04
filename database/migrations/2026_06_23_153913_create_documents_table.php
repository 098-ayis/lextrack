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
        Schema::create('documents', function (Blueprint $table) {
            $table->id('document_id');

            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('type_id')
                ->constrained('document_types')
                ->restrictOnDelete();

            $table->foreignId('client_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('lao_number')->nullable();
            $table->string('office_unit')->nullable();
            $table->text('particulars');

            $table->date('deadline')->nullable();

            $table->string('action_taken')->nullable();

            $table->string('sent_to')->nullable();
            $table->date('sent_date')->nullable();

            $table->string('returned_from')->nullable();
            $table->date('date_returned')->nullable();

            $table->date('outgoing_date')->nullable();

            $table->enum('status',
                ['pending', 'in_progress', 'completed', 'returned', 'archived']
            )->default('pending');

            $table->timestamps();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
