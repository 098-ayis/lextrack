<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('documents', 'transmittal')) {
            return;
        }

        if (! Schema::hasTable('document_transmittals')) {
            throw new RuntimeException(
                'Cannot remove documents.transmittal before document_transmittals exists.'
            );
        }

        DB::table('documents')
            ->whereNotNull('transmittal')
            ->where('transmittal', '<>', '')
            ->orderBy('document_id')
            ->chunkById(100, function ($documents): void {
                foreach ($documents as $document) {
                    $filePath = trim((string) $document->transmittal);

                    if ($filePath === '' || DB::table('document_transmittals')
                        ->where('document_id', $document->document_id)
                        ->where('file_path', $filePath)
                        ->exists()) {
                        continue;
                    }

                    [$fileDisk, $fileHash] = $this->locateLegacyFile($filePath);

                    DB::table('document_transmittals')->insert([
                        'document_id' => $document->document_id,
                        'user_id' => $document->user_id,
                        'file_path' => $filePath,
                        'file_disk' => $fileDisk,
                        'original_filename' => basename($filePath),
                        'file_hash' => $fileHash,
                        'created_at' => $document->created_at ?? now(),
                        'updated_at' => $document->updated_at ?? now(),
                    ]);
                }
            }, 'document_id');

        Schema::table('documents', function (Blueprint $table): void {
            $table->dropColumn('transmittal');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('documents', 'transmittal')) {
            Schema::table('documents', function (Blueprint $table): void {
                $table->string('transmittal')->nullable();
            });
        }

        if (! Schema::hasTable('document_transmittals')) {
            return;
        }

        DB::table('documents')
            ->orderBy('document_id')
            ->chunkById(100, function ($documents): void {
                foreach ($documents as $document) {
                    $filePath = DB::table('document_transmittals')
                        ->where('document_id', $document->document_id)
                        ->orderBy('created_at')
                        ->orderBy('transmittal_id')
                        ->value('file_path');

                    if ($filePath !== null) {
                        DB::table('documents')
                            ->where('document_id', $document->document_id)
                            ->update(['transmittal' => $filePath]);
                    }
                }
            }, 'document_id');
    }

    /**
     * @return array{0: string, 1: ?string}
     */
    private function locateLegacyFile(string $filePath): array
    {
        $diskNames = array_unique([
            (string) config('filesystems.document_disk', 'local'),
            'local',
            'public',
        ]);

        foreach ($diskNames as $diskName) {
            try {
                $disk = Storage::disk($diskName);

                if (! $disk->exists($filePath)) {
                    continue;
                }

                $stream = $disk->readStream($filePath);

                if (! is_resource($stream)) {
                    return [$diskName, null];
                }

                try {
                    $context = hash_init('sha256');
                    hash_update_stream($context, $stream);

                    return [$diskName, hash_final($context)];
                } finally {
                    fclose($stream);
                }
            } catch (Throwable) {
                continue;
            }
        }

        return [(string) config('filesystems.document_disk', 'local'), null];
    }
};
