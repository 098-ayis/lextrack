<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\RejectedDocument;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class ClientDocumentLookupService
{
    private const NO_AUTHORIZED_MATCH = 'I couldn’t find an authorized document with that LAO number.';

    private const CHATBOT_DOCUMENT_CHOICE_LIMIT = 5;

    public function latestStatus(User $user): string
    {
        return $this->latestStatusResult($user)['reply'];
    }

    /** @return array{reply: string, document_id: ?int, status: ?string} */
    public function latestSubmissionDateResult(User $user): array
    {
        $document = $this->submittedDocuments($user)
            ->orderByDesc('created_at')
            ->orderByDesc('document_id')
            ->first(['document_id', 'status', 'created_at']);

        if (! $document) {
            return $this->documentResult('You have no submitted documents yet.');
        }

        return $this->documentResult(
            $document->created_at?->format('F j, Y') !== null
                ? 'Submitted on ' . $document->created_at->format('F j, Y') . '.'
                : 'The submission date is not recorded.',
            (int) $document->document_id,
            (string) $document->status,
        );
    }

    /**
     * Resolve the latest document status and a session-safe reference for local
     * follow-ups. The reference is never included in an AI prompt.
     *
     * @return array{reply: string, document_id: ?int, status: ?string}
     */
    public function latestStatusResult(User $user): array
    {
        $document = $this->submittedDocuments($user)
            ->orderByDesc('created_at')
            ->orderByDesc('document_id')
            ->first([
                'document_id',
                'status',
                'lao_number',
                'document_name',
                'document_type',
            ]);

        if (! $document) {
            return $this->documentResult('You have no submitted documents yet.');
        }

        $label = $this->documentDisplayName($document);

        if (filled($label)) {
            $latestSubmittedLabel = 'Your latest submitted document "' . $label . '"';

            return $this->documentResult(
                $this->statusResponse(
                    $user,
                    (int) $document->document_id,
                    (string) $document->status,
                    filled($document->lao_number) ? (string) $document->lao_number : null,
                    $label,
                    null,
                    'english',
                    $latestSubmittedLabel,
                ),
                (int) $document->document_id,
                (string) $document->status,
            );
        }

        $reply = match ($document->status) {

            'pending' =>
                $this->withDocumentLabel($label, 'Your latest submitted document is currently Pending. ')
                . 'It is awaiting initial review and validation by the Legal Affairs Office.',

            'in_progress' =>
                $this->withDocumentLabel($label, 'Your latest submitted document is currently In Progress.'),

            'outgoing' =>
                $this->withDocumentLabel($label, 'Your latest submitted document is currently Outgoing.'),

            'completed' =>
                $this->withDocumentLabel($label, 'Your latest submitted document is Completed.'),

            'returned' =>
                $this->withDocumentLabel($label, 'Your latest submitted document is currently Returned. ')
                . 'Please check the Documents or Messages page for details.',

            'rejected' =>
                $this->withDocumentLabel($label, 'Your latest submitted document was Rejected. ')
                . 'Please review its recorded rejection reason in the Documents page.',

            'archived' =>
                $this->withDocumentLabel($label, 'Your latest submitted document is Archived — Retained for Records. ')
                . 'It is no longer undergoing active processing and has been retained '
                . 'by the Legal Affairs Office for future reference. '
                . 'Archiving does not necessarily mean that processing was completed.',

            default =>
                'The current document status is unavailable.',
        };

        return $this->documentResult($reply, (int) $document->document_id, (string) $document->status);
    }

    /**
     * Answer whether the latest authorized submission is processed without
     * treating “processed” as a synonym for Completed.
     *
     * @return array{reply: string, document_id: ?int, status: ?string}
     */
    public function latestProcessingStatusResult(User $user, string $language = 'english'): array
    {
        $document = $this->submittedDocuments($user)
            ->orderByDesc('created_at')
            ->orderByDesc('document_id')
            ->first(['document_id']);

        if (! $document) {
            return $this->documentResult(
                in_array($language, ['filipino', 'taglish'], true)
                    ? 'Wala ka pang naisumiteng document.'
                    : 'You have no submitted documents yet.',
            );
        }

        return $this->processingStatusByDocumentIdResult($user, (int) $document->document_id, $language);
    }

    /**
     * Check the current status of one authorized document against the
     * client's “processed/completed” question.
     *
     * @return array{reply: string, document_id: ?int, status: ?string}
     */
    public function processingStatusByDocumentIdResult(
        User $user,
        int $documentId,
        string $language = 'english',
    ): array {
        $document = Document::query()
            ->where('user_id', $user->getKey())
            ->where('document_id', $documentId)
            ->first([
                'document_id',
                'status',
                'document_name',
                'document_type',
                'lao_number',
            ]);

        if (! $document) {
            return $this->documentResult(self::NO_AUTHORIZED_MATCH);
        }

        $filipinoLike = in_array($language, ['filipino', 'taglish'], true);
        $label = $this->documentLabel(
            $this->documentDisplayName($document),
            filled($document->lao_number) ? (string) $document->lao_number : null,
        );
        $status = (string) $document->status;
        $statusLabel = $this->statusLabel($status);

        $reply = match (true) {
            $status === 'completed' => $filipinoLike
                ? "Oo. {$label} ay Completed."
                : "Yes. {$label} is Completed.",
            $status === 'rejected' => $filipinoLike
                ? "Hindi. {$label} ay Rejected."
                : "No. {$label} is Rejected.",
            $filipinoLike => "Hindi pa. {$label} ay kasalukuyang {$statusLabel}.",
            default => "Not yet. {$label} is currently {$statusLabel}.",
        };

        return $this->documentResult($reply, (int) $document->document_id, $status);
    }

    /**
     * Answer a combined “completed?” and “when was it completed?” question
     * from one owner-scoped record lookup. Completion time comes only from
     * the verified activity/status history.
     *
     * @return array{reply: string, document_id: ?int, status: ?string}
     */
    public function processingAndCompletionDateResult(
        User $user,
        int $documentId,
        string $language = 'english',
    ): array {
        $document = Document::query()
            ->where('user_id', $user->getKey())
            ->where('document_id', $documentId)
            ->first([
                'document_id',
                'status',
                'document_name',
                'document_type',
                'lao_number',
            ]);

        if (! $document) {
            return $this->documentResult(self::NO_AUTHORIZED_MATCH);
        }

        $filipinoLike = in_array($language, ['filipino', 'taglish'], true);
        $label = $this->documentLabel(
            $this->documentDisplayName($document),
            filled($document->lao_number) ? (string) $document->lao_number : null,
        );
        $status = (string) $document->status;
        $statusLabel = $this->statusLabel($status);

        if ($status !== 'completed') {
            $reply = $filipinoLike
                ? "Hindi. {$label} ay kasalukuyang {$statusLabel}. Wala pang verified completion date."
                : "No. {$label} is currently {$statusLabel}. No verified completion date is available.";

            return $this->documentResult($reply, (int) $document->document_id, $status);
        }

        $completionDate = $this->verifiedCompletionDate($document);
        if ($completionDate === null) {
            $reply = $filipinoLike
                ? "Oo. {$label} ay Completed, pero walang verified completion date na nakatala."
                : "Yes. {$label} is Completed, but no verified completion date is recorded.";

            return $this->documentResult($reply, (int) $document->document_id, $status);
        }

        $formatted = $completionDate->format('F j, Y g:i A');
        $reply = $filipinoLike
            ? "Oo. {$label} ay Completed. Completion date: {$formatted}."
            : "Yes. {$label} is Completed. Completion date: {$formatted}.";

        return $this->documentResult($reply, (int) $document->document_id, $status);
    }

    /**
     * Return a verified completion date only when the owned record is
     * currently Completed and the activity history contains that transition.
     *
     * @return array{reply: string, document_id: ?int, status: ?string}
     */
    public function completionDateByDocumentIdResult(
        User $user,
        int $documentId,
        string $language = 'english',
    ): array {
        $document = Document::query()
            ->where('user_id', $user->getKey())
            ->where('document_id', $documentId)
            ->first([
                'document_id',
                'status',
                'document_name',
                'document_type',
                'lao_number',
            ]);

        if (! $document) {
            return $this->documentResult(self::NO_AUTHORIZED_MATCH);
        }

        $filipinoLike = in_array($language, ['filipino', 'taglish'], true);
        $label = $this->documentLabel(
            $this->documentDisplayName($document),
            filled($document->lao_number) ? (string) $document->lao_number : null,
        );
        $status = (string) $document->status;
        $statusLabel = $this->statusLabel($status);

        if ($status !== 'completed') {
            $reply = $filipinoLike
                ? "Hindi pa Completed ang {$label}; kasalukuyang {$statusLabel} ito. Wala pang verified completion date."
                : "{$label} is currently {$statusLabel}, not Completed. No verified completion date is available.";

            return $this->documentResult($reply, (int) $document->document_id, $status);
        }

        $completionDate = $this->verifiedCompletionDate($document);
        if ($completionDate === null) {
            $reply = $filipinoLike
                ? "{$label} ay Completed, pero walang verified completion date na nakatala."
                : "{$label} is Completed, but no verified completion date is recorded.";

            return $this->documentResult($reply, (int) $document->document_id, $status);
        }

        $formatted = $completionDate->format('F j, Y g:i A');
        $reply = $filipinoLike
            ? "{$label} ay Completed. Completion date: {$formatted}."
            : "{$label} is Completed. Completion date: {$formatted}.";

        return $this->documentResult($reply, (int) $document->document_id, $status);
    }

    /** @return array{reply: string, document_id: ?int, status: ?string} */
    public function latestCompletionDateResult(User $user, string $language = 'english'): array
    {
        $document = $this->submittedDocuments($user)
            ->orderByDesc('created_at')
            ->orderByDesc('document_id')
            ->first(['document_id']);

        if (! $document) {
            return $this->documentResult(
                in_array($language, ['filipino', 'taglish'], true)
                    ? 'Wala ka pang naisumiteng document.'
                    : 'You have no submitted documents yet.',
            );
        }

        return $this->completionDateByDocumentIdResult($user, (int) $document->document_id, $language);
    }

    /**
     * Resolve the authenticated client's most recently updated submitted
     * document. This is deliberately separate from latestStatusResult(),
     * which orders by submission time.
     *
     * @return array{reply: string, document_id: ?int, status: ?string}
     */
    public function mostRecentlyUpdatedResult(User $user, string $language = 'english'): array
    {
        $document = $this->submittedDocuments($user)
            ->orderByDesc('updated_at')
            ->orderByDesc('document_id')
            ->first([
                'document_id',
                'status',
                'lao_number',
                'document_name',
                'document_type',
                'updated_at',
            ]);

        if (! $document) {
            return $this->documentResult(
                in_array($language, ['filipino', 'taglish'], true)
                    ? 'Wala kang submitted document na may recorded update.'
                    : 'You have no submitted document with a recorded update.',
            );
        }

        $filipinoLike = in_array($language, ['filipino', 'taglish'], true);
        $name = $this->documentDisplayName($document)
            ?? ($filipinoLike ? 'Walang pangalan na nakatala' : 'Name not recorded');
        $status = $this->statusLabel((string) $document->status);
        $laoNumber = filled($document->lao_number)
            ? (string) $document->lao_number
            : ($filipinoLike ? 'Hindi pa nakatalaga' : 'Not yet assigned');
        $activity = $this->latestVerifiedActivity($document, $filipinoLike);
        $updatedAt = $document->updated_at?->format('F j, Y g:i A')
            ?? ($filipinoLike ? 'hindi nakatala' : 'not recorded');

        $lines = $filipinoLike
            ? [
                'Pinakahuling na-update na document: ' . $name,
                'Status: ' . $status,
                'LAO number: ' . $laoNumber,
                'Huling verified activity: ' . $activity,
                'Na-update: ' . $updatedAt,
            ]
            : [
                'Most recently updated document: ' . $name,
                'Status: ' . $status,
                'LAO number: ' . $laoNumber,
                'Latest verified activity: ' . $activity,
                'Updated: ' . $updatedAt,
            ];

        return $this->documentResult(
            implode("\n", $lines),
            (int) $document->document_id,
            (string) $document->status,
        );
    }

    public function statusByLaoNumber(User $user, string $laoNumber): string
    {
        return $this->statusByLaoNumberResult($user, $laoNumber)['reply'];
    }

    /**
     * @return array{reply: string, document_id: ?int, status: ?string}
     */
    public function statusByLaoNumberResult(User $user, string $laoNumber): array
    {
        $laoNumber = strtoupper(trim($laoNumber));

        if (preg_match('/^LAO-\d{2}-\d{3,}$/D', $laoNumber) !== 1) {
            return $this->documentResult('Please enter a valid LAO number in the format LAO-26-009.');
        }

        // Scope by both authenticated owner and exact identifier before reading
        // even the status. Never perform an unrestricted fallback lookup.
        $document = Document::query()
            ->where('user_id', $user->getKey())
            ->where('lao_number', $laoNumber)
            ->first([
                'document_id',
                'status',
                'lao_number',
                'document_name',
                'document_type',
            ]);

        if (! $document) {
            return $this->documentResult(self::NO_AUTHORIZED_MATCH);
        }

        return $this->documentResult(
            $this->statusResponse(
                $user,
                (int) $document->document_id,
                (string) $document->status,
                $laoNumber,
                $this->documentDisplayName($document),
            ),
            (int) $document->document_id,
            (string) $document->status,
        );
    }

    public function statusByDocumentId(User $user, int $documentId): string
    {
        return $this->statusByDocumentIdResult($user, $documentId)['reply'];
    }

    /**
     * @return array{reply: string, document_id: ?int, status: ?string}
     */
    public function statusByDocumentIdResult(
        User $user,
        int $documentId,
        ?string $topic = null,
        string $language = 'english',
    ): array
    {
        // Selection indexes are session-scoped hints, not authorization. Re-check
        // the authenticated owner before reading any document status.
        $document = Document::query()
            ->where('user_id', $user->getKey())
            ->where('document_id', $documentId)
            ->first($this->documentStatusColumns());

        if (! $document) {
            return $this->documentResult(self::NO_AUTHORIZED_MATCH);
        }

        return $this->documentResult(
            $this->statusResponse(
                $user,
                (int) $document->document_id,
                (string) $document->status,
                filled($document->lao_number) ? (string) $document->lao_number : null,
                $this->documentDisplayName($document),
                $topic,
                $language,
            ),
            (int) $document->document_id,
            (string) $document->status,
        );
    }

    /**
     * Return a rejection reason only for an owned document whose current
     * status is actually Rejected. Ownership and status are checked in the
     * same query; the reason is never read for another status.
     *
     * @return array{reply: string, document_id: ?int, status: ?string}
     */
    public function rejectionReasonByDocumentIdResult(User $user, int $documentId): array
    {
        $document = Document::query()
            ->where('user_id', $user->getKey())
            ->where('document_id', $documentId)
            ->first([
                'document_id',
                'lao_number',
                'status',
                'document_name',
                'document_type',
                'rejection_reason',
            ]);

        if (! $document) {
            return $this->documentResult(self::NO_AUTHORIZED_MATCH);
        }

        $label = $this->documentLabel(
            $this->documentDisplayName($document),
            filled($document->lao_number) ? (string) $document->lao_number : null,
        );
        $status = (string) $document->status;

        if ($status !== 'rejected') {
            return $this->documentResult(
                "{$label} is currently " . $this->statusLabel($status) . ". I can show a rejection reason only when the current status is Rejected.",
                (int) $document->document_id,
                $status,
            );
        }

        $reason = filled($document->rejection_reason)
            ? (string) $document->rejection_reason
            : null;

        // Some deployments keep rejection history in rejected_documents
        // instead of copying the reason onto documents. The document has
        // already passed the owner and current-status checks above.
        if ($reason === null && Schema::hasTable('rejected_documents')) {
            $reason = RejectedDocument::query()
                ->where('document_id', (int) $document->document_id)
                ->latest('created_at')
                ->value('reason');
        }

        if (! filled($reason)) {
            return $this->documentResult(
                "{$label} is Rejected. No rejection reason is recorded here. Please open the Messages page for the authorized instructions.",
                (int) $document->document_id,
                $status,
            );
        }

        return $this->documentResult(
            "{$label} is Rejected. Recorded rejection reason: {$reason}",
            (int) $document->document_id,
            $status,
        );
    }

    public function rejectionReasonByLaoNumberResult(User $user, string $laoNumber): array
    {
        $laoNumber = strtoupper(trim($laoNumber));

        if (preg_match('/^LAO-\d{2}-\d{3,}$/D', $laoNumber) !== 1) {
            return $this->documentResult('Please enter a valid LAO number in the format LAO-26-009.');
        }

        $document = Document::query()
            ->where('user_id', $user->getKey())
            ->where('lao_number', $laoNumber)
            ->first(['document_id']);

        if (! $document) {
            return $this->documentResult(self::NO_AUTHORIZED_MATCH);
        }

        return $this->rejectionReasonByDocumentIdResult($user, (int) $document->document_id);
    }

    /**
     * Local guidance for a follow-up about the selected client's own document.
     * Only current status is queried; no message body or document contents are read.
     */
    public function guidanceForAuthorizedDocument(
        User $user,
        int $documentId,
        string $topic,
        string $language = 'english',
    ): array
    {
        $document = Document::query()
            ->where('user_id', $user->getKey())
            ->where('document_id', $documentId)
            ->first(['document_id', 'status']);

        if (! $document) {
            return $this->documentResult(self::NO_AUTHORIZED_MATCH);
        }

        $status = (string) $document->status;
        $reply = match ($topic) {
            'acceptance' => $this->acceptanceGuidance($status, $language),
            'acceptance_check' => $this->acceptanceCheck($status, $language),
            'acceptance_state' => $this->acceptanceState($status, $language),
            'resubmission' => $this->resubmissionGuidance($status, $language),
            default => 'I can answer questions about the current status, acceptance process, or an authorized revision request. For the official instructions, check Messages.',
        };

        return $this->documentResult($reply, (int) $document->document_id, $status);
    }

    /** @return array<string, int> */
    public function documentCountsByStatus(User $user): array
    {
        $statuses = ['pending', 'in_progress', 'outgoing', 'completed', 'returned', 'rejected', 'archived'];
        $counts = $this->submittedDocuments($user)
            ->select('status')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return collect($statuses)
            ->mapWithKeys(static fn (string $status): array => [$status => (int) ($counts[$status] ?? 0)])
            ->all();
    }

    public function countDocumentsByStatus(User $user, string $status): int
    {
        return $this->documentCountsByStatus($user)[$status] ?? 0;
    }

    /**
     * Return all submitted documents owned by the authenticated client for an
     * explicit aggregate list request. This is separate from the five-item
     * selector methods, and returns only approved display fields.
     *
     * @return list<array{document_id: int, lao_number: ?string, document_type: ?string, display_name: ?string, status_label: string, submitted_at: string}>
     */
    public function authorizedDocumentList(User $user, ?string $status = null): array
    {
        $allowedStatuses = ['pending', 'in_progress', 'outgoing', 'completed', 'returned', 'rejected', 'archived'];

        if ($status !== null && ! in_array($status, $allowedStatuses, true)) {
            return [];
        }

        $query = $this->submittedDocuments($user);

        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('document_id')
            ->get($this->documentChoiceColumns())
            ->map(fn (Document $document): array => [
                'document_id' => (int) $document->document_id,
                'lao_number' => filled($document->lao_number) ? (string) $document->lao_number : null,
                'document_type' => filled($document->document_type) ? (string) $document->document_type : null,
                'display_name' => $this->documentDisplayName($document),
                'status_label' => $this->statusLabel((string) $document->status),
                'submitted_at' => $document->created_at?->format('F j, Y') ?? 'date unavailable',
            ])
            ->all();
    }

    /**
     * Search only the authenticated client's document metadata. Search terms
     * are matched across the title, type, description, and particulars, but
     * those source fields are never returned as chatbot output.
     *
     * The returned choices are selectors, never an authorization decision.
     * The owner is checked again by statusByDocumentIdResult() after selection.
     *
     * @return list<array{document_id: int, lao_number: ?string, document_type: ?string, display_name: ?string, status_label: string, submitted_at: string}>
     * @param ?string $searchField A validated schema field such as document_type.
     *                             Null searches the approved descriptive fields.
     */
    public function authorizedDocumentChoicesByName(User $user, string $name, ?string $searchField = null): array
    {
        $terms = $this->documentSearchTerms($name);

        if ($terms === []) {
            return [];
        }

        $searchFields = $this->availableTopicSearchFields();

        if ($searchFields === []) {
            return [];
        }

        if ($searchField !== null && ! in_array($searchField, $searchFields, true)) {
            return [];
        }

        $matchingFields = $searchField !== null ? [$searchField] : $searchFields;

        $query = $this->submittedDocuments($user)
            ->where(function ($query) use ($terms, $matchingFields): void {
                foreach ($terms as $term) {
                    $like = '%' . $term . '%';

                    $query->where(function ($termQuery) use ($like, $matchingFields): void {
                        foreach ($matchingFields as $field) {
                            $termQuery->orWhere($field, 'like', $like);
                        }
                    });
                }
            });

        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('document_id')
            ->limit(self::CHATBOT_DOCUMENT_CHOICE_LIMIT)
            ->get($this->documentChoiceColumns())
            ->map(fn (Document $document): array => [
                'document_id' => (int) $document->document_id,
                'lao_number' => filled($document->lao_number) ? (string) $document->lao_number : null,
                'document_type' => filled($document->document_type) ? (string) $document->document_type : null,
                'display_name' => $this->documentDisplayName($document),
                'status_label' => $this->statusLabel((string) $document->status),
                'submitted_at' => $document->created_at?->format('F j, Y') ?? 'date unavailable',
            ])
            ->all();
    }

    /**
     * Resolve a natural-language document reference without exposing the
     * searchable source fields. A configured document type is matched exactly
     * after dynamic lookup; otherwise the reference is searched across the
     * approved descriptive fields.
     *
     * @return list<array{document_id: int, lao_number: ?string, document_type: ?string, display_name: ?string, status_label: string, submitted_at: string}>
     */
    public function authorizedDocumentChoicesByReference(
        User $user,
        string $reference,
        ?string $referenceType = null,
    ): array {
        $configuredType = $this->configuredDocumentType($reference);

        if ($configuredType !== null) {
            return $this->authorizedDocumentChoicesByDocumentType($user, $configuredType);
        }

        if ($referenceType === 'document_type') {
            // Even when the deployment has not populated document_types,
            // preserve the caller's type intent and search only the actual
            // document_type column. Never broaden a recognized type into
            // descriptions or particulars.
            return $this->authorizedDocumentChoicesByName($user, $reference, 'document_type');
        }

        return $this->authorizedDocumentChoicesByName($user, $reference);
    }

    /**
     * Match an exact configured type for this authenticated client's records.
     *
     * @return list<array{document_id: int, lao_number: ?string, document_type: ?string, display_name: ?string, status_label: string, submitted_at: string}>
     */
    public function authorizedDocumentChoicesByDocumentType(User $user, string $documentType): array
    {
        if (! Schema::hasColumn('documents', 'document_type')) {
            return [];
        }

        $documentType = trim($documentType);
        if ($documentType === '') {
            return [];
        }

        return $this->submittedDocuments($user)
            ->whereRaw('LOWER(TRIM(document_type)) = ?', [mb_strtolower($documentType, 'UTF-8')])
            ->orderByDesc('created_at')
            ->orderByDesc('document_id')
            ->limit(self::CHATBOT_DOCUMENT_CHOICE_LIMIT)
            ->get($this->documentChoiceColumns())
            ->map(fn (Document $document): array => [
                'document_id' => (int) $document->document_id,
                'lao_number' => filled($document->lao_number) ? (string) $document->lao_number : null,
                'document_type' => filled($document->document_type) ? (string) $document->document_type : null,
                'display_name' => $this->documentDisplayName($document),
                'status_label' => $this->statusLabel((string) $document->status),
                'submitted_at' => $document->created_at?->format('F j, Y') ?? 'date unavailable',
            ])
            ->all();
    }

    private function configuredDocumentType(string $candidate): ?string
    {
        if (! Schema::hasTable('document_types')) {
            return null;
        }

        $needle = $this->normalizeReferenceValue($candidate);
        if ($needle === '') {
            return null;
        }

        $types = DocumentType::query()
            ->whereNotNull('type_name')
            ->pluck('type_name')
            ->map(static fn (mixed $value): string => trim((string) $value))
            ->filter(static fn (string $value): bool => $value !== '')
            ->values();

        foreach ($types as $type) {
            if ($this->normalizeReferenceValue($type) === $needle) {
                return $type;
            }
        }

        // Allow a small spelling variation without maintaining a hardcoded
        // list of document types. The configured value remains authoritative.
        $bestType = null;
        $bestDistance = PHP_INT_MAX;

        foreach ($types as $type) {
            $normalizedType = $this->normalizeReferenceValue($type);
            $distance = levenshtein($needle, $normalizedType);
            $threshold = max(1, (int) floor(max(mb_strlen($needle), mb_strlen($normalizedType)) / 5));

            if ($distance <= $threshold && $distance < $bestDistance) {
                $bestType = $type;
                $bestDistance = $distance;
            }
        }

        return $bestType;
    }

    private function normalizeReferenceValue(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', mb_strtolower($value, 'UTF-8')));
    }

    /**
     * Return minimal selectors for this client's documents with one validated
     * status. This prevents a rejection-reason request from listing unrelated
     * Pending, In Progress, or Outgoing records.
     *
     * @return list<array{document_id: int, lao_number: ?string, document_type: ?string, display_name: ?string, status_label: string, submitted_at: string}>
     */
    public function authorizedDocumentChoicesByStatus(User $user, string $status): array
    {
        $allowedStatuses = ['pending', 'in_progress', 'outgoing', 'completed', 'returned', 'rejected', 'archived'];

        if (! in_array($status, $allowedStatuses, true)) {
            return [];
        }

        return $this->submittedDocuments($user)
            ->where('status', $status)
            ->orderByDesc('created_at')
            ->orderByDesc('document_id')
            ->limit(self::CHATBOT_DOCUMENT_CHOICE_LIMIT)
            ->get($this->documentChoiceColumns())
            ->map(fn (Document $document): array => [
                'document_id' => (int) $document->document_id,
                'lao_number' => filled($document->lao_number) ? (string) $document->lao_number : null,
                'document_type' => filled($document->document_type) ? (string) $document->document_type : null,
                'display_name' => $this->documentDisplayName($document),
                'status_label' => $this->statusLabel((string) $document->status),
                'submitted_at' => $document->created_at?->format('F j, Y') ?? 'date unavailable',
            ])
            ->all();
    }

    /**
     * Find only this client's authorized submissions made on the supplied
     * calendar date. The date is validated before it reaches the query and
     * created_at is the existing submission timestamp in the documents table.
     *
     * @return list<array{document_id: int, lao_number: ?string, document_type: ?string, display_name: ?string, status_label: string, submitted_at: string}>
     */
    public function authorizedDocumentChoicesBySubmittedDate(User $user, string $date): array
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            return [];
        }

        return $this->submittedDocuments($user)
            ->whereDate('created_at', $date)
            ->orderByDesc('created_at')
            ->orderByDesc('document_id')
            ->limit(self::CHATBOT_DOCUMENT_CHOICE_LIMIT)
            ->get($this->documentChoiceColumns())
            ->map(fn (Document $document): array => [
                'document_id' => (int) $document->document_id,
                'lao_number' => filled($document->lao_number) ? (string) $document->lao_number : null,
                'document_type' => filled($document->document_type) ? (string) $document->document_type : null,
                'display_name' => $this->documentDisplayName($document),
                'status_label' => $this->statusLabel((string) $document->status),
                'submitted_at' => $document->created_at?->format('F j, Y') ?? 'date unavailable',
            ])
            ->all();
    }

    /** @return list<string> */
    private function availableTopicSearchFields(): array
    {
        return array_values(array_filter(
            ['document_name', 'document_type', 'description', 'particulars'],
            static fn (string $field): bool => Schema::hasColumn('documents', $field),
        ));
    }

    /** @return list<string> */
    private function documentChoiceColumns(): array
    {
        $columns = ['document_id', 'lao_number', 'status', 'created_at'];

        foreach (['document_type', 'document_name'] as $field) {
            if (Schema::hasColumn('documents', $field)) {
                $columns[] = $field;
            }
        }

        return $columns;
    }

    /** @return list<string> */
    private function documentStatusColumns(): array
    {
        $columns = ['document_id', 'status', 'lao_number'];

        foreach (['document_name', 'document_type'] as $field) {
            if (Schema::hasColumn('documents', $field)) {
                $columns[] = $field;
            }
        }

        return $columns;
    }

    /** @return list<string> */
    private function documentSearchTerms(string $value): array
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        $parts = preg_split('/[^\p{L}\p{N}]+/u', $value) ?: [];
        $aliases = [
            'org' => 'organization',
            'orgs' => 'organizations',
            'req' => 'request',
            'reqs' => 'requests',
            'docs' => 'document',
        ];

        return array_values(array_unique(array_filter(
            array_map(
                static fn (string $part): string => $aliases[trim($part)] ?? trim($part),
                $parts,
            ),
            static fn (string $part): bool => mb_strlen($part, 'UTF-8') >= 2,
        )));
    }

    /**
     * Return the selected client's current approved details. Every call is
     * owner-scoped; the session reference is not treated as authorization.
     *
     * @return array{reply: string, document_id: ?int, status: ?string}
     */
    public function detailsByDocumentIdResult(
        User $user,
        int $documentId,
        string $topic = 'summary',
        string $language = 'english',
    ): array
    {
        $document = Document::query()
            ->where('user_id', $user->getKey())
            ->where('document_id', $documentId)
            ->first([
                'document_id',
                'status',
                'document_name',
                'document_type',
                'action_type',
                'sent_to',
                'sent_date',
                'lao_number',
                'created_at',
                'updated_at',
            ]);

        if (! $document) {
            return $this->documentResult(self::NO_AUTHORIZED_MATCH);
        }

        $label = $this->documentLabel(
            $this->documentDisplayName($document),
            filled($document->lao_number) ? (string) $document->lao_number : null,
        );
        $status = (string) $document->status;

        if ($topic === 'document_type') {
            $reply = filled($document->document_type)
                ? ($language === 'filipino' ? 'Uri ng document: ' : 'Document type: ')
                    . (string) $document->document_type . '.'
                : ($language === 'filipino'
                    ? 'Hindi nakatala ang uri ng document.'
                    : 'The document type is not recorded.');
        } elseif ($topic === 'submission_date') {
            $name = $this->documentDisplayName($document) ?: 'this document';
            $reply = $document->created_at?->format('F j, Y') !== null
                ? (in_array($language, ['filipino', 'taglish'], true)
                    ? 'Isinumite mo ang ' . $name . ' noong ' . $document->created_at->format('F j, Y') . '.'
                    : 'You submitted ' . $name . ' on ' . $document->created_at->format('F j, Y') . '.')
                : (in_array($language, ['filipino', 'taglish'], true)
                    ? 'Walang nakatalang submission date para sa document na ito.'
                    : 'The submission date is not recorded.');
        } elseif ($topic === 'lao_number') {
            $reply = filled($document->lao_number)
                ? 'LAO number: ' . $document->lao_number . '.'
                : (in_array($language, ['filipino', 'taglish'], true)
                    ? 'Wala pang assigned LAO number ang document na ito.'
                    : 'This document does not have an assigned LAO number yet.');
        } elseif ($topic === 'updates') {
            $activity = $this->latestVerifiedActivity(
                $document,
                in_array($language, ['filipino', 'taglish'], true),
            );
            $updated = $document->updated_at?->format('F j, Y g:i A') ?? 'date unavailable';
            $statusLabel = $this->statusLabel($status);
            $reply = in_array($language, ['filipino', 'taglish'], true)
                ? "{$this->filipinoDocumentLabel($this->documentDisplayName($document), filled($document->lao_number) ? (string) $document->lao_number : null)} ay {$statusLabel}.\nPinakabagong verified activity: {$activity}\nNa-update: {$updated}."
                : "{$label} is {$statusLabel}.\nLatest verified activity: {$activity}\nUpdated: {$updated}.";
        } elseif ($topic === 'action_type') {
            $statusLabel = $this->statusLabel($status);
            $reply = $status !== 'in_progress'
                ? "{$label} is currently {$statusLabel}. An assigned action type is available only for an In Progress document."
                : (filled($document->action_type)
                ? "{$label} is currently {$statusLabel}. Assigned action type: {$document->action_type}."
                : "{$label} is currently {$statusLabel}. No assigned action type has been recorded.");
        } else {
            $reply = $this->statusResponse(
                $user,
                (int) $document->document_id,
                $status,
                filled($document->lao_number) ? (string) $document->lao_number : null,
                $this->documentDisplayName($document),
                null,
                $language,
            );
        }

        return $this->documentResult($reply, (int) $document->document_id, $status);
    }

    /**
     * Return minimal selectors for the authenticated client's own records.
     * These values are used only to help the client disambiguate a private lookup.
     *
     * @return list<array{document_id: int, lao_number: ?string, document_type: ?string, display_name: ?string, status_label: string, submitted_at: string}>
     */
    public function authorizedDocumentChoices(User $user, int $limit = self::CHATBOT_DOCUMENT_CHOICE_LIMIT): array
    {
        return $this->submittedDocuments($user)
            ->orderByDesc('created_at')
            ->orderByDesc('document_id')
            ->limit(max(1, min($limit, self::CHATBOT_DOCUMENT_CHOICE_LIMIT)))
            ->get([
                'document_id',
                'lao_number',
                'document_type',
                'document_name',
                'status',
                'created_at',
            ])
            ->map(fn (Document $document): array => [
                'document_id' => (int) $document->document_id,
                'lao_number' => filled($document->lao_number) ? (string) $document->lao_number : null,
                'document_type' => filled($document->document_type) ? (string) $document->document_type : null,
                'display_name' => $this->documentDisplayName($document),
                'status_label' => $this->statusLabel((string) $document->status),
                'submitted_at' => $document->created_at?->format('F j, Y') ?? 'date unavailable',
            ])
            ->all();
    }

    /**
     * Compare only status fields for exact LAO numbers owned by this client.
     * If any identifier is not authorized, return the same generic response.
     *
     * @param  list<string>  $laoNumbers
     */
    public function compareStatusesByLaoNumbers(User $user, array $laoNumbers): string
    {
        $laoNumbers = array_values(array_unique(array_map(
            static fn (string $number): string => strtoupper(trim($number)),
            $laoNumbers,
        )));

        if (count($laoNumbers) < 2 || count($laoNumbers) > 5) {
            return 'Please compare between two and five documents at a time.';
        }

        foreach ($laoNumbers as $laoNumber) {
            if (preg_match('/^LAO-\d{2}-\d{3,}$/D', $laoNumber) !== 1) {
                return 'Please enter valid LAO numbers in the format LAO-26-009.';
            }
        }

        $documents = Document::query()
            ->where('user_id', $user->getKey())
            ->whereIn('lao_number', $laoNumbers)
            ->get(['lao_number', 'status']);

        if ($documents->count() !== count($laoNumbers)) {
            return self::NO_AUTHORIZED_MATCH;
        }

        $lines = ['Your documents have these statuses:'];

        foreach ($laoNumbers as $laoNumber) {
            $document = $documents->firstWhere('lao_number', $laoNumber);
            $lines[] = $laoNumber . ' — ' . $this->statusLabel((string) $document->status);
        }

        return implode("\n", $lines);
    }

    /**
     * Compare the most recent owned documents using only identifier, status,
     * and submission date.
     */
    public function compareLatestStatuses(User $user, int $limit = 5): string
    {
        $documents = $this->submittedDocuments($user)
            ->orderByDesc('created_at')
            ->orderByDesc('document_id')
            ->limit(max(2, min($limit, 5)))
            ->get(['lao_number', 'status', 'created_at']);

        if ($documents->isEmpty()) {
            return 'You have no submitted documents yet.';
        }

        $lines = ['Your most recent document statuses are:'];

        foreach ($documents as $index => $document) {
            $identifier = filled($document->lao_number)
                ? (string) $document->lao_number
                : 'No LAO number assigned';
            $submittedAt = $document->created_at?->format('F j, Y') ?? 'date unavailable';
            $lines[] = ($index + 1) . '. ' . $identifier . ' — '
                . $this->statusLabel((string) $document->status)
                . ' (submitted ' . $submittedAt . ')';
        }

        return implode("\n", $lines);
    }

    private function statusResponse(
        User $user,
        int $documentId,
        string $status,
        ?string $laoNumber,
        ?string $displayName = null,
        ?string $topic = null,
        string $language = 'english',
        ?string $labelOverride = null,
    ): string {
        $filipinoLike = in_array($language, ['filipino', 'taglish'], true);
        $label = $labelOverride
            ?? ($filipinoLike
                ? $this->filipinoDocumentLabel($displayName, $laoNumber)
                : $this->documentLabel($displayName, $laoNumber));

        if (filled($topic)) {
            $topicLabel = mb_convert_case(trim($topic), MB_CASE_TITLE, 'UTF-8');
            $documentLabel = $displayName ?: ($laoNumber ?: 'selected document');
            $label = $filipinoLike
                ? 'Ang document tungkol sa "' . $topicLabel . '" ("' . $documentLabel . '")'
                : 'Your document about "' . $topicLabel . '" ("' . $documentLabel . '")';
        }

        return match ($status) {
            'pending' => $filipinoLike ? "{$label} ay Pending." : "{$label} is Pending.",
            'in_progress' => $this->inProgressStatus($user, $documentId, $laoNumber, $label, $filipinoLike),
            'outgoing' => $this->outgoingStatus($user, $documentId, $laoNumber, $label, $filipinoLike),
            'completed' => $filipinoLike ? "{$label} ay Completed." : "{$label} is Completed.",
            'rejected' => $filipinoLike
                ? "{$label} ay Rejected. Tingnan ang authorized rejection reason sa Documents page."
                : "{$label} is Rejected. Please view its authorized rejection reason in the Documents page.",
            'archived' => $filipinoLike
                ? "{$label} ay Archived — Retained for Records."
                : "{$label} is Archived — Retained for Records.",
            'returned' => $filipinoLike ? "{$label} ay Returned." : "{$label} is currently Returned.",
            default => $filipinoLike
                ? 'Hindi available ang kasalukuyang status ng document.'
                : 'The current document status is unavailable.',
        };
    }

    private function inProgressStatus(
        User $user,
        int $documentId,
        ?string $laoNumber,
        string $label,
        bool $filipinoLike = false,
    ): string {
        $query = Document::query()
            ->where('user_id', $user->getKey())
            ->where('document_id', $documentId)
            ->where('status', 'in_progress');

        if ($laoNumber !== null) {
            $query->where('lao_number', $laoNumber);
        }

        $document = $query->first(['action_type']);

        if (! $document) {
            return 'The current document status is unavailable.';
        }

        return filled($document->action_type)
            ? ($filipinoLike
                ? "{$label} ay In Progress. Assigned action type: {$document->action_type}."
                : "{$label} is In Progress. Assigned action type: {$document->action_type}.")
            : ($filipinoLike
                ? "{$label} ay In Progress. Walang assigned action type na nakatala."
                : "{$label} is In Progress. No assigned action type has been recorded.");
    }

    private function outgoingStatus(
        User $user,
        int $documentId,
        ?string $laoNumber,
        string $label,
        bool $filipinoLike = false,
    ): string {
        // Destination and sent date are disclosed only for Outgoing documents.
        $query = Document::query()
            ->where('user_id', $user->getKey())
            ->where('document_id', $documentId)
            ->where('status', 'outgoing');

        if ($laoNumber !== null) {
            $query->where('lao_number', $laoNumber);
        }

        $document = $query->first(['sent_to', 'sent_date']);

        if (! $document) {
            return 'The current document status is unavailable.';
        }

        $destination = filled($document->sent_to)
            ? $document->sent_to
            : 'No destination has been recorded';
        $sentDate = $document->sent_date?->format('F j, Y') ?? 'No sent date has been recorded';

        return $filipinoLike
            ? "{$label} ay Outgoing. Recorded destination: {$destination}. Date sent: {$sentDate}."
            : "{$label} is Outgoing. Recorded destination: {$destination}. Date sent: {$sentDate}.";
    }

    private function latestVerifiedActivity(Document $document, bool $filipinoLike): string
    {
        if (! Schema::hasTable('activity_logs')) {
            return $filipinoLike
                ? 'Walang hiwalay na activity na nakatala.'
                : 'No separate activity recorded.';
        }

        $log = $document->activityLogs()
            ->orderByDesc('created_at')
            ->orderByDesc('log_id')
            ->first(['action_type', 'new_value']);

        if (! $log) {
            return $filipinoLike
                ? 'Walang hiwalay na activity na nakatala.'
                : 'No separate activity recorded.';
        }

        $action = strtolower(trim((string) $log->action_type));

        if (str_contains($action, 'accept')) {
            return $filipinoLike ? 'Tinanggap para sa processing.' : 'Accepted for processing.';
        }

        if (str_contains($action, 'reject')) {
            return $filipinoLike ? 'Minarkahang Rejected.' : 'Marked Rejected.';
        }

        if ($action === 'document moved to outgoing') {
            return $filipinoLike ? 'Inilipat sa Outgoing.' : 'Moved to Outgoing.';
        }

        if ($action === 'document completed') {
            return $filipinoLike ? 'Nakumpleto.' : 'Completed.';
        }

        if ($action === 'document archived') {
            return $filipinoLike ? 'In-archive.' : 'Archived.';
        }

        if ($action === 'document returned') {
            return $filipinoLike ? 'Ibinalik para sa karagdagang action.' : 'Returned for further action.';
        }

        if (str_contains($action, 'updated')) {
            $new = json_decode((string) ($log->new_value ?? ''), true);

            if (is_array($new) && filled($new['status'] ?? null)) {
                $status = $this->statusLabel((string) $new['status']);

                return $filipinoLike
                    ? 'Na-update ang status sa ' . $status . '.'
                    : 'Status updated to ' . $status . '.';
            }

            if (is_array($new) && filled($new['action_type'] ?? null)) {
                return $filipinoLike ? 'Na-update ang action type.' : 'Action type updated.';
            }

            return $filipinoLike ? 'Na-update ang detalye ng document.' : 'Document details updated.';
        }

        return $filipinoLike ? 'May recorded activity.' : 'Recorded activity available.';
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'in_progress' => 'In Progress',
            'pending' => 'Pending',
            'outgoing' => 'Outgoing',
            'completed' => 'Completed',
            'returned' => 'Returned',
            'rejected' => 'Rejected',
            'archived' => 'Archived',
            default => 'Unavailable',
        };
    }

    private function filipinoDocumentLabel(?string $displayName, ?string $laoNumber): string
    {
        if (filled($displayName) && filled($laoNumber)) {
            return "Ang document {$displayName} ({$laoNumber})";
        }

        if (filled($displayName)) {
            return "Ang document {$displayName}";
        }

        return filled($laoNumber) ? "Ang document {$laoNumber}" : 'Ang napiling document';
    }

    private function documentDisplayName(Document $document): ?string
    {
        // document_name is the actual title field populated from the uploaded
        // document. document_type is only a display label and never an ID.
        $name = $document->document_name ?: $document->document_type;

        return filled($name) ? trim((string) $name) : null;
    }

    private function verifiedCompletionDate(Document $document): ?\Carbon\CarbonInterface
    {
        if (! Schema::hasTable('activity_logs')) {
            return null;
        }

        $logs = $document->activityLogs()
            ->orderByDesc('created_at')
            ->get(['action_type', 'old_value', 'new_value', 'created_at']);

        foreach ($logs as $log) {
            if ($log->action_type === 'Document completed') {
                return $log->created_at;
            }

            $old = json_decode((string) ($log->old_value ?? ''), true);
            $new = json_decode((string) ($log->new_value ?? ''), true);

            if ($log->action_type === 'Document updated'
                && ($new['status'] ?? null) === 'completed'
                && ($old['status'] ?? null) !== 'completed') {
                return $log->created_at;
            }
        }

        return null;
    }

    /**
     * Build the owner-scoped query used for submitted-document operations.
     * A fulfilled document request can create a row in documents and link it
     * through document_requests; that row must remain available to the
     * request lookup, but must not be mistaken for a client submission.
     */
    private function submittedDocuments(User $user)
    {
        $query = Document::query()
            ->where('user_id', $user->getKey());

        if (Schema::hasTable('document_requests')) {
            $query->whereDoesntHave('documentRequests');
        }

        return $query;
    }

    private function documentLabel(?string $displayName, ?string $laoNumber): string
    {
        if (filled($displayName) && filled($laoNumber)) {
            return "Document {$displayName} ({$laoNumber})";
        }

        if (filled($displayName)) {
            return "Document {$displayName}";
        }

        return filled($laoNumber) ? "Document {$laoNumber}" : 'Your selected document';
    }

    private function withDocumentLabel(?string $label, string $message): string
    {
        return filled($label)
            ? 'Document ' . $label . ': ' . ltrim($message)
            : $message;
    }

    /**
     * @return array{reply: string, document_id: ?int, status: ?string}
     */
    private function documentResult(string $reply, ?int $documentId = null, ?string $status = null): array
    {
        return [
            'reply' => $reply,
            'document_id' => $documentId,
            'status' => $status,
        ];
    }

    private function acceptanceGuidance(string $status, string $language): string
    {
        if ($status === 'pending') {
            if ($language === 'filipino') {
                return 'Maaaring ma-accept ang Pending submission pagkatapos ng initial review ng Legal Affairs Office at kapag pasado ito sa mga requirement. Tingnan ang Messages kung may kailangang itama.';
            }

            return 'A Pending submission may be accepted after the Legal Affairs Office completes its initial review and confirms that the submission meets the requirements. If corrections are needed, check Messages for instructions.';
        }

        if ($language === 'filipino') {
            return 'Nakadepende ang acceptance sa review ng Legal Affairs Office. Kasalukuyang ' . $this->statusLabel($status) . ' ang document mo. Tingnan ang Documents para sa status at Messages para sa instructions.';
        }

        return 'Acceptance depends on the Legal Affairs Office’s review. Your document is currently '
            . $this->statusLabel($status)
            . '. Check Documents for its status and Messages for any instructions.';
    }

    private function acceptanceCheck(string $status, string $language): string
    {
        if ($status === 'pending') {
            return $language === 'filipino'
                ? 'Oo. Maaari itong ma-accept kapag natapos ng Legal Affairs Office ang initial review at pasado ang submission sa requirements.'
                : 'Yes. It may be accepted after the Legal Affairs Office completes its initial review and confirms that the submission meets the requirements.';
        }

        if ($status === 'rejected') {
            return $language === 'filipino'
                ? 'Hindi. Rejected ang kasalukuyang submission. Tingnan sa Documents ang recorded reason; gumamit ng Submit Document para sa corrected na bagong submission.'
                : 'No. The current submission is Rejected. Review its recorded reason in Documents, then use Submit Document for a corrected new submission.';
        }

        if ($status === 'in_progress') {
            return $language === 'filipino'
                ? 'Oo. Ang In Progress status ay nangangahulugang na-accept ito para sa karagdagang processing.'
                : 'Yes. In Progress means the submission was accepted for further processing.';
        }

        return $language === 'filipino'
            ? 'Kasalukuyang ' . $this->statusLabel($status) . ' ang document. Tingnan ang Documents para sa kasalukuyang record nito.'
            : 'The document is currently ' . $this->statusLabel($status) . '. Check Documents for its current record.';
    }

    private function acceptanceState(string $status, string $language): string
    {
        if ($status === 'pending') {
            return $language === 'filipino'
                ? 'Hindi pa. Pending pa ang submission at naghihintay ito ng initial review.'
                : 'No. It is still Pending and awaiting its initial review.';
        }

        if ($status === 'in_progress') {
            return $language === 'filipino'
                ? 'Oo. Ang In Progress status ay nangangahulugang na-accept ito para sa karagdagang processing.'
                : 'Yes. In Progress means the submission was accepted for further processing.';
        }

        if ($status === 'rejected') {
            return $language === 'filipino'
                ? 'Hindi. Rejected ang submission. Tingnan sa Documents ang recorded reason.'
                : 'No. The submission is Rejected. Review its recorded reason in Documents.';
        }

        return $language === 'filipino'
            ? 'Kasalukuyang ' . $this->statusLabel($status) . ' ang document. Tingnan ang Documents para sa status nito.'
            : 'The document is currently ' . $this->statusLabel($status) . '. Check Documents for its status.';
    }

    private function resubmissionGuidance(string $status, string $language): string
    {
        if ($status === 'rejected') {
            if ($language === 'filipino') {
                return 'Rejected ang submission na ito. Tingnan sa Documents ang recorded reason. Para magpasa ng corrected file bilang bagong submission, gamitin ang Submit Document maliban kung may partikular na revision request ang Legal Affairs Office. Gamitin lang ang revision upload kapag may active request sa Messages.';
            }

            return 'This submission is Rejected. Review the recorded reason in Documents, then use Submit Document for a corrected new submission unless the Legal Affairs Office gave you a specific revision request. A revision upload is only for an authorized open request in Messages.';
        }

        if ($status === 'in_progress') {
            if ($language === 'filipino') {
                return 'Kung humingi ng revision ang Legal Affairs Office, buksan ang Messages ng document na ito at gamitin ang authorized revision link. Magdadagdag ito ng bagong version sa kasalukuyang document. Kung bagong document ito, gamitin ang Submit Document.';
            }

            return 'If the Legal Affairs Office requested a revision, open that document’s Messages conversation and use its authorized revision link. That uploads a new version to the existing document. Otherwise, use Submit Document for a separate new submission.';
        }

        if ($language === 'filipino') {
            return 'Hiwalay ang bagong document submission sa revision. Gamitin ang Submit Document para gumawa ng bagong record. Gamitin lang ang revision upload kapag may authorized request mula sa Legal Affairs Office sa Messages.';
        }

        return 'A new document submission is separate from a revision. Use Submit Document for a new record. Use the revision upload only when the Legal Affairs Office has made an authorized request in Messages.';
    }
}
