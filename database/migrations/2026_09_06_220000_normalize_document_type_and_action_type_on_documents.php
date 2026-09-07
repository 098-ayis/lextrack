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
            $table->string('document_type')->nullable()->after('type_id');
            $table->string('action_type')->nullable()->after('action_id');
        });

        DB::table('documents')
            ->leftJoin('document_types', 'document_types.type_id', '=', 'documents.type_id')
            ->leftJoin('action_types', 'action_types.action_id', '=', 'documents.action_id')
            ->select([
                'documents.document_id',
                'documents.other_document_type',
                'documents.action_taken',
                'document_types.type_name',
                'action_types.action_name',
            ])
            ->orderBy('documents.document_id')
            ->get()
            ->each(function (object $document): void {
                $customDocumentType = trim((string) $document->other_document_type);
                $documentType = $customDocumentType !== ''
                    ? $customDocumentType
                    : trim((string) $document->type_name);

                $actionTaken = trim((string) $document->action_taken);
                $actionType = $actionTaken !== ''
                    ? $actionTaken
                    : trim((string) $document->action_name);

                DB::table('documents')
                    ->where('document_id', $document->document_id)
                    ->update([
                        'document_type' => $documentType !== '' ? $documentType : null,
                        'action_type' => $actionType !== '' ? $actionType : null,
                    ]);
            });

        Schema::table('documents', function (Blueprint $table): void {
            $table->dropForeign(['type_id']);
            $table->dropForeign(['action_id']);
            $table->dropColumn([
                'type_id',
                'other_document_type',
                'action_id',
                'action_taken',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->unsignedBigInteger('type_id')->nullable()->after('document_type');
            $table->unsignedBigInteger('action_id')->nullable()->after('action_type');
            $table->string('other_document_type')->nullable()->after('type_id');
            $table->string('action_taken')->nullable()->after('action_id');
        });

        DB::table('documents')
            ->select(['document_id', 'document_type', 'action_type'])
            ->orderBy('document_id')
            ->get()
            ->each(function (object $document): void {
                $typeId = filled($document->document_type)
                    ? DB::table('document_types')
                        ->where('type_name', $document->document_type)
                        ->value('type_id')
                    : null;

                $actionId = filled($document->action_type)
                    ? DB::table('action_types')
                        ->where('action_name', $document->action_type)
                        ->value('action_id')
                    : null;

                DB::table('documents')
                    ->where('document_id', $document->document_id)
                    ->update([
                        'type_id' => $typeId,
                        'other_document_type' => $typeId === null ? $document->document_type : null,
                        'action_id' => $actionId,
                        'action_taken' => $actionId === null ? $document->action_type : null,
                    ]);
            });

        Schema::table('documents', function (Blueprint $table): void {
            $table->foreign('type_id')->references('type_id')->on('document_types');
            $table->foreign('action_id')->references('action_id')->on('action_types')->nullOnDelete();
            $table->dropColumn(['document_type', 'action_type']);
        });
    }
};
