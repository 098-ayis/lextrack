<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('office_units', function (Blueprint $table): void {
            $table->id('office_unit_id');
            $table->string('name')->unique();
            $table->string('color', 7)->nullable();
            $table->timestamps();
        });

        Schema::table('documents', function (Blueprint $table): void {
            $table->foreignId('office_unit_id')
                ->nullable()
                ->after('lao_number')
                ->constrained('office_units', 'office_unit_id')
                ->nullOnDelete();
        });

        $legacyOfficeUnits = DB::table('documents')
            ->whereNotNull('office_unit')
            ->where('office_unit', '<>', '')
            ->distinct()
            ->pluck('office_unit')
            ->map(fn (string $name): string => trim($name))
            ->filter()
            ->unique()
            ->values();

        foreach ($legacyOfficeUnits as $name) {
            $officeUnitId = DB::table('office_units')
                ->where('name', $name)
                ->value('office_unit_id');

            if ($officeUnitId === null) {
                $officeUnitId = DB::table('office_units')->insertGetId([
                    'name' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('documents')
                ->whereRaw('TRIM(office_unit) = ?', [$name])
                ->update(['office_unit_id' => $officeUnitId]);
        }

        Schema::table('documents', function (Blueprint $table): void {
            $table->dropColumn('office_unit');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->string('office_unit')->nullable()->after('lao_number');
        });

        DB::table('documents')
            ->select(['document_id', 'office_unit_id'])
            ->whereNotNull('office_unit_id')
            ->orderBy('document_id')
            ->each(function (object $document): void {
                $name = DB::table('office_units')
                    ->where('office_unit_id', $document->office_unit_id)
                    ->value('name');

                DB::table('documents')
                    ->where('document_id', $document->document_id)
                    ->update(['office_unit' => $name]);
            });

        Schema::table('documents', function (Blueprint $table): void {
            $table->dropForeign(['office_unit_id']);
            $table->dropColumn('office_unit_id');
        });

        Schema::dropIfExists('office_units');
    }
};
