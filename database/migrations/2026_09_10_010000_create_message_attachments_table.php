<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_attachments', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('message_id')
                ->constrained('messages')
                ->cascadeOnDelete();

            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('sha256', 64)->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['message_id', 'sort_order']);
        });

        // Preserve attachments created before the relationship table existed.
        if (! Schema::hasColumn('messages', 'attachment_path')) {
            return;
        }

        DB::table('messages')
            ->select([
                'id',
                'attachment_path',
                'attachment_name',
                'attachment_mime_type',
                'created_at',
                'updated_at',
            ])
            ->whereNotNull('attachment_path')
            ->orderBy('id')
            ->each(function (object $message): void {
                DB::table('message_attachments')->insert([
                    'message_id' => $message->id,
                    'disk' => 'local',
                    'path' => $message->attachment_path,
                    'original_name' => $message->attachment_name,
                    'mime_type' => $message->attachment_mime_type,
                    'created_at' => $message->created_at,
                    'updated_at' => $message->updated_at,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_attachments');
    }
};
