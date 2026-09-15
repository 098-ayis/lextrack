<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->string('public_id', 26)
                ->nullable()
                ->after('document_id');
        });

        DB::table('documents')
            ->whereNull('public_id')
            ->chunkById(500, function ($documents): void {
                foreach ($documents as $document) {
                    do {
                        $publicId = (string) Str::ulid();
                    } while (DB::table('documents')->where('public_id', $publicId)->exists());

                    DB::table('documents')
                        ->where('document_id', $document->document_id)
                        ->whereNull('public_id')
                        ->update(['public_id' => $publicId]);
                }
            }, 'document_id', 'document_id');

        Schema::table('documents', function (Blueprint $table): void {
            $table->unique('public_id');
        });

        Schema::table('documents', function (Blueprint $table): void {
            $table->string('public_id', 26)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropUnique(['public_id']);
            $table->dropColumn('public_id');
        });
    }
};
