<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Classifies chatbot requests locally before any message can reach an AI provider.
 * The rules are intent-based rather than a list of exact question sentences.
 */
class ChatbotIntentRouter
{
    /**
     * Detect what the client is asking independently of any document reference.
     *
     * @return 'workflow_explanation'|'yes_no_comparison'|'status_lookup'|'document_count'|'request_status'|'request_count'|'general_knowledge'
     */
    public function detectQuestionIntent(string $message): string
    {
        $normalized = $this->normalize($message);

        if ($this->isDocumentCountInquiry($normalized)) {
            return 'document_count';
        }

        if ($this->isWorkflowExplanationQuestion($normalized)) {
            return 'workflow_explanation';
        }

        if ($this->isYesNoStatusComparison($normalized)) {
            return 'yes_no_comparison';
        }

        if ($this->isDocumentCountInquiry($normalized)) {
            return 'document_count';
        }

        if ($this->isPrivateDocumentRequestInquiry($normalized)) {
            return $this->isRequestCountInquiry($normalized) ? 'request_count' : 'request_status';
        }

        if ($this->isLatestDocumentInquiry($normalized)
            || ($this->isPrivateDocumentInquiry($normalized) && $this->hasDocumentStatusTerm($normalized))) {
            return 'status_lookup';
        }

        return 'general_knowledge';
    }

    /**
     * Resolve only the document reference, without deciding what the client is asking.
     *
     * @return array{type: 'lao_numbers', numbers: list<string>}|array{type: 'selection', index: int}|array{type: 'invalid'}|array{type: 'latest'}|array{type: 'contextual'}|array{type: 'ambiguous'}|array{type: 'none'}
     */
    public function resolveDocumentReference(
        string $message,
        bool $hasPrivateDocumentContext = false,
    ): array {
        $normalized = $this->normalize($message);
        $laoNumbers = $this->extractLaoNumbers($message);

        if ($laoNumbers !== []) {
            return ['type' => 'lao_numbers', 'numbers' => $laoNumbers];
        }

        if ($this->isLatestDocumentInquiry($normalized)) {
            return ['type' => 'latest'];
        }

        if (($index = $this->documentSelectionIndex($normalized)) !== null) {
            return ['type' => 'selection', 'index' => $index];
        }

        if (preg_match(
            '/\b(?:doc|trk|tracking|document|request)\s*[-#:]?\s*\d[\p{L}\p{N}-]*\b/i',
            $message,
        ) === 1 || $this->hasIncompleteLaoReference($normalized)) {
            return ['type' => 'invalid'];
        }

        if ($hasPrivateDocumentContext && $this->hasContextualDocumentReference($normalized)) {
            return ['type' => 'contextual'];
        }

        if ($this->isPrivateDocumentInquiry($normalized)) {
            return ['type' => 'ambiguous'];
        }

        return ['type' => 'none'];
    }

    public function containsProtectedIdentifier(string $message): bool
    {
        return $this->extractLaoNumbers($message) !== []
            || preg_match(
                '/\b(?:doc|trk|tracking|document|request)\s*[-#:]?\s*\d[\p{L}\p{N}-]*\b/i',
                $message,
            ) === 1
            || preg_match('/\b[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}\b/i', $message) === 1
            || preg_match('/(?<!\d)(?:\+?\d[\s().-]?){9,}\d(?!\d)/', $message) === 1;
    }

    public function confirmationValue(string $message): ?bool
    {
        $normalized = $this->normalize($message);

        if (preg_match('/^(?:yes|y|oo|opo|sige|go ahead|tuloy|ituloy)\b/', $normalized) === 1) {
            return true;
        }

        if (preg_match('/^(?:no|n|hindi|ayaw|cancel|kanselahin|wag|huwag)\b/', $normalized) === 1) {
            return false;
        }

        return null;
    }

    public function isClearTopicChange(string $message): bool
    {
        $normalized = $this->normalize($message);

        return str_contains($message, '?')
            || preg_match(
                '/^(?:what|how|why|where|when|can|could|do|does|is|are|tell me|thanks|thank you|salamat|okay|ok|noted|ano|paano|pano|bakit|saan|kailan|mayroon ba|meron ba)\b/',
                $normalized,
            ) === 1;
    }

    public function documentSelectionIndex(string $message): ?int
    {
        return $this->selectionIndex($this->normalize($message));
    }

    public function requestId(string $message): ?int
    {
        if (preg_match('/\b(?:request|req|hiling|application)\s*(?:number|no\.?|#|id)?\s*(\d{1,12})\b/i', $message, $matches) !== 1) {
            return null;
        }

        $id = filter_var($matches[1], FILTER_VALIDATE_INT);

        return $id !== false && $id > 0 ? (int) $id : null;
    }

    public function isGreeting(string $message): bool
    {
        return preg_match('/^(?:hi|hello|hey|good morning|good afternoon|good evening|kumusta|kamusta|musta|magandang umaga|magandang hapon|magandang gabi)[!. ]*$/i', trim($message)) === 1;
    }

    /**
     * @return array{intent: string, lao_numbers?: list<string>, selection_index?: int}
     */
    public function classify(
        string $message,
        bool $hasPrivateContext = false,
        bool $hasDocumentChoices = false,
        bool $hasPrivateDocumentContext = false,
        bool $hasPrivateMessageContext = false,
        bool $hasRequestChoices = false,
        bool $hasPrivateRequestContext = false,
    ): array {
        $normalized = $this->normalize($message);
        $questionIntent = $this->detectQuestionIntent($normalized);
        $documentReference = $this->resolveDocumentReference(
            $message,
            $hasPrivateDocumentContext,
        );

        if ($this->isMessageContentRequest($normalized, $hasPrivateMessageContext)) {
            return ['intent' => 'message_content', 'language' => $this->responseLanguage($normalized)];
        }

        if ($this->isGreeting($message)) {
            return ['intent' => 'greeting', 'language' => $this->responseLanguage($normalized)];
        }

        if ($this->isAcknowledgment($normalized)) {
            return ['intent' => 'acknowledgment', 'language' => $this->responseLanguage($normalized)];
        }

        if ($this->isPositiveConversation($normalized)) {
            return [
                'intent' => 'conversational_reply',
                'language' => $this->responseLanguage($normalized),
            ];
        }

        if ($this->isEmailDeliveryInquiry($normalized)) {
            return ['intent' => 'email_delivery', 'language' => $this->responseLanguage($normalized)];
        }

        if ($this->isMessageMetadataInquiry($normalized)) {
            return [
                'intent' => 'message_metadata',
                'kind' => $this->hasUnreadCue($normalized)
                    ? 'unread'
                    : ($this->isCountQuestion($normalized) ? 'count' : 'exists'),
                'language' => $this->responseLanguage($normalized),
            ];
        }

        // Short standalone terms are useful answers to a pending question,
        // not automatically unrelated or unclear messages.
        if ($this->isStandaloneRejectionDefinition($normalized)) {
            return [
                'intent' => 'rejection_definition',
                'language' => $this->responseLanguage($normalized),
            ];
        }

        if ($this->isStandaloneAcceptanceTerm($normalized)) {
            return [
                'intent' => 'acceptance_definition',
                'language' => $this->responseLanguage($normalized),
            ];
        }

        if ($this->isStandaloneCopyTypeTerm($normalized)) {
            return $hasPrivateRequestContext
                ? [
                    'intent' => 'request_context_details',
                    'topic' => 'copy_type',
                    'language' => $this->responseLanguage($normalized),
                ]
                : [
                    'intent' => 'copy_type_clarification',
                    'language' => $this->responseLanguage($normalized),
                ];
        }

        if ($this->isStandaloneRequestTerm($normalized)) {
            return $hasPrivateRequestContext
                ? [
                    'intent' => 'request_context_details',
                    'topic' => 'summary',
                    'language' => $this->responseLanguage($normalized),
                ]
                : [
                    'intent' => 'request_scope_clarification',
                    'language' => $this->responseLanguage($normalized),
                ];
        }

        if ($this->isUnsupportedRequest($message, $normalized)) {
            return ['intent' => 'unsupported'];
        }

        if ($hasRequestChoices
            && ($selectionIndex = $this->documentSelectionIndex($normalized)) !== null) {
            return [
                'intent' => 'request_selection',
                'selection_index' => $selectionIndex,
                'language' => $this->responseLanguage($normalized),
            ];
        }

        if (($requestId = $this->requestId($message)) !== null) {
            return [
                'intent' => 'request_status',
                'request_id' => $requestId,
                'topic' => $this->requestTopic($normalized),
                'language' => $this->responseLanguage($normalized),
            ];
        }

        if ($hasPrivateRequestContext
            && ($this->hasContextualRecordReference($normalized)
                || ($this->hasPersonalReference($normalized)
                    && $this->requestTopic($normalized) !== 'summary'))) {
            return [
                'intent' => 'request_context_details',
                'topic' => $this->requestTopic($normalized),
                'language' => $this->responseLanguage($normalized),
            ];
        }

        if (! $hasPrivateRequestContext
            && preg_match('/\b(?:request|requests|hiling|application)\b/', $normalized) === 1
            && $this->hasContextualRecordReference($normalized)) {
            return ['intent' => 'ambiguous_request'];
        }

        if ($this->isPrivateDocumentRequestInquiry($normalized)) {
            return [
                'intent' => $this->isRequestCountInquiry($normalized) ? 'request_count' : 'latest_request',
                'status' => $this->requestedRequestStatus($normalized),
                'language' => $this->responseLanguage($normalized),
            ];
        }

        if ($documentReference['type'] === 'lao_numbers') {
            $laoNumbers = $documentReference['numbers'];

            if ($this->isRejectionReasonQuestion($normalized)) {
                return [
                    'intent' => 'lao_rejection_reason',
                    'lao_numbers' => $laoNumbers,
                ];
            }

            if ($this->isComparison($normalized) && count($laoNumbers) > 1) {
                return [
                    'intent' => 'compare_lao_numbers',
                    'lao_numbers' => $laoNumbers,
                ];
            }

            return [
                'intent' => 'lao_lookup',
                'lao_numbers' => $laoNumbers,
            ];
        }

        if ($documentReference['type'] === 'selection') {
            if ($hasRequestChoices) {
                return [
                    'intent' => 'request_selection',
                    'selection_index' => $documentReference['index'],
                    'language' => $this->responseLanguage($normalized),
                ];
            }

            if (! $hasDocumentChoices) {
                return ['intent' => 'ambiguous_document'];
            }

            return [
                'intent' => 'document_selection',
                'selection_index' => $documentReference['index'],
            ];
        }

        if ($documentReference['type'] === 'invalid') {
            return ['intent' => 'invalid_lao'];
        }

        // Rejection-reason questions are status-filtered private lookups.
        // Resolve this intent before generic document-topic extraction so
        // words such as “was rejected” never become a search reference.
        if ($this->isRejectionReasonQuestion($normalized)) {
            $rejectionReference = $this->extractRejectionDocumentReference($message);

            if ($hasPrivateDocumentContext && $rejectionReference === null) {
                return [
                    'intent' => 'document_context_rejection_reason',
                    'language' => $this->responseLanguage($normalized),
                ];
            }

            return [
                'intent' => 'rejection_reason_lookup',
                'document_name' => $rejectionReference['value'] ?? null,
                'reference_type' => $rejectionReference['type'] ?? null,
                'reference_field' => $rejectionReference['field'] ?? null,
                'language' => $this->responseLanguage($normalized),
            ];
        }

        // Aggregate status counts must be resolved before workflow or
        // generic topic handling. For example, “how many documents are in
        // progress?” asks Laravel for a count, not for the definition of
        // In Progress.
        if ($this->isDocumentCountInquiry($normalized)) {
            return [
                'intent' => 'document_count',
                'status' => $this->requestedStatus($normalized),
                'yes_no' => $this->isYesNoCountQuestion($normalized),
                'language' => $this->responseLanguage($normalized),
            ];
        }

        $documentReferenceText = $this->extractDocumentReference($message);
        if ($documentReferenceText !== null
            && ($this->isNamedDocumentInquiry($normalized)
                || $this->isDocumentSearchPhrase($normalized)
                || ($documentReferenceText['type'] ?? null) === 'document_type')) {
            return [
                'intent' => 'document_name_lookup',
                'document_name' => $documentReferenceText['value'],
                'reference_type' => $documentReferenceText['type'],
                'reference_field' => $documentReferenceText['field'] ?? null,
                'document_action' => $this->documentReferenceAction($normalized),
                'language' => $this->responseLanguage($normalized),
            ];
        }

        if ($documentReference['type'] === 'none'
            && $this->isPotentialPrivateRecordInquiry($normalized)) {
            return ['intent' => 'ambiguous_document'];
        }

        if ($this->isRejectionGuidanceQuestion($normalized)) {
            return [
                'intent' => 'rejection_guidance',
                'language' => $this->responseLanguage($normalized),
            ];
        }

        if ($questionIntent === 'workflow_explanation') {
            return [
                'intent' => 'workflow_explanation',
                'language' => $this->responseLanguage($normalized),
            ];
        }

        if ($this->isGeneralResubmissionQuestion($normalized) || $this->isGeneralAcceptanceQuestion($normalized)) {
            return ['intent' => 'general_knowledge'];
        }

        if ($hasPrivateDocumentContext && $this->isAcceptancePossibilityQuestion($normalized)) {
            return [
                'intent' => 'document_context_guidance',
                'topic' => 'acceptance_check',
                'language' => $this->responseLanguage($normalized),
            ];
        }

        if ($hasPrivateDocumentContext && $this->isAcceptanceStateQuestion($normalized)) {
            return [
                'intent' => 'document_context_guidance',
                'topic' => 'acceptance_state',
                'language' => $this->responseLanguage($normalized),
            ];
        }

        if ($hasPrivateDocumentContext && $this->isAcceptanceGuidanceFollowUp($normalized)) {
            return [
                'intent' => 'document_context_guidance',
                'topic' => 'acceptance',
                'language' => $this->responseLanguage($normalized),
            ];
        }

        if ($hasPrivateDocumentContext
            && $this->isResubmissionQuestion($normalized)
            && $this->hasContextualDocumentReference($normalized)) {
            return [
                'intent' => 'document_context_guidance',
                'topic' => 'resubmission',
                'language' => $this->responseLanguage($normalized),
            ];
        }

        if ($hasPrivateDocumentContext
            && $questionIntent === 'status_lookup'
            && $documentReference['type'] === 'contextual'
            && $this->isDocumentStatusFollowUp($normalized)) {
            return ['intent' => 'document_context_status'];
        }

        // A short follow-up such as “kailan sinumbit?” can omit the pronoun
        // because the preceding turn already selected the latest document.
        // Use only the existing, expiring private context; the controller
        // still reauthorizes the document before reading its date.
        if ($hasPrivateDocumentContext && $this->isSubmissionDateQuestion($normalized)) {
            return [
                'intent' => 'document_context_details',
                'topic' => 'submission_date',
                'language' => $this->responseLanguage($normalized),
            ];
        }

        if ($hasPrivateDocumentContext
            && $documentReference['type'] === 'contextual'
            && $this->isDocumentDetailFollowUp($normalized)) {
            return [
                'intent' => 'document_context_details',
                'topic' => $this->isSubmissionDateQuestion($normalized)
                    ? 'submission_date'
                    : ($this->isActionTypeQuestion($normalized) ? 'action_type' : 'summary'),
                'language' => $this->responseLanguage($normalized),
            ];
        }

        // A short date follow-up can safely refer to the newest owned
        // submission even when the preceding context is not available.
        if ($this->isSubmissionDateQuestion($normalized)
            && ! $this->hasDocumentTerm($normalized)
            && $this->requestId($normalized) === null) {
            return [
                'intent' => 'latest_submission_date',
                'language' => $this->responseLanguage($normalized),
            ];
        }

        $hasDocumentTerm = $this->hasDocumentTerm($normalized);
        $hasPersonalReference = $this->hasPersonalReference($normalized);

        if ($this->isComparison($normalized)) {
            if ($hasPersonalReference && ($hasDocumentTerm || $this->hasDocumentStatusTerm($normalized))) {
                return ['intent' => 'compare_latest_documents'];
            }

            if ($hasDocumentTerm && ! $this->isGeneralStatusComparison($normalized)) {
                return ['intent' => 'ambiguous_document'];
            }
        }

        if ($documentReference['type'] === 'latest') {
            return $this->isLatestDocumentIdentityQuestion($normalized)
                ? ['intent' => 'ambiguous_document']
                : ['intent' => 'latest_status'];
        }

        if ($documentReference['type'] === 'ambiguous') {
            return ['intent' => 'ambiguous_document'];
        }

        if ($hasPrivateContext && $this->isContextDependentFollowUp($normalized)) {
            return ['intent' => 'ambiguous_document'];
        }

        if ($this->isUnclearShortMessage($normalized)) {
            return [
                'intent' => 'clarification',
                'language' => $this->responseLanguage($normalized),
            ];
        }

        return ['intent' => 'general_knowledge'];
    }

    private function isWorkflowExplanationQuestion(string $message): bool
    {
        $asksHowOrWhen = preg_match(
            '/\b(?:how|what needs to happen|what must happen|when does|when will|paano|pano|kelan|kailan|ano ang kailangan|ano kailangan)\b/',
            $message,
        ) === 1;
        $asksAboutTransition = preg_match(
            '/\b(?:in progress|pending|become|becomes|becoming|move|moves|moving|change|changes|turn|turns|magiging|maging|mapunta|ma move|ma\s+move)\b/',
            $message,
        ) === 1;
        $asksAboutAcceptance = preg_match(
            '/\b(?:accept(?:ed|ance)?|approve(?:d|al)?|ma accept|maaccept|maaprubahan|matanggap|tanggapin)\b/',
            $message,
        ) === 1;

        return $asksHowOrWhen && ($asksAboutTransition || $asksAboutAcceptance);
    }

    private function isRejectionGuidanceQuestion(string $message): bool
    {
        $hasRejectionCue = preg_match(
            '/\b(?:rejected|rejection|nareject|na reject|tinanggihan|rejectado|reject)\b/',
            $message,
        ) === 1;
        $asksNextStep = preg_match(
            '/\b(?:what should i do|what do i do|what now|what next|next step|how do i proceed|paano na|ano ang gagawin|ano gagawin|ano ngayon|anong susunod|anong dapat gawin|anong gagawin)\b/',
            $message,
        ) === 1;

        return $hasRejectionCue && $asksNextStep;
    }

    private function isRejectionReasonQuestion(string $message): bool
    {
        return preg_match(
            '/\b(?:rejection|rejction|rejecton)\s+reason\b|\b(?:reason|dahilan)\b.*\b(?:rejection|rejction|rejecton)\b|\b(?:why|bakit)\b.*\b(?:rejected|reject|nareject|na\s+reject|tinanggihan)\b|\brecorded\s+reason\b/',
            $message,
        ) === 1;
    }

    /**
     * @return array{type: 'search_text', value: string}|null
     */
    private function extractRejectionDocumentReference(string $message): ?array
    {
        $normalized = $this->normalize($message);
        $candidates = [];

        if (preg_match(
            '/\b(?:why|bakit)\b(?:\s+(?:was|is|did|does))?\s+(?:my|the|yung|ang)?\s*(?:document|doc|submission|dokumento)?\s*(.+?)\s+(?:was|is|got|were|na)?\s*(?:rejected|reject|nareject|na\s+reject|tinanggihan)\b/iu',
            $normalized,
            $matches,
        ) === 1) {
            $candidates[] = $matches[1];
        }

        if (preg_match(
            '/\b(?:rejection|rejction|rejecton)\s+reason\s+(?:for|of|ng|sa)?\s*(?:my|the|yung|ang)?\s*(?:document|doc|submission|dokumento)?\s*(.+)$/iu',
            $normalized,
            $matches,
        ) === 1) {
            $candidates[] = $matches[1];
        }

        foreach ($candidates as $candidate) {
            $candidate = preg_replace(
                '/\b(?:was|is|got|were|na|rejected|reject|nareject|tinanggihan|document|doc|submission|dokumento|my|the|yung|ang)\b/iu',
                ' ',
                $candidate,
            ) ?? $candidate;
            $value = $this->cleanDocumentReference($candidate);

            if ($value !== null && ! $this->isRejectionOnlyReference($value)) {
                return ['type' => 'search_text', 'value' => $value];
            }
        }

        return null;
    }

    private function isRejectionOnlyReference(string $value): bool
    {
        return preg_match(
            '/^(?:reason|rejection|rejction|rejecton|status|was|is|rejected|reject|nareject|tinanggihan)$/iu',
            trim($value),
        ) === 1;
    }

    private function isYesNoStatusComparison(string $message): bool
    {
        $hasStatusTerms = preg_match(
            '/\b(?:pending|in progress|outgoing|completed|returned|rejected|archived)\b/',
            $message,
        ) === 1;
        $asksYesNo = preg_match('/^(?:is|are|does|do|did|has|have|same|pareho|magkaiba|iba)\b/', $message) === 1;
        $compares = preg_match(
            '/\b(?:same as|different from|different than|the same|more advanced|further along|better than|worse than|pareho|magkaiba|mas mataas|mas advance|mas nauuna)\b/',
            $message,
        ) === 1;

        return $hasStatusTerms && $asksYesNo && $compares;
    }

    public function normalize(string $message): string
    {
        $message = mb_strtolower($message, 'UTF-8');
        $message = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $message) ?? '';
        $message = trim(preg_replace('/\s+/u', ' ', $message) ?? '');

        // Normalize common chat shorthand and spelling variants once so each
        // intent can remain semantic instead of accumulating sentence rules.
        foreach ([
            'statuz' => 'status',
            'statu' => 'status',
            'inprogress' => 'in progress',
            'inprogres' => 'in progress',
            'outgoin' => 'outgoing',
            'pendng' => 'pending',
            'msg' => 'message',
            'msgs' => 'messages',
            'mssg' => 'message',
            'recieve' => 'receive',
            'recieved' => 'received',
            'sinubmt' => 'sinubmit',
            'sinumbt' => 'sinubmit',
            'snumit' => 'sinumite',
            'dco' => 'doc',
            'documnt' => 'document',
            'requst' => 'request',
            'reqest' => 'request',
            'upadte' => 'update',
            'updte' => 'update',
            'staus' => 'status',
            'twhats' => 'whats',
        ] as $variant => $canonical) {
            $message = preg_replace(
                '/\b' . preg_quote($variant, '/') . '\b/u',
                $canonical,
                $message,
            ) ?? $message;
        }

        return trim(preg_replace('/\s+/u', ' ', $message) ?? '');
    }

    /** @return list<string> */
    private function extractLaoNumbers(string $message): array
    {
        preg_match_all(
            '~(?<![\p{L}\p{N}])LAO\s*-\s*[\p{L}\p{N}-]+|(?<![\p{L}\p{N}])LAO[\s./#:-]*\d[\p{L}\p{N}./#:-]*~iu',
            $message,
            $matches,
        );

        return array_values(array_unique(array_map(
            static fn (string $number): string => strtoupper(preg_replace('/\s+/u', '-', $number) ?? $number),
            $matches[0] ?? [],
        )));
    }

    private function hasIncompleteLaoReference(string $message): bool
    {
        return preg_match('/\blao\b/', $message) === 1
            && preg_match('/\b(?:document|submission|request|status|tracking|number|numero|bilang)\b/', $message) === 1;
    }

    private function isLatestDocumentInquiry(string $message): bool
    {
        $hasLatestReference = preg_match(
            '/\b(?:latest|last|most recent|newest|recent|recently submitted|just submitted|pinakabag(?:o|ong)|pinakahuli(?:ng)?|pinaka(?:\s+)?latest|pinaka(?:\s+)?huli(?:ng)?|kamakailan|kaka submit|kaka upload)\b/',
            $message,
        ) === 1;

        $refersToSubmittedRecord = $this->hasDocumentTerm($message)
            || $this->hasPersonalReference($message)
            || preg_match('/\b(?:submitted|uploaded|sent|requested|submit|isumite|isinumite|naisumite|sinumite|ipinasa|naipasa|pinas|pinasa|pinass|na upload)\b/', $message) === 1;

        return $hasLatestReference && $refersToSubmittedRecord;
    }

    private function isPrivateDocumentInquiry(string $message): bool
    {
        if ($this->isGeneralStatusComparison($message)) {
            return false;
        }

        $hasDocumentTerm = $this->hasDocumentTerm($message);
        $hasPersonalReference = $this->hasPersonalReference($message);
        $hasRecordQuestion = $this->hasDocumentStatusTerm($message)
            || preg_match(
                '/\b(?:details?|particulars?|contents?|rejection reason|destination|resubmit|reupload|revision|submit(?:ted)?|upload(?:ed)?|filed|send|sent|receive(?:d)?|deliver(?:ed)?|process(?:ed)?|forward(?:ed)?|transmit(?:ted)?|write|message|email|text|location|where|when|why|who|date|tracking|identifier|number|id)\b/',
                $message,
            ) === 1
            || preg_match(
                '/\b(?:detalye|impormasyon|nilalaman|dahilan|saan|kailan|kanino|kalagayan|katayuan|happened|handling|assigned to|submit(?:ted)?|upload(?:ed)?|filed)\b/',
                $message,
            ) === 1;

        if ($this->isGeneralProcessQuestion($message)
            && ! ($hasPersonalReference && $hasRecordQuestion)) {
            return false;
        }

        if ($this->isGeneralResubmissionQuestion($message)) {
            return false;
        }

        if ($this->isGeneralAcceptanceQuestion($message)) {
            return false;
        }

        if ($hasPersonalReference && $hasDocumentTerm) {
            return true;
        }

        if ($this->hasPossessiveReference($message) && $hasRecordQuestion) {
            return true;
        }

        if ($hasDocumentTerm && $hasRecordQuestion) {
            return ! $this->isGeneralDocumentDefinition($message);
        }

        if ($hasRecordQuestion && preg_match('/\b(?:it|this|that|there|they|them|their|iyan|niyan|yan|jan|diyan|iyon|yun|doon|dun|sya|siya|nya)\b/', $message) === 1) {
            return true;
        }

        return preg_match(
            '/\b(?:this|that|these|those|selected|current)\s+(?:document|submission|file|record|request)\b|\b(?:yung|itong|iyan|iyon|nito|niyon)\s+(?:documento|dokumento|papel)\b/',
            $message,
        ) === 1;
    }

    private function isGeneralDocumentDefinition(string $message): bool
    {
        return (
            preg_match(
                '/\b(?:what does|what is the meaning of|define|explain|describe|meaning of|ibig sabihin|kahulugan|ipaliwanag)\b/',
                $message,
            ) === 1
            || preg_match('/\b(?:transmittal|endorsement|submission process|document requirements)\b/', $message) === 1
        )
            && ! $this->hasPersonalReference($message)
            && preg_match('/\b(?:this|that|these|those|selected|current|itong|iyan|iyon|yung)\b/', $message) !== 1;
    }

    private function isLatestDocumentIdentityQuestion(string $message): bool
    {
        // “What is the status of my latest document?” is a direct latest
        // record lookup. Only ask for a selection when the client is asking
        // which record is latest and has not requested status or details.
        if ($this->hasDocumentStatusTerm($message)
            || preg_match('/\b(?:detail|details|particulars|information|info|detalye|impormasyon)\b/', $message) === 1) {
            return false;
        }

        return preg_match(
            '/\b(?:which|what|anong|alin)\b.*\b(?:document|doc|submission|sinumite|ipinasa)\b/',
            $message,
        ) === 1;
    }

    private function isGeneralProcessQuestion(string $message): bool
    {
        $asksForProcess = preg_match(
            '/\b(?:how|can i|where can i|do i|what can i|want to|need to|would like to|gusto|nais|balak|steps|process|procedure|requirements|paano|hakbang|proseso|kailangan)\b/',
            $message,
        ) === 1;
        $mentionsGeneralAction = preg_match(
            '/\b(?:submit|submission|upload|request|revise|transmittal|endorsement|requirements?|mag submit|magsubmit|magsumite|isumite|mag upload|mag revise)\b/',
            $message,
        ) === 1;

        return $asksForProcess && $mentionsGeneralAction
            && ! preg_match('/\b(?:latest|most recent|current status|kalagayan|katayuan|sent to|destination|rejection reason|document number|document id|tracking number|pending|in progress|outgoing|completed|returned|rejected|archived)\b/', $message);
    }

    private function isGeneralResubmissionQuestion(string $message): bool
    {
        $asksAboutProcess = preg_match(
            '/\b(?:can|how|should i|what should i|paano|pano|pwede|maaari|kailangan)\b/',
            $message,
        ) === 1;
        $mentionsResubmission = preg_match(
            '/\b(?:resubmit|submit again|submit a new|new submission|rejected submission|revision request|revised document|magsumite ulit|isumite ulit|bagong submission|rejected na submission)\b/',
            $message,
        ) === 1;

        $hasContextualReference = preg_match('/\b(?:it|this|that|its|sya|siya|nya|iyan|iyon|yun|yung)\b/', $message) === 1;

        return $asksAboutProcess
            && $mentionsResubmission
            && ! $this->hasPossessiveReference($message)
            && ! $hasContextualReference;
    }

    private function isGeneralAcceptanceQuestion(string $message): bool
    {
        $asksAboutAcceptance = preg_match('/\b(?:can|how|does|do|what makes|paano|pano|pwede|maaari)\b/', $message) === 1
            && preg_match('/\b(?:accept(?:ed|ance)?|approve(?:d|al)?|tanggap|ma accept|maaccept|maaprubahan)\b/', $message) === 1;
        $hasSpecificReference = preg_match('/\b(?:it|this|that|my|mine|our|ours|sya|siya|nya|iyan|iyon|yun)\b/', $message) === 1;

        return $asksAboutAcceptance && ! $hasSpecificReference;
    }

    private function hasPossessiveReference(string $message): bool
    {
        return preg_match('/\b(?:my|mine|our|ours|ko|kong|akin|amin|namin|natin|sa akin|sa amin|akin ko)\b/', $message) === 1;
    }

    private function isStandaloneRejectionDefinition(string $message): bool
    {
        $message = trim($message);

        if (preg_match('/\b(?:rejection|rejction|rejecton)\s+reason\b/iu', $message) === 1) {
            return false;
        }

        return preg_match(
            '/^(?:(?:what does|what is the meaning of|meaning of|define|ano ang ibig sabihin ng|ibig sabihin ng)\s+)?(?:rejection|reject|rejected|rejction|rejecton|nareject|na reject|tinanggihan)(?:\s+(?:means?|ibig sabihin|meaning))?[.! ]*$/iu',
            $message,
        ) === 1;
    }

    private function isStandaloneAcceptanceTerm(string $message): bool
    {
        return preg_match(
            '/^(?:acceptance|accepted|approval|approve|tanggap|pagtanggap|pag accept|pag-accept|maaccept)[.! ]*$/iu',
            trim($message),
        ) === 1;
    }

    private function isStandaloneCopyTypeTerm(string $message): bool
    {
        return preg_match(
            '/^(?:original|original copy|soft copy|softcopy|digital copy|electronic copy|uri ng kopya|copy type)[.! ]*$/iu',
            trim($message),
        ) === 1;
    }

    private function isStandaloneRequestTerm(string $message): bool
    {
        return preg_match(
            '/^(?:request|requests|document request|document requests|hiling|kahilingan)[.! ]*$/iu',
            trim($message),
        ) === 1;
    }

    private function isAcknowledgment(string $message): bool
    {
        return preg_match(
            '/^(?:thanks|thank you|thank you so much|thx|salamat|maraming salamat|sige|okay|ok|noted|got it|understood|gets|gets ko|opo|oo|cge|arigato)(?:\s+(?:na|po|naman|ha|rin|din|thanks|salamat)){0,2}[.! ]*$/',
            $message,
        ) === 1;
    }

    private function isPositiveConversation(string $message): bool
    {
        return preg_match('/^(?:mabuti|mabuti rin|mabuti naman|mabuti po|mabuti rin po)[.! ]*$/', $message) === 1;
    }

    private function isUnclearShortMessage(string $message): bool
    {
        if ($message === '' || str_contains($message, '?')) {
            return false;
        }

        $tokens = preg_split('/\s+/u', trim($message), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($tokens) > 3 || mb_strlen($message, 'UTF-8') > 40) {
            return false;
        }

        return preg_match(
            '/\b(?:what|how|when|where|why|which|define|explain|ano|paano|pano|kailan|saan|bakit|ibig sabihin|lextrack|document|documents?|request|requests?|status|message|messages?)\b/i',
            $message,
        ) !== 1;
    }

    private function isEmailDeliveryInquiry(string $message): bool
    {
        $hasEmailTopic = preg_match('/\b(?:email|e mail|notification|notifications|inbox|email ko|personal email)\b/', $message) === 1;
        $asksAboutPersonalEmail = preg_match('/\b(?:personal|my|our|ko|amin)\s+email\b/', $message) === 1;
        $hasDeliveryOrPersonalCue = preg_match(
            '/\b(?:receive|received|deliver|delivered|sent|send|notification|notifications|personal|my email|email ko|nakatanggap|natanggap|dumating|ipinadala)\b/',
            $message,
        ) === 1;
        $asksAboutOwnDelivery = $this->hasPossessiveReference($message)
            && $hasDeliveryOrPersonalCue;

        return $hasEmailTopic && ($asksAboutPersonalEmail || $asksAboutOwnDelivery)
            && ! preg_match('/\b(?:login|log in|sign in|mag login|mag log in|makapag login)\b/', $message);
    }

    private function isMessageMetadataInquiry(string $message): bool
    {
        $hasMessageTopic = preg_match('/\b(?:messages?|inbox|mensah?e|mensahe|unread|read messages?)\b/', $message) === 1;
        $hasMessageTopic = $hasMessageTopic
            || preg_match('/\b(?:nag ?message|minessage)\b/', $message) === 1;
        $asksForMetadata = preg_match(
            '/\b(?:how many|do i have|any|are there|check|count|number of|ilan|ilang|mayroon ba|meron ba|may ba|unread|read messages?)\b/',
            $message,
        ) === 1
            || preg_match('/\b(?:may|meron|mayroon)\b.*\b(?:messages?|mensahe)\b/', $message) === 1
            || preg_match('/\b(?:did|has|have)\b.*\b(?:messages?|mensahe)\b/', $message) === 1
            || preg_match('/\b(?:messages?|mensahe)\b.*\b(?:from (?:the )?legal|from (?:the )?office|to me|sa akin)\b/', $message) === 1
            || preg_match('/\b(?:nag ?message|minessage|nakatanggap ng (?:messages?|mensahe)|nagpadala ng (?:messages?|mensahe))\b/', $message) === 1;
        $isProcedureQuestion = preg_match('/\b(?:how do i|how can i|paano|saan|where can i)\b/', $message) === 1
            && ! $this->hasUnreadCue($message)
            && ! $this->isCountQuestion($message)
            && ! preg_match('/\b(?:do i have|are there|mayroon ba|meron ba|may ba)\b/', $message);

        return $hasMessageTopic && $asksForMetadata && ! $isProcedureQuestion;
    }

    private function isMessageContentRequest(string $message, bool $hasPrivateMessageContext): bool
    {
        $hasMessageTopic = preg_match(
            '/\b(?:messages?|inbox|conversation|chat|mensahe|usapan|nag ?message|minessage)\b/',
            $message,
        ) === 1;
        $asksForContent = preg_match(
            '/\b(?:about|say|says|said|tell|told|reply|replied|respond|response|answer|answered|read|show|summari[sz]e|summary|content|contents|body|details?|ano ang sinabi|ano sinabi|ano sabi|sagot|sinagot|sumagot|tugon|sabi ng|sinabi ng|laman|basahin|ibuod|buod|tungkol saan)\b/',
            $message,
        ) === 1;
        $asksWhatSpecificMessage = preg_match(
            '/\b(?:what s|what is|what was|ano ang|ano yung)\b.*\b(?:the|my|this|that|message|mensahe)\b/',
            $message,
        ) === 1
            && preg_match('/\b(?:page|feature|system|lextrack|portal)\b/', $message) !== 1;
        $asksWhatOfficeSaid = preg_match(
            '/\b(?:what did|what has|what was|ano ang sinabi|ano sinabi|ano sabi|ano ang sagot|ano ang tugon|sabi ng|sinabi ng)\b.*\b(?:legal|office|lao|staff|opisina)\b|\b(?:legal|office|lao|staff|opisina)\b.*\b(?:say|said|tell|told|reply|replied|respond|response|answer|answered|sinabi|sabi|sagot|sumagot|tugon)\b/',
            $message,
        ) === 1;
        $asksWhatSomeoneToldThem = preg_match(
            '/\b(?:what did they tell me|what did they say|what was i told|ano ang sinabi sa akin|ano sabi sa akin|ano sinabi sa akin)\b/',
            $message,
        ) === 1;
        $contextualContentFollowUp = $hasPrivateMessageContext
            && preg_match('/\b(?:it|that|this|they|them|their|there|one|sya|siya|niya|iyon|yun|ito|nila|nya|iyon ba|yun ba)\b/', $message) === 1;

        return ($hasMessageTopic && ($asksForContent || $asksWhatSpecificMessage))
            || $asksWhatOfficeSaid
            || $asksWhatSomeoneToldThem
            || $contextualContentFollowUp;
    }

    private function hasUnreadCue(string $message): bool
    {
        return preg_match('/\b(?:unread|not read|hindi pa nababasa|di pa nababasa)\b/', $message) === 1;
    }

    private function isCountQuestion(string $message): bool
    {
        return preg_match('/\b(?:how many|count|number of|ilan|ilang|dami ng|karami)\b/', $message) === 1;
    }

    private function isYesNoCountQuestion(string $message): bool
    {
        return ! $this->isCountQuestion($message)
            && preg_match('/\b(?:do i have|is there|are there|have i|mayroon ba|meron ba|may ba)\b/', $message) === 1;
    }

    private function isDocumentCountInquiry(string $message): bool
    {
        $hasCountCue = preg_match(
            '/\b(?:how many|count|number of|do i have|ilan|ilang|dami ng|karami)\b/',
            $message,
        ) === 1;
        $hasDocumentOrStatus = $this->hasDocumentTerm($message)
            || $this->requestedStatus($message) !== null;
        $asksForKinds = preg_match('/\b(?:type|types|kind|kinds|category|categories)\b/', $message) === 1;

        return $hasCountCue && $hasDocumentOrStatus && ! $asksForKinds && ! $this->isComparison($message);
    }

    private function isPrivateDocumentRequestInquiry(string $message): bool
    {
        $hasRequestTerm = preg_match('/\b(?:document requests?|requests?|requested|requesting|req|hiling|application|hinihiling|humiling)\b/', $message) === 1;
        $hasPersonalReference = $this->hasPersonalReference($message);
        $hasRecordCue = preg_match(
            '/\b(?:status|pending|accepted|rejected|copy|soft copy|original|pickup|schedule|download|available|availability|latest|recent|update|updates|count|how many|ilan|mayroon|meron|may|number|details?|type|pending request|request status|kalagayan|katayuan|kumusta|kamusta|balita|news|progress(?:ing)?|nakuha|kunin|downloadable)\b/',
            $message,
        ) === 1;

        if (! $hasRequestTerm || ! $hasRecordCue) {
            return false;
        }

        // General procedure questions such as “How do I request a document?”
        // remain approved knowledge-base questions unless they refer to the
        // authenticated client's own request record.
        return $hasPersonalReference
            || preg_match('/\b(?:my request|latest request|recent request|request ko|hiling ko|sa request ko|requests? ko)\b/', $message) === 1
            || $this->requestId($message) !== null
            || (! $this->isGeneralProcessQuestion($message)
                && preg_match('/\b(?:status|update|updates|pending|accepted|rejected|pickup|download|kumusta|kamusta|balita|news|progress(?:ing)?)\b/', $message) === 1);
    }

    private function isRequestCountInquiry(string $message): bool
    {
        return preg_match('/\b(?:how many|count|number of|do i have|have i|ilan|ilang|dami|karami|may|mayroon|meron)\b/', $message) === 1;
    }

    private function requestedRequestStatus(string $message): ?string
    {
        foreach ([
            'pending' => '/\b(?:pending|awaiting|waiting|nakabinbin|hinihintay)\b/',
            'accepted' => '/\b(?:accepted|approved|approve|tinanggap|na approve|naaprubahan)\b/',
            'rejected' => '/\b(?:rejected|reject|tinanggihan|na reject|nareject)\b/',
        ] as $status => $pattern) {
            if (preg_match($pattern, $message) === 1) {
                return $status;
            }
        }

        return null;
    }

    private function requestTopic(string $message): string
    {
        if (preg_match('/\b(?:copy type|copy|soft copy|original|uri|type)\b/', $message) === 1) {
            return 'copy_type';
        }

        if (preg_match('/\b(?:pickup|pick up|pick it up|schedule|when can i get|when can i pick|makukuha|makuha|kukunin|kukuha|kailan makukuha|kailan kukunin|kuha|iskedyul)\b/', $message) === 1) {
            return 'pickup';
        }

        if (preg_match('/\b(?:download|downloadable|available|availability|soft copy)\b/', $message) === 1) {
            return 'download';
        }

        return 'summary';
    }

    private function hasContextualRecordReference(string $message): bool
    {
        return preg_match('/\b(?:it|this|that|its|they|them|their|one|siya|niya|niyan|nya|nyan|iyon|iyon ba|yun|yan|jan|diyan|doon|dun|dito|yung|that one|this one)\b/', $message) === 1;
    }

    private function requestedStatus(string $message): ?string
    {
        $patterns = [
            'in_progress' => '/\b(?:in progress|being processed|under process|pinoproseso|nasa proseso)\b/',
            'pending' => '/\b(?:pending|awaiting review|for review|nakabinbin)\b/',
            'outgoing' => '/\b(?:outgoing|sent out|naipadala|na forward)\b/',
            'completed' => '/\b(?:completed|complete|finished|natapos|nakumpleto)\b/',
            'returned' => '/\b(?:returned|ibinalik|naibalik)\b/',
            'rejected' => '/\b(?:rejected|rejection|tinanggihan|na reject)\b/',
            'archived' => '/\b(?:archived|archive|naka archive)\b/',
        ];

        foreach ($patterns as $status => $pattern) {
            if (preg_match($pattern, $message) === 1) {
                return $status;
            }
        }

        return null;
    }

    private function isAcceptanceGuidanceFollowUp(string $message): bool
    {
        $asksHow = preg_match('/\b(?:how|paano|pano|what do i need|ano ang kailangan|paano ba)\b/', $message) === 1;
        $mentionsAcceptance = preg_match(
            '/\b(?:accept(?:ed|ance)?|approve(?:d|al)?|ma accept|maaccept|maaprubahan|ma approve|maapprove|matanggap|maging accepted)\b/',
            $message,
        ) === 1;

        return $asksHow && $mentionsAcceptance
            && preg_match('/\b(?:it|this|that|she|he|sya|siya|nya|ito|iyon|yun|the document|the submission)\b/', $message) === 1;
    }

    private function isAcceptancePossibilityQuestion(string $message): bool
    {
        return preg_match('/\b(?:can|could|would|is it possible|pwede ba|maaari ba)\b/', $message) === 1
            && preg_match('/\b(?:accept(?:ed|ance)?|approve(?:d|al)?|ma accept|maaccept|maaprubahan|matanggap)\b/', $message) === 1
            && preg_match('/\b(?:it|this|that|sya|siya|nya|ito|iyon|yun)\b/', $message) === 1;
    }

    private function isAcceptanceStateQuestion(string $message): bool
    {
        return preg_match('/\b(?:already accepted|accepted na ba|approved na ba|tanggap na ba|has it been accepted|is it accepted|accepted already)\b/', $message) === 1
            && preg_match('/\b(?:it|this|that|sya|siya|nya|ito|iyon|yun)\b/', $message) === 1;
    }

    private function isResubmissionQuestion(string $message): bool
    {
        return preg_match(
            '/\b(?:resubmit|submit again|reupload|upload again|revision|revised|magsumite ulit|isumite ulit|i submit ulit|i upload ulit)\b/',
            $message,
        ) === 1;
    }

    private function isDocumentStatusFollowUp(string $message): bool
    {
        return $this->hasDocumentStatusTerm($message)
            || preg_match('/\b(?:accepted already|approved already|accepted na|approved na|tanggap na|na accept na|naapprove na)\b/', $message) === 1;
    }

    private function isDocumentDetailFollowUp(string $message): bool
    {
        if ($this->isDocumentStatusFollowUp($message)
            || $this->isAcceptanceGuidanceFollowUp($message)
            || $this->isResubmissionQuestion($message)) {
            return false;
        }

        return $this->isActionTypeQuestion($message)
            || preg_match('/\b(?:detail|details|particulars|information|info|detalye|impormasyon|tungkol saan|about)\b/', $message) === 1
            || $this->isContextDependentFollowUp($message);
    }

    private function isActionTypeQuestion(string $message): bool
    {
        return preg_match('/\b(?:action type|assigned action|what action|anong action|ano ang action|action niya|action nya)\b/', $message) === 1;
    }

    private function isSubmissionDateQuestion(string $message): bool
    {
        return preg_match(
            '/\b(?:when did i submit|when was .* submitted|submission date|submitted date|kailan|anong petsa)\b.*\b(?:submit|submitted|sinubmit|sinumbit|sumbit|pasa|ipinasa|pinasa|sinumite)\b/',
            $message,
        ) === 1;
    }

    private function isNamedDocumentInquiry(string $message): bool
    {
        $hasQuotedName = preg_match('/["“‘][^"”’]+["”’]/u', $message) === 1;

        return ($this->hasDocumentTerm($message) || $hasQuotedName)
            && ($this->hasDocumentStatusTerm($message)
                || preg_match('/\b(?:detail|details|information|info|about|tungkol|detalye|impormasyon|action type|assigned action)\b/', $message) === 1);
    }

    private function isDocumentSearchPhrase(string $message): bool
    {
        $hasSearchCue = $this->hasDocumentStatusTerm($message)
            || preg_match('/\b(?:detail|details|information|info|action type|assigned action|named|called|titled)\b/', $message) === 1;
        $hasTopicStructure = preg_match(
            '/\b(?:about|regarding|concerning|tungkol sa|patungkol sa|para sa|dun sa|doon sa|diyan sa)\b/',
            $message,
        ) === 1;

        return ($this->hasDocumentTerm($message) && ($hasSearchCue || $this->hasPossessiveReference($message)))
            || $this->hasPossessiveReference($message)
            || $hasTopicStructure;
    }

    private function documentReferenceAction(string $message): string
    {
        if (preg_match('/\b(?:document\s+)?type\b/', $message) === 1) {
            return 'get_document_type';
        }

        return preg_match(
            '/\b(?:update|updates|updated|progress|ano na|anong balita|any news|what happened|how is|how are)\b/',
            $message,
        ) === 1
            ? 'get_document_updates'
            : 'get_document_status';
    }

    private function isPotentialPrivateRecordInquiry(string $message): bool
    {
        $hasRecordQuestion = $this->hasDocumentStatusTerm($message)
            || preg_match('/\b(?:details?|particulars?|destination|recipient|pickup|download|availability|action type|assigned action)\b/', $message) === 1;
        $hasReferenceStructure = preg_match('/\b(?:status|details?|information|update|where|when|what)\s+(?:of|for|about|on|sa|ng)\b/', $message) === 1;
        $isKnownStatusDefinition = preg_match('/\b(?:pending|in progress|outgoing|completed|returned|rejected|archived)\b/', $message) === 1
            && ! $this->hasPersonalReference($message);

        return $hasRecordQuestion && $hasReferenceStructure && ! $isKnownStatusDefinition;
    }

    /**
     * Extract a client-provided document reference locally; it is never sent
     * to OpenAI. The value is derived from the message structure, not from a
     * list of document subjects or known examples.
     *
     * @return array{type: 'search_text'|'document_type'|'submission_date', field?: string, value: string}|null
     */
    public function extractDocumentReference(string $message): ?array
    {
        $normalized = $this->normalize($message);

        // “latest” is a record selector, never a document topic. Resolve it
        // through the existing latest-document intent instead of allowing a
        // generic possessive pattern to search for the literal word.
        if ($this->isLatestDocumentInquiry($normalized)) {
            return null;
        }

        // Rejection questions have their own status-aware extraction path.
        if ($this->isRejectionReasonQuestion($normalized)) {
            return null;
        }

        if (preg_match('/["“‘]([^"”’]+)["”’]/u', $message, $matches) === 1) {
            $value = $this->cleanDocumentReference($matches[1], preserveTitleWords: true);

            return $value === null ? null : ['type' => 'search_text', 'value' => $value];
        }

        if (preg_match(
            '/\b(?:document|doc|submission)\s+(?:named|called|titled)\s+(.+?)(?:[?!.,]|$)/iu',
            $normalized,
            $matches,
        ) === 1) {
            $value = $this->cleanDocumentReference($matches[1], preserveTitleWords: true);

            return $value === null ? null : ['type' => 'search_text', 'value' => $value];
        }

        if (preg_match(
            '/\b(?:document\s+)?type\s*(?:is|of|for|ng|na|:)?\s+(.+?)(?:[?!.,]|$)/iu',
            $normalized,
            $matches,
        ) === 1) {
            $value = $this->cleanDocumentReference($matches[1]);

            if ($value !== null) {
                return [
                    'type' => 'document_type',
                    'field' => 'document_type',
                    'value' => $value,
                ];
            }
        }

        // A submission date is a record reference, not the answer itself.
        // Keep it separate from free-text document matching so phrases such
        // as “update with my doc submitted Sep 20” resolve against the
        // authenticated client's created_at value in Laravel.
        if (($submissionDate = $this->extractSubmissionDate($message)) !== null) {
            return $submissionDate;
        }

        $patterns = [
            '/\b(?:about|regarding|concerning|tungkol\s+sa|patungkol\s+sa|para\s+sa)\s+(.+?)(?:[?!.,]|$)/iu',
            '/\b(?:dun\s+sa|doon\s+sa|diyan\s+sa|yan\s+sa|niyan\s+sa)\s+(.+?)(?:\s+(?:document|doc|submission)\b|[?!.,]|$)/iu',
            '/\b(?:with|for|on)\s+(?:my|our|the)\s+(.+?)(?:\s+(?:document|doc|submission)\b|[?!.,]|$)/iu',
            '/\b(?:my|our|mine)\s+(.+?)(?:[?!.,]|$)/iu',
            '/^(.+?)\s+(?:document|doc|submission)\b/iu',
            '/^\s*(?:document|doc|submission)\s+(.+?)(?:[?!.,]|$)/iu',
            '/^(.+?)\s+(?:ko|mo|kong|mong|akin|natin|namin)\s*$/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $normalized, $matches) !== 1) {
                continue;
            }

            $value = $this->cleanDocumentReference($matches[1]);
            if ($value !== null) {
                return ['type' => 'search_text', 'value' => $value];
            }
        }

        return null;
    }

    /**
     * Extract a date used to identify a submission without querying or
     * exposing any document data. A missing year means the current app year.
     *
     * @return array{type: 'submission_date', field: 'created_at', value: string}|null
     */
    public function extractSubmissionDate(string $message): ?array
    {
        $month = '(?:jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|jun(?:e)?|jul(?:y)?|aug(?:ust)?|sep(?:t(?:ember)?)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?)';
        $pattern = '/\b(?:submitted|submited|submtted|sinubmit(?:ted)?|sinumite|ipinasa|naipasa)(?:\s+(?:on|noong|ng))?\s+('
            . $month . '\s+\d{1,2}(?:\s*,?\s*\d{4})?|\d{1,2}[\/-]\d{1,2}(?:[\/-]\d{2,4})?)\b/iu';

        if (preg_match($pattern, $message, $matches) !== 1) {
            return null;
        }

        $value = trim($matches[1]);
        $date = null;

        try {
            if (preg_match('/^([a-z]+)\s+(\d{1,2})(?:\s*,?\s*(\d{4}))?$/iu', $value, $parts) === 1) {
                $months = [
                    'jan' => 1, 'january' => 1,
                    'feb' => 2, 'february' => 2,
                    'mar' => 3, 'march' => 3,
                    'apr' => 4, 'april' => 4,
                    'may' => 5,
                    'jun' => 6, 'june' => 6,
                    'jul' => 7, 'july' => 7,
                    'aug' => 8, 'august' => 8,
                    'sep' => 9, 'sept' => 9, 'september' => 9,
                    'oct' => 10, 'october' => 10,
                    'nov' => 11, 'november' => 11,
                    'dec' => 12, 'december' => 12,
                ];
                $monthNumber = $months[mb_strtolower($parts[1], 'UTF-8')] ?? null;
                $year = isset($parts[3]) && $parts[3] !== '' ? (int) $parts[3] : now()->year;
                $date = $monthNumber === null
                    ? null
                    : Carbon::createSafe($year, $monthNumber, (int) $parts[2]);
            } elseif (preg_match('/^(\d{1,2})[\/-](\d{1,2})(?:[\/-](\d{2,4}))?$/', $value, $parts) === 1) {
                $year = isset($parts[3]) && $parts[3] !== '' ? (int) $parts[3] : now()->year;
                if ($year < 100) {
                    $year += 2000;
                }
                $date = Carbon::createSafe($year, (int) $parts[1], (int) $parts[2]);
            }
        } catch (\Throwable) {
            $date = null;
        }

        return $date === null
            ? null
            : ['type' => 'submission_date', 'field' => 'created_at', 'value' => $date->toDateString()];
    }

    /** Extract a client-provided title locally; it is never sent to OpenAI. */
    public function extractDocumentName(string $message): ?string
    {
        return $this->extractDocumentReference($message)['value'] ?? null;
    }

    private function cleanDocumentReference(string $value, bool $preserveTitleWords = false): ?string
    {
        $value = $this->normalize($value);
        if (! $preserveTitleWords) {
            $value = preg_replace(
                '/^(?:(?:yung|ang|the|my|our|a|an|sa|ng|dun\s+sa|doon\s+sa|diyan\s+sa)\s+)+/u',
                '',
                $value,
            ) ?? $value;
        }
        $value = preg_replace(
            '/\s+(?:(?:document|doc|submission|file|record)(?:\s+(?:ko|mo|kong|mong|niya|nya|ito|iyon|yan|diyan))?|ko|mo|kong|mong|akin|natin|namin)\s*$/u',
            '',
            $value,
        ) ?? $value;
        $value = preg_replace(
            '/^(?:(?:anong|ano|what|how|when|where|kamusta|kumusta|status|update|updates|details?|detalye|information|info|latest|current)\s+)+/u',
            '',
            $value,
        ) ?? $value;
        $value = trim($value, " \t\n\r\0\x0B?!.,:;-");

        $noise = [
            'a', 'an', 'ang', 'about', 'and', 'document', 'documents', 'doc', 'dun',
            'for', 'how', 'is', 'ko', 'mo', 'my', 'ng', 'of', 'sa', 'status', 'the',
            'this', 'to', 'tungkol', 'update', 'updates', 'what', 'with', 'yung',
        ];
        if ($preserveTitleWords) {
            $noise = array_values(array_diff($noise, ['my', 'our', 'a', 'an', 'the']));
        }
        $tokens = preg_split('/\s+/u', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $meaningful = array_values(array_filter(
            $tokens,
            static fn (string $token): bool => mb_strlen($token, 'UTF-8') >= 2
                && ! in_array($token, $noise, true),
        ));

        return $meaningful === [] ? null : implode(' ', $meaningful);
    }

    private function hasContextualDocumentReference(string $message): bool
    {
        return preg_match('/\b(?:it|this|that|its|sya|siya|niya|nya|iyan|niyan|yun|yan|jan|diyan|dito|doon|dun|yung|that one|this one)\b/', $message) === 1
            || ($this->hasPossessiveReference($message) && $this->hasDocumentTerm($message));
    }

    private function responseLanguage(string $message): string
    {
        return preg_match('/\b(?:salamat|sige|opo|oo|po|ko|ba|ano|paano|pano|kamusta|kumusta|mabuti|mensahe|hindi|nakatanggap|dokumentong?)\b/', $message) === 1
            ? 'filipino'
            : 'english';
    }

    private function isGeneralStatusComparison(string $message): bool
    {
        $hasStatusTerms = preg_match(
            '/\b(?:pending|in progress|outgoing|completed|returned|rejected|archived|status|statuses|kalagayan|katayuan)\b/',
            $message,
        ) === 1;

        return $hasStatusTerms && ! $this->hasPersonalReference($message);
    }

    private function isComparison(string $message): bool
    {
        return preg_match(
            '/\b(?:compare|comparison|contrast|versus|vs|difference|differences|which is better|more advanced|further along|which one is ahead|ihambing|ikumpara|pagkakaiba|kumpara|alin ang mas)\b/',
            $message,
        ) === 1;
    }

    private function isContextDependentFollowUp(string $message): bool
    {
        return preg_match(
            '/^(?:and|also|then|what about|how about|why|where|when|does it|is it|what happened|paano|bakit|saan|kailan|ano naman|at|tapos|iyon|yun|ito|niya|doon|dun)\b/',
            $message,
        ) === 1
            || preg_match('/\b(?:it|this|that|there|they|them|their|those|its|iyan|niyan|yan|diyan|iyon|yun|doon|dun|status|state|details?|destination|recipient|sent|sent date|rejection reason|kalagayan|katayuan)\b/', $message) === 1
            || preg_match('/\b(?:what is next|what happens next|ano ang susunod|anong susunod|ano next)\b/', $message) === 1;
    }

    private function isUnsupportedRequest(string $original, string $normalized): bool
    {
        if (preg_match(
            '/\b(?:fuck|fucking|shit|bullshit|damn|bitch|gago|gaga|tanga|ulol|putang ina|puta)\b/i',
            $normalized,
        ) === 1) {
            return true;
        }

        if (preg_match('/\b(?:mama mo|nanay mo|your mom)\b/i', $normalized) === 1) {
            return true;
        }

        if (preg_match(
            '/\b(?:legal advice|should i sue|what should i do (?:about|with|in) (?:my )?(?:case|lawsuit|legal matter)|is my case legal|legal opinion|personal legal problem|payo sa kaso ko|ano ang dapat kong gawin sa kaso)\b/',
            $normalized,
        ) === 1) {
            return true;
        }

        if (preg_match('/\b(?:payo|advice)\b.*\b(?:case|lawsuit|legal|kaso)\b/', $normalized) === 1) {
            return true;
        }

        if (preg_match(
            '/\b(?:ignore|bypass|override|reveal|show|print)\b.*\b(?:system prompt|hidden instructions|private data|database|all clients|all users|secret)\b/',
            $normalized,
        ) === 1) {
            return true;
        }

        if (preg_match(
            '/\b(?:what did|what have|repeat|quote|read|show|summari[sz]e)\b.*\b(?:i|we|my|our)\b.*\b(?:send|sent|write|wrote|say|said|tell|told|message|email|text)\b|\b(?:ano ang sinabi ko|ano ang ipinadala ko|ano ang isinulat ko|sinabi ko sa kanila|ipinadala ko sa office)\b/',
            $normalized,
        ) === 1) {
            return true;
        }

        if ($this->hasPersonalReference($normalized)
            && (preg_match('/\b(?:name|address|account|profile|identity|personal data|contact details|phone number|email address|user id)\b/', $normalized) === 1
                || preg_match('/\b(?:my|ko|akin|amin)\s+email\b|\bemail\s+(?:ko|on my account)\b/', $normalized) === 1)) {
            return true;
        }

        $isInformationalUpdate = preg_match(
            '/\b(?:any|an|a|what|give me|provide|send me)\b.*\bupdate\b|\bupdate\b.*\b(?:on|about|for|with|sa|tungkol)\b/',
            $normalized,
        ) === 1;
        $isStatusQuestion = $this->hasDocumentStatusTerm($normalized)
            && preg_match('/\b(?:did|does|do|is|are|has|have|was|were|ba|already|yet|na)\b/', $normalized) === 1;

        if (! $this->isGeneralProcessQuestion($normalized)
            && ! $isInformationalUpdate
            && ! $isStatusQuestion
            && preg_match(
            '/\b(?:submit|upload|request|revise|send|forward|approve|accept|reject|change|update|delete|archive|modify|cancel|alter|restore|magsumite|isumite|mag upload|mag revise|aprubahan|baguhin|burahin|tanggalin|i reject|i approve)\b.*\b(?:document|submission|status|record|dokumento|papel|sinumite)\b/',
            $normalized,
        ) === 1) {
            return true;
        }

        if (preg_match(
            '/\b(?:read|show|summari[sz]e|quote|open|what is inside|what(?:\s+s)? in|whats? in|contents of|basahin|ipakita ang laman)\b.*\b(?:my |the )?(?:uploaded )?(?:file|pdf|attachment|document|messages?|conversation|file ko|dokumento|papel)\b/',
            $normalized,
        ) === 1) {
            return true;
        }

        return preg_match('/\b[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}\b/i', $original) === 1
            || preg_match('/\b(?:my name is|i am|ako si|ang pangalan ko ay)\s+[\p{Lu}][\p{L}.-]+/u', $original) === 1
            || preg_match('/(?<!\d)(?:\+?\d[\s().-]?){9,}\d(?!\d)/', $original) === 1;
    }

    private function hasDocumentTerm(string $message): bool
    {
        return preg_match(
            '/\b(?:document|documents|legal document|submission|submissions|submit|submitted|uploaded|filed|file|files|record|records|request|requests|contract|contracts|agreement|agreements|case file|attachment|attachments|transmittal|endorsement|doc|docs|dokumento|dokuments|papel|papeles|kasulatan|kontrata|sinumite|isumite|isinumite|naisumite|ipinasa|naipasa)\b/',
            $message,
        ) === 1;
    }

    private function hasPersonalReference(string $message): bool
    {
        return preg_match(
            '/\b(?:my|mine|our|ours|i|ako|ko|akin|amin|namin|natin|sa akin|sa amin|akin ko)\b/',
            $message,
        ) === 1;
    }

    private function hasDocumentStatusTerm(string $message): bool
    {
        return preg_match(
            '/\b(?:status(?:es)?|state|kalagayan|katayuan|estado|update|progress(?:ing)?|kumusta|kamusta|ano na|anong balita|any news|how is|how are|what happened|pending|in progress|outgoing|completed|returned|rejected|archived|approved|approval|accepted|na approve|naaprubahan|natanggap|na reject|nareject|na forward|naforward|naipadala|naipasa|pinoproseso|inaasikaso|nasa proseso|nakumpleto|natapos|naibalik)\b/',
            $message,
        ) === 1;
    }

    private function selectionIndex(string $message): ?int
    {
        if (preg_match('/\b(?:latest|most recent|newest|pinakabago|pinakahuli)\b/', $message) === 1) {
            return 0;
        }

        if (preg_match('/^(\d{1,2})$/', $message, $matches) === 1) {
            return max(0, (int) $matches[1] - 1);
        }

        if (preg_match('/\b(?:document|doc|request|req|number|no)\s+(\d{1,2})\b/', $message, $matches) === 1) {
            return max(0, (int) $matches[1] - 1);
        }

        $ordinals = [
            'first' => 0,
            '1st' => 0,
            'una' => 0,
            'unang' => 0,
            'second' => 1,
            '2nd' => 1,
            'pangalawa' => 1,
            'ikalawa' => 1,
            'third' => 2,
            '3rd' => 2,
            'pangatlo' => 2,
            'ikatlo' => 2,
            'fourth' => 3,
            '4th' => 3,
            'pang-apat' => 3,
            'fifth' => 4,
            '5th' => 4,
            'panglima' => 4,
        ];

        foreach ($ordinals as $word => $index) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/', $message) === 1) {
                return $index;
            }
        }

        return null;
    }
}
