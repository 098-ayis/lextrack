<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('document_versions', 'source')) {
            Schema::table('document_versions', function (Blueprint $table): void {
                $table->string('source')->default('admin')->after('file_hash');
            });
        }

        // Existing client uploads use the document owner's account. Files
        // uploaded by staff use the staff account, so use that distinction
        // to preserve the current records when adding the new marker.
        DB::table('document_versions')
            ->orderBy('version_id')
            ->chunkById(100, function ($versions): void {
                foreach ($versions as $version) {
                    $documentUserId = DB::table('documents')
                        ->where('document_id', $version->document_id)
                        ->value('user_id');

                    DB::table('document_versions')
                        ->where('version_id', $version->version_id)
                        ->update([
                            'source' => $documentUserId !== null
                                && (int) $documentUserId === (int) $version->user_id
                                ? 'client'
                                : 'admin',
                        ]);
                }
            }, 'version_id');
    }

    public function down(): void
    {
        if (Schema::hasColumn('document_versions', 'source')) {
            Schema::table('document_versions', function (Blueprint $table): void {
                $table->dropColumn('source');
            });
        }
    }
};
