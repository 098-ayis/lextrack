<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->string('office_unit')->nullable()->after('office_unit_id');
        });

        DB::table('documents')
            ->leftJoin('office_units', 'office_units.office_unit_id', '=', 'documents.office_unit_id')
            ->select([
                'documents.document_id',
                'office_units.name',
            ])
            ->orderBy('documents.document_id')
            ->get()
            ->each(function (object $document): void {
                DB::table('documents')
                    ->where('document_id', $document->document_id)
                    ->update([
                        'office_unit' => filled($document->name)
                            ? trim((string) $document->name)
                            : null,
                    ]);
            });

        Schema::table('documents', function (Blueprint $table): void {
            $table->dropForeign(['office_unit_id']);
            $table->dropColumn('office_unit_id');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->foreignId('office_unit_id')
                ->nullable()
                ->after('office_unit')
                ->constrained('office_units', 'office_unit_id')
                ->nullOnDelete();
        });

        DB::table('documents')
            ->select(['document_id', 'office_unit'])
            ->orderBy('document_id')
            ->get()
            ->each(function (object $document): void {
                $officeUnitId = filled($document->office_unit)
                    ? DB::table('office_units')
                        ->where('name', $document->office_unit)
                        ->value('office_unit_id')
                    : null;

                if ($officeUnitId === null && filled($document->office_unit)) {
                    $officeUnitId = DB::table('office_units')->insertGetId([
                        'name' => trim((string) $document->office_unit),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('documents')
                    ->where('document_id', $document->document_id)
                    ->update(['office_unit_id' => $officeUnitId]);
            });

        Schema::table('documents', function (Blueprint $table): void {
            $table->dropColumn('office_unit');
        });
    }
};
