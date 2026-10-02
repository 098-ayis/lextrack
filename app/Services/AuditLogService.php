<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    public function record(
        string $actionType,
        string $description,
        ?Model $subject = null,
        ?int $documentId = null,
    ): ?ActivityLog {
        $actor = auth()->user();

        if (! $actor instanceof User || ! $actor->isAdmin()) {
            return null;
        }

        if ($subject instanceof Document) {
            $documentId ??= (int) $subject->getKey();
        }

        return ActivityLog::create([
            'user_id' => $actor->getKey(),
            'document_id' => $documentId,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->getKey(),
            'action_type' => $actionType,
            'action_details' => $description,
        ]);
    }
}
