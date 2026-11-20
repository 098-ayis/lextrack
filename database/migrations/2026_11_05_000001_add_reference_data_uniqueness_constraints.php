<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('document_types') || ! Schema::hasTable('action_types')) {
            throw new \RuntimeException('Reference data tables must exist before adding uniqueness constraints.');
        }

        if (! Schema::hasColumn('document_types', 'type_name') || ! Schema::hasColumn('action_types', 'action_name')) {
            throw new \RuntimeException('Reference data name columns must exist before adding uniqueness constraints.');
        }

        $this->failOnDuplicates('document_types', 'type_name');
        $this->failOnDuplicates('action_types', 'action_name');

        Schema::table('document_types', function ($table): void {
            $table->unique('type_name', 'document_types_type_name_unique');
        });

        Schema::table('action_types', function ($table): void {
            $table->unique('action_name', 'action_types_action_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('document_types', function ($table): void {
            $table->dropUnique('document_types_type_name_unique');
        });

        Schema::table('action_types', function ($table): void {
            $table->dropUnique('action_types_action_name_unique');
        });
    }

    private function failOnDuplicates(string $table, string $column): void
    {
        try {
            $duplicates = DB::table($table)
                ->selectRaw("LOWER(TRIM(`{$column}`)) AS normalized_name, COUNT(*) AS duplicate_count")
                ->whereNotNull($column)
                ->groupBy('normalized_name')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('duplicate_count', 'normalized_name')
                ->all();
        } catch (QueryException $exception) {
            throw new \RuntimeException("Unable to inspect duplicate {$table}.{$column} values safely.", 0, $exception);
        }

        if ($duplicates !== []) {
            throw new \RuntimeException(sprintf(
                'Cannot add the unique constraint for %s.%s. Resolve these duplicate values first: %s',
                $table,
                $column,
                implode(', ', array_keys($duplicates)),
            ));
        }
    }
};
