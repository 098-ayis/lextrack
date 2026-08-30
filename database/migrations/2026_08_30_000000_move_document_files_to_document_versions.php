<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('documents', 'file_path')) {
            return;
        }

        DB::table('documents')
            ->whereNotNull('file_path')
            ->where('file_path', '!=', '')
            ->orderBy('document_id')
            ->each(function (object $document): void {
                $alreadyMigrated = DB::table('document_versions')
                    ->where('document_id', $document->document_id)
                    ->where('file_path', $document->file_path)
                    ->exists();

                if ($alreadyMigrated) {
                    return;
                }

                $highestVersion = DB::table('document_versions')
                    ->where('document_id', $document->document_id)
                    ->pluck('version_number')
                    ->map(function ($versionNumber): int {
                        preg_match('/(\d+)\s*$/', (string) $versionNumber, $matches);

                        return (int) ($matches[1] ?? 0);
                    })
                    ->max() ?? 0;

                $createdAt = $document->created_at ?? now();

                DB::table('document_versions')->insert([
                    'user_id' => $document->user_id,
                    'document_id' => $document->document_id,
                    'version_number' => (string) max(1, $highestVersion + 1),
                    'file_path' => $document->file_path,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            });

        Schema::table('documents', function (Blueprint $table): void {
            $table->dropColumn('file_path');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('documents', 'file_path')) {
            Schema::table('documents', function (Blueprint $table): void {
                $table->string('file_path')->nullable()->after('status');
            });
        }

        DB::table('documents')
            ->orderBy('document_id')
            ->each(function (object $document): void {
                $latestVersion = DB::table('document_versions')
                    ->where('document_id', $document->document_id)
                    ->orderByDesc('created_at')
                    ->orderByDesc('version_id')
                    ->first();

                if ($latestVersion) {
                    DB::table('documents')
                        ->where('document_id', $document->document_id)
                        ->update(['file_path' => $latestVersion->file_path]);
                }
            });
    }
};
