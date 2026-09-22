<?php

namespace App\Services;

/**
 * Classifies chatbot requests locally before any message can reach an AI provider.
 * The rules are intent-based rather than a list of exact question sentences.
 */
class ChatbotIntentRouter
{
    /**
     * Detect what the client is asking independently of any document reference.
     *
     * @return 'workflow_explanation'|'yes_no_comparison'|'status_lookup'|'document_count'|'general_knowledge'
     */
    public function detectQuestionIntent(string $message): string
    {
        $normalized = $this->normalize($message);

        if ($this->isWorkflowExplanationQuestion($normalized)) {
            return 'workflow_explanation';
        }

        if ($this->isYesNoStatusComparison($normalized)) {
            return 'yes_no_comparison';
        }

        if ($this->isDocumentCountInquiry($normalized)) {
            return 'document_count';
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

        if (($index = $this->documentSelectionIndex($normalized)) !== null) {
            return ['type' => 'selection', 'index' => $index];
        }

        if (preg_match(
            '/\b(?:doc|trk|tracking|document|request)\s*[-#:]?\s*\d[\p{L}\p{N}-]*\b/i',
            $message,
        ) === 1 || $this->hasIncompleteLaoReference($normalized)) {
            return ['type' => 'invalid'];
        }

        if ($this->isLatestDocumentInquiry($normalized)) {
            return ['type' => 'latest'];
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

    /**
     * @return array{intent: string, lao_numbers?: list<string>, selection_index?: int}
     */
    public function classify(
        string $message,
        bool $hasPrivateContext = false,
        bool $hasDocumentChoices = false,
        bool $hasPrivateDocumentContext = false,
        bool $hasPrivateMessageContext = false,
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

        if ($this->isAcknowledgment($normalized)) {
            return ['intent' => 'acknowledgment', 'language' => $this->responseLanguage($normalized)];
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

        if ($this->isUnsupportedRequest($message, $normalized)) {
            return ['intent' => 'unsupported'];
        }

        if ($documentReference['type'] === 'lao_numbers') {
            $laoNumbers = $documentReference['numbers'];

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

        if ($questionIntent === 'workflow_explanation') {
            return [
                'intent' => 'workflow_explanation',
                'language' => $this->responseLanguage($normalized),
            ];
        }

        if ($this->isGeneralResubmissionQuestion($normalized) || $this->isGeneralAcceptanceQuestion($normalized)) {
            return ['intent' => 'general_knowledge'];
        }

        if ($this->isDocumentCountInquiry($normalized)) {
            return [
                'intent' => 'document_count',
                'status' => $this->requestedStatus($normalized),
                'yes_no' => $this->isYesNoCountQuestion($normalized),
                'language' => $this->responseLanguage($normalized),
            ];
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
            return $this->hasDocumentStatusTerm($normalized)
                ? ['intent' => 'latest_status']
                : ['intent' => 'ambiguous_document'];
        }

        if ($documentReference['type'] === 'ambiguous') {
            return ['intent' => 'ambiguous_document'];
        }

        if ($hasPrivateContext && $this->isContextDependentFollowUp($normalized)) {
            return ['intent' => 'ambiguous_document'];
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
            '/\b(?:latest|last|most recent|newest|recent|recently submitted|just submitted|pinakabag(?:o|ong)|pinakahuli(?:ng)?|huli(?:ng)?|pinaka latest|kamakailan|kaka submit|kaka upload)\b/',
            $message,
        ) === 1;

        $refersToSubmittedRecord = $this->hasDocumentTerm($message)
            || $this->hasPersonalReference($message)
            || preg_match('/\b(?:submitted|uploaded|sent|requested|submit|isumite|isinumite|naisumite|sinumite|ipinasa|naipasa|na upload)\b/', $message) === 1;

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

        if ($this->isGeneralProcessQuestion($message)) {
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

        if ($hasRecordQuestion && preg_match('/\b(?:it|this|that|there|they|them|their|iyan|iyon|yun|doon|dun|sya|siya|nya)\b/', $message) === 1) {
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
        return preg_match('/\b(?:my|mine|our|ours|ko|akin|amin|namin|natin|sa akin|sa amin|akin ko)\b/', $message) === 1;
    }

    private function isAcknowledgment(string $message): bool
    {
        return preg_match(
            '/^(?:thanks|thank you|thank you so much|thx|salamat|maraming salamat|sige|okay|ok|noted|got it|understood|gets ko|opo|oo|cge)(?:\s+(?:na|po|naman|ha|thanks|salamat)){0,2}[.! ]*$/',
            $message,
        ) === 1;
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

    private function hasContextualDocumentReference(string $message): bool
    {
        return preg_match('/\b(?:it|this|that|its|sya|siya|nya|iyan|iyon|yun|yung|that one|this one)\b/', $message) === 1
            || ($this->hasPossessiveReference($message) && $this->hasDocumentTerm($message));
    }

    private function responseLanguage(string $message): string
    {
        return preg_match('/\b(?:salamat|sige|opo|oo|po|ko|ba|ano|paano|pano|mensahe|hindi|nakatanggap|dokumentong?)\b/', $message) === 1
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
            || preg_match('/\b(?:it|this|that|there|they|them|their|those|its|iyan|iyon|yun|doon|dun|status|state|details?|destination|recipient|sent|sent date|rejection reason|kalagayan|katayuan)\b/', $message) === 1
            || preg_match('/\b(?:what is next|what happens next|ano ang susunod|anong susunod|ano next)\b/', $message) === 1;
    }

    private function isUnsupportedRequest(string $original, string $normalized): bool
    {
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
            '/\b(?:any|an|a|what|give me|provide|send me)\b.*\bupdate\b|\bupdate\b.*\b(?:on|about|for|sa|tungkol)\b/',
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
            '/\b(?:my|mine|our|ours|i|ko|akin|amin|namin|natin|sa akin|sa amin|akin ko)\b/',
            $message,
        ) === 1;
    }

    private function hasDocumentStatusTerm(string $message): bool
    {
        return preg_match(
            '/\b(?:status(?:es)?|state|kalagayan|katayuan|estado|update|progress|kumusta|kamusta|ano na|anong balita|any news|how is|how are|what happened|pending|in progress|outgoing|completed|returned|rejected|archived|approved|approval|accepted|na approve|naaprubahan|natanggap|na reject|nareject|na forward|naforward|naipadala|naipasa|pinoproseso|inaasikaso|nasa proseso|nakumpleto|natapos|naibalik)\b/',
            $message,
        ) === 1;
    }

    private function documentSelectionIndex(string $message): ?int
    {
        if (preg_match('/\b(?:document|doc|number|no)\s+(\d{1,2})\b/', $message, $matches) === 1) {
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
