<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;

class ClientDocumentLookupService
{
    private const NO_AUTHORIZED_MATCH = 'I couldn’t find an authorized document with that LAO number.';

    public function latestStatus(User $user): string
    {
        return $this->latestStatusResult($user)['reply'];
    }

    /**
     * Resolve the latest document status and a session-safe reference for local
     * follow-ups. The reference is never included in an AI prompt.
     *
     * @return array{reply: string, document_id: ?int, status: ?string}
     */
    public function latestStatusResult(User $user): array
    {
        $document = Document::query()
            ->where('user_id', $user->getKey())
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
            return $this->documentResult(
                $this->statusResponse(
                    $user,
                    (int) $document->document_id,
                    (string) $document->status,
                    filled($document->lao_number) ? (string) $document->lao_number : null,
                    $label,
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
    public function statusByDocumentIdResult(User $user, int $documentId): array
    {
        // Selection indexes are session-scoped hints, not authorization. Re-check
        // the authenticated owner before reading any document status.
        $document = Document::query()
            ->where('user_id', $user->getKey())
            ->where('document_id', $documentId)
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
                filled($document->lao_number) ? (string) $document->lao_number : null,
                $this->documentDisplayName($document),
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
            ->first(['document_id', 'lao_number', 'status', 'rejection_reason']);

        if (! $document) {
            return $this->documentResult(self::NO_AUTHORIZED_MATCH);
        }

        $label = filled($document->lao_number)
            ? 'Document ' . $document->lao_number
            : 'Your selected document';
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
            : 'No recorded rejection reason is available.';

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
        $counts = Document::query()
            ->where('user_id', $user->getKey())
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
     * Resolve a title/name only among the authenticated client's documents.
     * The returned choices are selectors, never an authorization decision.
     * The owner is checked again by statusByDocumentIdResult() after selection.
     *
     * @return list<array{document_id: int, lao_number: ?string, document_type: ?string, display_name: ?string, submitted_at: string}>
     */
    public function authorizedDocumentChoicesByName(User $user, string $name): array
    {
        $name = trim($name);

        if ($name === '') {
            return [];
        }

        return Document::query()
            ->where('user_id', $user->getKey())
            ->where(function ($query) use ($name): void {
                $query->where('document_name', $name);
            })
            ->orderByDesc('created_at')
            ->orderByDesc('document_id')
            ->get([
                'document_id',
                'lao_number',
                'document_type',
                'document_name',
                'created_at',
            ])
            ->map(fn (Document $document): array => [
                'document_id' => (int) $document->document_id,
                'lao_number' => filled($document->lao_number) ? (string) $document->lao_number : null,
                'document_type' => filled($document->document_type) ? (string) $document->document_type : null,
                'display_name' => $this->documentDisplayName($document),
                'submitted_at' => $document->created_at?->format('F j, Y') ?? 'date unavailable',
            ])
            ->all();
    }

    /**
     * Return the selected client's current approved details. Every call is
     * owner-scoped; the session reference is not treated as authorization.
     *
     * @return array{reply: string, document_id: ?int, status: ?string}
     */
    public function detailsByDocumentIdResult(User $user, int $documentId, string $topic = 'summary'): array
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
            ]);

        if (! $document) {
            return $this->documentResult(self::NO_AUTHORIZED_MATCH);
        }

        $label = $this->documentLabel(
            $this->documentDisplayName($document),
            filled($document->lao_number) ? (string) $document->lao_number : null,
        );
        $status = (string) $document->status;

        if ($topic === 'action_type') {
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
                $label,
            );
        }

        return $this->documentResult($reply, (int) $document->document_id, $status);
    }

    /**
     * Return minimal selectors for the authenticated client's own records.
     * These values are used only to help the client disambiguate a private lookup.
     *
     * @return list<array{document_id: int, lao_number: ?string, document_type: ?string, display_name: ?string, submitted_at: string}>
     */
    public function authorizedDocumentChoices(User $user, int $limit = 10): array
    {
        return Document::query()
            ->where('user_id', $user->getKey())
            ->orderByDesc('created_at')
            ->orderByDesc('document_id')
            ->limit(max(1, min($limit, 10)))
            ->get([
                'document_id',
                'lao_number',
                'document_type',
                'document_name',
                'created_at',
            ])
            ->map(fn (Document $document): array => [
                'document_id' => (int) $document->document_id,
                'lao_number' => filled($document->lao_number) ? (string) $document->lao_number : null,
                'document_type' => filled($document->document_type) ? (string) $document->document_type : null,
                'display_name' => $this->documentDisplayName($document),
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
        $documents = Document::query()
            ->where('user_id', $user->getKey())
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
    ): string {
        $label = $this->documentLabel($displayName, $laoNumber);

        return match ($status) {
            'pending' => "{$label} is Pending.",
            'in_progress' => $this->inProgressStatus($user, $documentId, $laoNumber, $label),
            'outgoing' => $this->outgoingStatus($user, $documentId, $laoNumber, $label),
            'completed' => "{$label} is Completed.",
            'rejected' => "{$label} is Rejected. Please view its authorized rejection reason in the Documents page.",
            'archived' => "{$label} is Archived — Retained for Records.",
            'returned' => "{$label} is currently Returned.",
            default => 'The current document status is unavailable.',
        };
    }

    private function inProgressStatus(
        User $user,
        int $documentId,
        ?string $laoNumber,
        string $label,
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
            ? "{$label} is In Progress. Assigned action type: {$document->action_type}."
            : "{$label} is In Progress. No assigned action type has been recorded.";
    }

    private function outgoingStatus(
        User $user,
        int $documentId,
        ?string $laoNumber,
        string $label,
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

        return "{$label} is Outgoing. Recorded destination: {$destination}. Date sent: {$sentDate}.";
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

    private function documentDisplayName(Document $document): ?string
    {
        // document_name is the actual title field populated from the uploaded
        // document. document_type is only a display label and never an ID.
        $name = $document->document_name ?: $document->document_type;

        return filled($name) ? trim((string) $name) : null;
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
