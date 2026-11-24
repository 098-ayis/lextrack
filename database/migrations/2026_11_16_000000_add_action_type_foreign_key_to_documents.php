<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('action_types', function (Blueprint $table): void {
            $table->boolean('allows_custom_value')->default(false)->after('color');
        });

        $now = now();
        $otherActionTypeId = DB::table('action_types')
            ->whereRaw('LOWER(TRIM(action_name)) = ?', ['others'])
            ->orderBy('action_id')
            ->value('action_id');

        if ($otherActionTypeId === null) {
            $otherActionTypeId = DB::table('action_types')->insertGetId([
                'action_name' => 'Others',
                'color' => '#64748B',
                'allows_custom_value' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ], 'action_id');
        } else {
            DB::table('action_types')
                ->whereRaw('LOWER(TRIM(action_name)) = ?', ['others'])
                ->update(['allows_custom_value' => true, 'updated_at' => $now]);
        }

        Schema::table('documents', function (Blueprint $table): void {
            $table->foreignId('action_type_id')
                ->nullable()
                ->after('action_type')
                ->constrained('action_types', 'action_id')
                ->restrictOnDelete();
        });

        $actionTypes = DB::table('action_types')
            ->orderBy('action_id')
            ->get(['action_id', 'action_name'])
            ->keyBy(fn (object $actionType): string => mb_strtolower(trim((string) $actionType->action_name)));

        DB::table('documents')
            ->select(['document_id', 'action_type'])
            ->orderBy('document_id')
            ->chunkById(200, function ($documents) use ($actionTypes): void {
                foreach ($documents as $document) {
                    $key = mb_strtolower(trim((string) $document->action_type));

                    DB::table('documents')
                        ->where('document_id', $document->document_id)
                        ->update([
                            'action_type_id' => $key !== ''
                                ? $actionTypes->get($key)?->action_id
                                : null,
                        ]);
                }
            }, 'document_id');
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropForeign(['action_type_id']);
            $table->dropColumn('action_type_id');
        });

        Schema::table('action_types', function (Blueprint $table): void {
            $table->dropColumn('allows_custom_value');
        });
    }
};
