<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_types', function (Blueprint $table): void {
            $table->boolean('allows_custom_value')->default(false)->after('days_to_process');
        });

        Schema::table('office_units', function (Blueprint $table): void {
            $table->boolean('allows_custom_value')->default(false)->after('color');
        });

        $now = now();

        $otherDocumentTypeId = DB::table('document_types')
            ->whereRaw('LOWER(TRIM(type_name)) = ?', ['others'])
            ->orderBy('type_id')
            ->value('type_id');

        if ($otherDocumentTypeId === null) {
            $otherDocumentTypeId = DB::table('document_types')->insertGetId([
                'type_name' => 'Others',
                'type_desc' => 'Custom document type',
                'allows_custom_value' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ], 'type_id');
        } else {
            DB::table('document_types')
                ->where('type_id', $otherDocumentTypeId)
                ->update(['allows_custom_value' => true, 'updated_at' => $now]);
        }

        $otherOfficeUnitId = DB::table('office_units')
            ->whereRaw('LOWER(TRIM(name)) = ?', ['others'])
            ->orderBy('office_unit_id')
            ->value('office_unit_id');

        if ($otherOfficeUnitId === null) {
            $otherOfficeUnitId = DB::table('office_units')->insertGetId([
                'name' => 'Others',
                'allows_custom_value' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ], 'office_unit_id');
        } else {
            DB::table('office_units')
                ->where('office_unit_id', $otherOfficeUnitId)
                ->update(['allows_custom_value' => true, 'updated_at' => $now]);
        }

        Schema::table('documents', function (Blueprint $table): void {
            $table->foreignId('document_type_id')
                ->nullable()
                ->after('document_type')
                ->constrained('document_types', 'type_id')
                ->restrictOnDelete();

            $table->foreignId('office_unit_id')
                ->nullable()
                ->after('office_unit')
                ->constrained('office_units', 'office_unit_id')
                ->restrictOnDelete();
        });

        $documentTypes = DB::table('document_types')
            ->orderBy('type_id')
            ->get(['type_id', 'type_name'])
            ->keyBy(fn (object $type): string => mb_strtolower(trim((string) $type->type_name)));

        $officeUnits = DB::table('office_units')
            ->orderBy('office_unit_id')
            ->get(['office_unit_id', 'name'])
            ->keyBy(fn (object $office): string => mb_strtolower(trim((string) $office->name)));

        DB::table('documents')
            ->select(['document_id', 'document_type', 'office_unit'])
            ->orderBy('document_id')
            ->chunkById(200, function ($documents) use ($documentTypes, $officeUnits): void {
                foreach ($documents as $document) {
                    $typeKey = mb_strtolower(trim((string) $document->document_type));
                    $officeKey = mb_strtolower(trim((string) $document->office_unit));

                    DB::table('documents')
                        ->where('document_id', $document->document_id)
                        ->update([
                            'document_type_id' => $typeKey !== ''
                                ? $documentTypes->get($typeKey)?->type_id
                                : null,
                            'office_unit_id' => $officeKey !== ''
                                ? $officeUnits->get($officeKey)?->office_unit_id
                                : null,
                        ]);
                }
            }, 'document_id');
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropForeign(['document_type_id']);
            $table->dropForeign(['office_unit_id']);
            $table->dropColumn(['document_type_id', 'office_unit_id']);
        });

        Schema::table('document_types', function (Blueprint $table): void {
            $table->dropColumn('allows_custom_value');
        });

        Schema::table('office_units', function (Blueprint $table): void {
            $table->dropColumn('allows_custom_value');
        });
    }
};
