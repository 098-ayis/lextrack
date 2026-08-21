<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('client_id')
                ->nullable()
                ->change();

            $table->string('client_name')
                ->nullable()
                ->after('client_id');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('client_name');

            $table->foreignId('client_id')
            ->nullable()
            ->constrained('clients')
            ->nullOnDelete();
        });
    }
};