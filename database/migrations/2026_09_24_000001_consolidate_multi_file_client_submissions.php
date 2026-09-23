<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Consolidate rows created by the old upload flow, which created one
     * document row per file in a single submission.
     */
    public function up(): void
    {
        if (! Schema::hasTable('documents') || ! Schema::hasTable('document_versions')) {
            return;
        }

        $candidateDocuments = DB::table('documents')
            ->whereNotNull('transmittal')
            ->where('transmittal', '!=', '')
            ->whereNotNull('description')
            ->where('description', '!=', '')
            ->orderBy('document_id')
            ->get();

        $groups = $candidateDocuments->groupBy(static fn (object $document): string => implode('|', [
            (string) $document->user_id,
            (string) $document->description,
            (string) $document->document_type,
            (string) $document->office_unit,
            (string) $document->transmittal,
            (string) $document->status,
            (string) $document->created_at,
        ]));

        foreach ($groups as $documents) {
            if ($documents->count() < 2) {
                continue;
            }

            $documentIds = $documents->pluck('document_id')->map(static fn ($id): int => (int) $id)->all();

            if ($this->hasProtectedRelatedRecords($documentIds)) {
                continue;
            }

            $keeper = $documents->first();
            $keeperId = (int) $keeper->document_id;
            $keeperVersions = DB::table('document_versions')
                ->where('document_id', $keeperId)
                ->get();

            foreach ($keeperVersions as $version) {
                $filePath = (string) $version->file_path;
                $newPath = $this->restoreOriginalFilename(
                    $filePath,
                    (string) ($keeper->document_name ?? ''),
                );

                if ($newPath !== $filePath) {
                    DB::table('document_versions')
                        ->where('version_id', $version->version_id)
                        ->update(['file_path' => $newPath]);
                }
            }

            $nextVersion = (int) DB::table('document_versions')
                ->where('document_id', $keeperId)
                ->get(['version_number'])
                ->map(static fn (object $version): int => (int) $version->version_number)
                ->max() + 1;

            foreach ($documents->slice(1) as $duplicate) {
                $duplicateId = (int) $duplicate->document_id;
                $versions = DB::table('document_versions')
                    ->where('document_id', $duplicateId)
                    ->orderBy('created_at')
                    ->orderBy('version_id')
                    ->get();

                foreach ($versions as $version) {
                    $filePath = (string) $version->file_path;
                    $newPath = $this->restoreOriginalFilename(
                        $filePath,
                        (string) ($duplicate->document_name ?? ''),
                    );

                    DB::table('document_versions')
                        ->where('version_id', $version->version_id)
                        ->update([
                            'document_id' => $keeperId,
                            'file_path' => $newPath,
                            'version_number' => (string) $nextVersion++,
                        ]);
                }

                if (Schema::hasTable('document_transmittals')) {
                    DB::table('document_transmittals')
                        ->where('document_id', $duplicateId)
                        ->delete();
                }

                DB::table('documents')
                    ->where('document_id', $duplicateId)
                    ->delete();
            }
        }
    }

    public function down(): void
    {
        // The old duplicate rows cannot be recreated without knowing the
        // original upload batch boundaries. This migration is intentionally
        // one-way because it keeps every uploaded file as a version.
    }

    /**
     * Do not merge a record that has already been acted on elsewhere.
     *
     * @param list<int> $documentIds
     */
    private function hasProtectedRelatedRecords(array $documentIds): bool
    {
        foreach ([
            'document_requests',
            'notes',
            'activity_logs',
            'rejected_documents',
            'conversations',
        ] as $table) {
            if (Schema::hasTable($table)
                && DB::table($table)->whereIn('document_id', $documentIds)->exists()) {
                return true;
            }
        }

        return false;
    }

    private function restoreOriginalFilename(string $filePath, string $originalName): string
    {
        $originalName = basename(str_replace('\\', '/', $originalName));

        if ($originalName === '' || $filePath === '' || basename($filePath) === $originalName) {
            return $filePath;
        }

        $directory = trim(dirname($filePath), '/.');
        $newPath = ($directory !== '' ? $directory . '/' : '') . $originalName;
        $sourceDisk = null;

        foreach (['local', 'public'] as $diskName) {
            if (Storage::disk($diskName)->exists($filePath)) {
                $sourceDisk = Storage::disk($diskName);

                break;
            }
        }

        if ($sourceDisk === null || $sourceDisk->exists($newPath)) {
            return $filePath;
        }

        $sourceDisk->move($filePath, $newPath);

        return $newPath;
    }
};
