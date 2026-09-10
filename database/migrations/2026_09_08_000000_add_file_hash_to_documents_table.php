<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->string('file_hash', 64)
                ->nullable()
                ->after('user_id');

            $table->unique(
                ['user_id', 'file_hash'],
                'documents_user_id_file_hash_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropUnique('documents_user_id_file_hash_unique');
            $table->dropColumn('file_hash');
        });
    }
};
