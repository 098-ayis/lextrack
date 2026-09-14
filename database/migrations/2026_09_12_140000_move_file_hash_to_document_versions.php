<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const string VERSION_HASH_INDEX = 'document_versions_user_id_file_hash_unique';

    private const string DOCUMENT_HASH_INDEX = 'documents_user_id_file_hash_unique';

    public function up(): void
    {
        if (! Schema::hasColumn('document_versions', 'file_hash')) {
            Schema::table('document_versions', function (Blueprint $table): void {
                $table->string('file_hash', 64)
                    ->nullable()
                    ->after('file_path');
            });
        }

        if (! in_array(self::VERSION_HASH_INDEX, Schema::getIndexListing('document_versions'), true)) {
            Schema::table('document_versions', function (Blueprint $table): void {
                $table->unique(
                    ['user_id', 'file_hash'],
                    self::VERSION_HASH_INDEX,
                );
            });
        }

        if (! Schema::hasColumn('documents', 'file_hash')) {
            return;
        }

        DB::table('documents')
            ->whereNotNull('file_hash')
            ->where('file_hash', '!=', '')
            ->orderBy('document_id')
            ->get(['document_id', 'file_hash'])
            ->each(function (object $document): void {
                $versionId = DB::table('document_versions')
                    ->where('document_id', $document->document_id)
                    ->orderBy('version_id')
                    ->value('version_id');

                if ($versionId === null) {
                    return;
                }

                DB::table('document_versions')
                    ->where('version_id', $versionId)
                    ->whereNull('file_hash')
                    ->update(['file_hash' => $document->file_hash]);
            });

        $documentsUserForeignKey = null;

        if (in_array(self::DOCUMENT_HASH_INDEX, Schema::getIndexListing('documents'), true)) {
            $documentsUserForeignKey = $this->detachUserForeignKey('documents');

            Schema::table('documents', function (Blueprint $table): void {
                $table->dropUnique(self::DOCUMENT_HASH_INDEX);
            });

            $this->restoreUserForeignKey('documents', $documentsUserForeignKey);
        }

        Schema::table('documents', function (Blueprint $table): void {
            $table->dropColumn('file_hash');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('documents', 'file_hash')) {
            Schema::table('documents', function (Blueprint $table): void {
                $table->string('file_hash', 64)
                    ->nullable()
                    ->after('user_id');
            });
        }

        if (! in_array(self::DOCUMENT_HASH_INDEX, Schema::getIndexListing('documents'), true)) {
            Schema::table('documents', function (Blueprint $table): void {
                $table->unique(
                    ['user_id', 'file_hash'],
                    self::DOCUMENT_HASH_INDEX,
                );
            });
        }

        DB::table('document_versions')
            ->whereNotNull('file_hash')
            ->where('file_hash', '!=', '')
            ->orderBy('version_id')
            ->get(['document_id', 'file_hash'])
            ->each(function (object $version): void {
                DB::table('documents')
                    ->where('document_id', $version->document_id)
                    ->whereNull('file_hash')
                    ->update(['file_hash' => $version->file_hash]);
            });

        if (Schema::hasColumn('document_versions', 'file_hash')) {
            $documentVersionsUserForeignKey = null;

            if (in_array(self::VERSION_HASH_INDEX, Schema::getIndexListing('document_versions'), true)) {
                $documentVersionsUserForeignKey = $this->detachUserForeignKey('document_versions');

                Schema::table('document_versions', function (Blueprint $table): void {
                    $table->dropUnique(self::VERSION_HASH_INDEX);
                });

                $this->restoreUserForeignKey('document_versions', $documentVersionsUserForeignKey);
            }

            Schema::table('document_versions', function (Blueprint $table): void {
                $table->dropColumn('file_hash');
            });
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function detachUserForeignKey(string $table): ?array
    {
        $foreignKey = collect(Schema::getForeignKeys($table))
            ->first(fn (array $key): bool => $key['columns'] === ['user_id']);

        if ($foreignKey === null) {
            return null;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($foreignKey): void {
            $blueprint->dropForeign($foreignKey['name'] ?? ['user_id']);
        });

        return $foreignKey;
    }

    /**
     * @param array<string, mixed>|null $foreignKey
     */
    private function restoreUserForeignKey(string $table, ?array $foreignKey): void
    {
        if ($foreignKey === null || Schema::hasForeignKey($table, ['user_id'])) {
            return;
        }

        if (! Schema::hasIndex($table, ['user_id'])) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->index('user_id');
            });
        }

        Schema::table($table, function (Blueprint $blueprint) use ($foreignKey): void {
            $definition = $blueprint
                ->foreign('user_id', $foreignKey['name'] ?? null)
                ->references($foreignKey['foreign_columns'][0] ?? 'id')
                ->on($foreignKey['foreign_table'] ?? 'users');

            match (strtolower((string) ($foreignKey['on_delete'] ?? ''))) {
                'cascade' => $definition->cascadeOnDelete(),
                'set null' => $definition->nullOnDelete(),
                default => $definition->restrictOnDelete(),
            };
        });
    }
};
