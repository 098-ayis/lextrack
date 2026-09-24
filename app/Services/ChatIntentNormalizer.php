<?php

namespace App\Services;

/**
 * Produces a local, validated interpretation of a chatbot message.
 *
 * This class deliberately does not call an AI provider or query the database.
 * It supplies a structured plan for the controller, which remains responsible
 * for invoking owner-scoped Laravel services.
 */
class ChatIntentNormalizer
{
    public function __construct(
        private readonly ChatbotIntentRouter $router,
    ) {
    }

    /**
     * @param array{
     *     hasPrivateDocumentContext?: bool,
     *     hasPrivateRequestContext?: bool,
     *     hasPrivateMessageContext?: bool,
     *     hasDocumentChoices?: bool,
     *     hasRequestChoices?: bool
     * } $context
     *
     * @return array{
     *     normalized: string,
     *     domain: string,
     *     intents: list<array{
     *         name: string,
     *         domain: string,
     *         parameters: array<string, mixed>,
     *         reference: array<string, mixed>,
     *         clarification_required: bool,
     *         language: string,
     *         clause: string
     *     }>,
     *     parameters: array<string, mixed>,
     *     reference: array<string, mixed>,
     *     response_language: 'english'|'filipino'|'taglish',
     *     clarification_required: bool,
     *     clarification_question: ?string
     * }
     */
    public function interpret(string $message, array $context = []): array
    {
        $normalized = $this->router->normalize($message);
        $language = $this->responseLanguage($normalized);

        if ($normalized === '') {
            return $this->clarificationResult($normalized, $language, 'Please ask a LexTrack question.');
        }

        $clauses = $this->segment($message);
        $aggregate = $this->isAggregateQuestion($normalized);
        $routerContext = [
            'hasPrivateDocumentContext' => (bool) ($context['hasPrivateDocumentContext'] ?? false) && ! $aggregate,
            'hasPrivateRequestContext' => (bool) ($context['hasPrivateRequestContext'] ?? false) && ! $aggregate,
            'hasPrivateMessageContext' => (bool) ($context['hasPrivateMessageContext'] ?? false),
            'hasDocumentChoices' => (bool) ($context['hasDocumentChoices'] ?? false),
            'hasRequestChoices' => (bool) ($context['hasRequestChoices'] ?? false),
        ];

        $classifications = array_map(
            fn (string $clause): array => $this->router->classify(
                $clause,
                hasPrivateDocumentContext: $routerContext['hasPrivateDocumentContext'],
                hasPrivateMessageContext: $routerContext['hasPrivateMessageContext'],
                hasDocumentChoices: $routerContext['hasDocumentChoices'],
                hasRequestChoices: $routerContext['hasRequestChoices'],
                hasPrivateRequestContext: $routerContext['hasPrivateRequestContext'],
            ),
            $clauses,
        );

        $intentRows = [];
        foreach ($clauses as $index => $clause) {
            $classification = $classifications[$index] ?? ['intent' => 'general_knowledge'];
            $classification = $this->inheritDomainForClause(
                $classification,
                $this->router->normalize($clause),
                $normalized,
                $language,
            );
            $intentRows[] = $this->structuredIntent(
                $clause,
                $classification,
                $language,
                $aggregate,
                $normalized,
            );
        }

        // A second clause such as “ilan ba doon ang Accepted” can omit the
        // word “request”. The request domain established by the first clause
        // supplies the safe scope; the status remains a validated parameter.
        if ($this->hasRequestDomain($normalized) && $this->hasMultipleCountClauses($clauses)) {
            $intentRows = $this->aggregateCountIntents($clauses, 'document_requests', $language);
        } elseif ($this->hasDocumentDomain($normalized) && $this->hasMultipleCountClauses($clauses)) {
            $intentRows = $this->aggregateCountIntents($clauses, 'documents', $language);
        }

        $intentRows = array_values(array_filter(
            $intentRows,
            static fn (array $intent): bool => $intent['name'] !== 'acknowledgment'
                || count($intentRows) === 1,
        ));

        if ($intentRows === []) {
            return $this->clarificationResult(
                $normalized,
                $language,
                'I could not determine what you want to check. Please ask about one LexTrack topic at a time.',
            );
        }

        $clarification = collect($intentRows)->first(
            static fn (array $intent): bool => $intent['clarification_required'] === true,
        );
        $domain = $this->commonDomain($intentRows);
        $reference = $this->commonReference($intentRows);
        $parameters = count($intentRows) === 1
            ? $intentRows[0]['parameters']
            : ['aggregate' => $aggregate, 'intent_count' => count($intentRows)];

        return [
            'normalized' => $normalized,
            'domain' => $domain,
            'intents' => $intentRows,
            'parameters' => $parameters,
            'reference' => $reference,
            'response_language' => $language,
            'clarification_required' => $clarification !== null,
            'clarification_question' => $clarification['clarification_question'] ?? null,
        ];
    }

    /** @return list<string> */
    private function segment(string $message): array
    {
        $parts = preg_split(
            '/\s+(?:and|also|plus|at|pati|tsaka|kasama(?:\s+rin)?|then|tapos)\s+|[;?]+/iu',
            trim($message),
        ) ?: [];

        $parts = array_values(array_filter(
            array_map(static fn (string $part): string => trim($part, " \t\n\r\0\x0B?!"), $parts),
            static fn (string $part): bool => $part !== '',
        ));

        return $parts !== [] ? $parts : [trim($message)];
    }

    /** @param array<string, mixed> $classification */
    private function structuredIntent(
        string $clause,
        array $classification,
        string $language,
        bool $aggregate,
        string $wholeMessage,
    ): array {
        $sourceIntent = (string) ($classification['intent'] ?? 'general_knowledge');
        $name = $sourceIntent === 'document_name_lookup'
            ? match ($classification['document_action'] ?? null) {
                'get_document_updates' => 'get_document_updates',
                'get_document_type' => 'get_document_type',
                default => 'get_document_status',
            }
            : $sourceIntent;
        $domain = $this->domainFor($name);
        $normalizedClause = $this->router->normalize($clause);
        $parameters = $this->parametersFor($normalizedClause, $clause, $classification, $aggregate, $wholeMessage);
        $reference = $this->referenceFor($clause, $classification, $domain, $aggregate);
        $clarificationQuestion = $this->clarificationQuestion($name, $language);

        return [
            'name' => $name,
            'domain' => $domain,
            'parameters' => $parameters,
            'reference' => $reference,
            'clarification_required' => $clarificationQuestion !== null,
            'clarification_question' => $clarificationQuestion,
            'language' => $language,
            'clause' => $clause,
        ];
    }

    /** @param array<string, mixed> $classification */
    private function parametersFor(
        string $message,
        string $originalMessage,
        array $classification,
        bool $aggregate,
        string $wholeMessage,
    ): array {
        $status = $this->statusFilter($message);
        $requestId = $this->router->requestId($message);
        // Extract identifiers from the original clause. Normalization may
        // replace punctuation such as the hyphens in an LAO number with
        // spaces, while the validated identifier must remain exact.
        $laoNumbers = $this->extractLaoNumbers($originalMessage);
        $selectionIndex = $this->hasLatestCue($message)
            ? null
            : $this->router->documentSelectionIndex($message);
        $name = (string) ($classification['intent'] ?? 'general_knowledge');

        if ($name === 'payment_inquiry') {
            return [
                'payment' => true,
                'aggregate' => $aggregate || $this->hasAggregateCue($wholeMessage),
            ];
        }

        return array_filter([
            'status' => $status ?? ($classification['status'] ?? null),
            'latest' => $this->hasLatestCue($message),
            'count' => $this->hasCountCue($message) || $name === 'request_count' || $name === 'document_count',
            'yes_no' => $this->hasYesNoCue($message),
            'request_id' => $requestId,
            'lao_numbers' => $laoNumbers !== [] ? $laoNumbers : null,
            'selection_index' => $selectionIndex,
            'document_name' => $classification['document_name'] ?? null,
            'search_text' => $classification['document_name'] ?? null,
            'reference_field' => $classification['reference_field'] ?? null,
            'topic' => $classification['topic'] ?? null,
            'kind' => $classification['kind'] ?? null,
            'aggregate' => $aggregate || $this->hasAggregateCue($wholeMessage),
        ], static fn (mixed $value): bool => $value !== null && $value !== false);
    }

    /** @param array<string, mixed> $classification */
    private function referenceFor(
        string $clause,
        array $classification,
        string $domain,
        bool $aggregate,
    ): array {
        if (($classification['intent'] ?? null) === 'payment_inquiry') {
            return ['type' => 'none'];
        }

        if ($aggregate && in_array($classification['intent'] ?? null, ['request_count', 'document_count'], true)) {
            return ['type' => 'aggregate', 'record' => $domain === 'document_requests' ? 'request' : 'document'];
        }

        $requestId = $this->router->requestId($clause);
        if ($requestId !== null) {
            return ['type' => 'explicit', 'record' => 'request', 'validated' => true];
        }

        $laoNumbers = $this->extractLaoNumbers($clause);
        if ($laoNumbers !== []) {
            return ['type' => 'explicit', 'record' => 'document', 'validated' => true];
        }

        if (in_array($classification['intent'] ?? null, ['document_name_lookup', 'rejection_reason_lookup'], true)
            && filled($classification['document_name'] ?? null)) {
            $reference = [
                'type' => $classification['reference_type'] ?? 'search_text',
                'record' => 'document',
                'value' => (string) $classification['document_name'],
                'validated' => false,
            ];

            if (filled($classification['reference_field'] ?? null)) {
                $reference['field'] = (string) $classification['reference_field'];
            }

            return $reference;
        }

        return match ($classification['intent'] ?? null) {
            'latest_status', 'latest_submission_date', 'latest_request' => ['type' => 'latest', 'record' => $domain === 'document_requests' ? 'request' : 'document'],
            'document_context_status', 'document_context_details', 'document_context_guidance', 'document_context_rejection_reason', 'request_context_details' => ['type' => 'context', 'record' => $domain === 'document_requests' ? 'request' : 'document'],
            'document_status_filter' => [
                'type' => 'status_filter',
                'record' => 'document',
                'status' => $classification['status'] ?? null,
            ],
            'ambiguous_document', 'ambiguous_request', 'invalid_lao' => ['type' => 'ambiguous'],
            'document_selection', 'request_selection' => ['type' => 'selection', 'validated' => true],
            default => ['type' => 'none'],
        };
    }

    private function domainFor(string $intent): string
    {
        if (str_starts_with($intent, 'request_') || $intent === 'latest_request' || $intent === 'ambiguous_request') {
            return 'document_requests';
        }

        if (str_starts_with($intent, 'message_')) {
            return 'messages';
        }

        if (in_array($intent, [
            'document_count',
            'document_status_filter',
            'latest_status',
            'latest_submission_date',
            'lao_lookup',
            'lao_rejection_reason',
            'compare_lao_numbers',
            'compare_latest_documents',
            'document_selection',
            'document_name_lookup',
            'rejection_reason_lookup',
            'document_context_rejection_reason',
            'get_document_status',
            'get_document_updates',
            'get_document_type',
            'document_context_status',
            'document_context_details',
            'document_context_guidance',
            'ambiguous_document',
            'invalid_lao',
        ], true)) {
            return 'documents';
        }

        return 'general_knowledge';
    }

    /** @param array<string, mixed> $classification */
    private function inheritDomainForClause(
        array $classification,
        string $clause,
        string $wholeMessage,
        string $language,
    ): array {
        if (($classification['intent'] ?? 'general_knowledge') !== 'general_knowledge') {
            return $classification;
        }

        if ($this->hasRequestDomain($wholeMessage)
            && ($this->hasCountCue($clause) || $this->statusFilter($clause) !== null)) {
            return [
                'intent' => 'request_count',
                'status' => $this->statusFilter($clause),
                'language' => $language,
            ];
        }

        if ($this->hasMessageDomain($wholeMessage) && $this->hasMessageMetadataCue($clause)) {
            return [
                'intent' => 'message_metadata',
                'kind' => $this->hasUnreadCue($clause)
                    ? 'unread'
                    : ($this->hasCountCue($clause) ? 'count' : 'exists'),
                'language' => $language,
            ];
        }

        if ($this->hasDocumentDomain($wholeMessage)) {
            if ($this->hasCountCue($clause) || $this->statusFilter($clause) !== null) {
                return [
                    'intent' => 'document_count',
                    'status' => $this->statusFilter($clause),
                    'yes_no' => $this->hasYesNoCue($clause),
                    'language' => $language,
                ];
            }

            if ($this->hasActionTypeCue($clause)) {
                return [
                    'intent' => 'document_context_details',
                    'topic' => 'action_type',
                    'language' => $language,
                ];
            }
        }

        if ($this->hasRequestDomain($wholeMessage) && $this->hasRequestDetailCue($clause)) {
            return [
                'intent' => 'request_context_details',
                'topic' => $this->requestTopic($clause),
                'language' => $language,
            ];
        }

        return $classification;
    }

    private function hasMessageDomain(string $message): bool
    {
        return preg_match('/\b(?:message|messages|mensahe|inbox|conversation|usapan)\b/', $message) === 1;
    }

    private function hasMessageMetadataCue(string $message): bool
    {
        return $this->hasCountCue($message)
            || $this->hasUnreadCue($message)
            || preg_match('/\b(?:any|there|may|meron|mayroon|have|do|does|are|is)\b/', $message) === 1;
    }

    private function hasUnreadCue(string $message): bool
    {
        return preg_match('/\b(?:unread|not read|hindi pa nababasa|di pa nababasa)\b/', $message) === 1;
    }

    private function hasActionTypeCue(string $message): bool
    {
        return preg_match('/\b(?:action type|assigned action|assigned to|anong action|anong gawain|ano ang action)\b/', $message) === 1;
    }

    private function hasRequestDetailCue(string $message): bool
    {
        return preg_match('/\b(?:copy|soft copy|original|pickup|pick up|download|available|availability|schedule|makukuha|makuha|kukunin|type)\b/', $message) === 1;
    }

    private function requestTopic(string $message): string
    {
        if (preg_match('/\b(?:copy|soft copy|original|type)\b/', $message) === 1) {
            return 'copy_type';
        }

        if (preg_match('/\b(?:pickup|pick up|schedule|makukuha|makuha|kukunin)\b/', $message) === 1) {
            return 'pickup';
        }

        if (preg_match('/\b(?:download|available|availability)\b/', $message) === 1) {
            return 'download';
        }

        return 'summary';
    }

    /** @param list<array<string, mixed>> $intents */
    private function commonDomain(array $intents): string
    {
        $domains = array_values(array_unique(array_map(
            static fn (array $intent): string => (string) $intent['domain'],
            $intents,
        )));

        return count($domains) === 1 ? $domains[0] : 'general_knowledge';
    }

    /** @param list<array<string, mixed>> $intents */
    private function commonReference(array $intents): array
    {
        $references = array_values(array_unique(array_map(
            static fn (array $intent): string => json_encode($intent['reference']) ?: '{}',
            $intents,
        )));

        return count($references) === 1
            ? (json_decode($references[0], true) ?: ['type' => 'none'])
            : ['type' => 'multiple'];
    }

    /** @param list<string> $clauses */
    private function hasMultipleCountClauses(array $clauses): bool
    {
        return count(array_filter($clauses, fn (string $clause): bool => $this->hasCountCue($this->router->normalize($clause)))) > 1
            || (count($clauses) > 1 && $this->hasCountCue($this->router->normalize($clauses[0])));
    }

    /** @param list<string> $clauses */
    private function aggregateCountIntents(array $clauses, string $domain, string $language): array
    {
        $rows = [];
        foreach ($clauses as $clause) {
            $normalized = $this->router->normalize($clause);
            if (! $this->hasCountCue($normalized) && $this->statusFilter($normalized) === null) {
                continue;
            }

            $name = $domain === 'document_requests' ? 'request_count' : 'document_count';
            $parameters = array_filter([
                'status' => $this->statusFilter($normalized),
                'count' => true,
                'aggregate' => true,
                'yes_no' => $this->hasYesNoCue($normalized),
                'kind' => 'count',
            ], static fn (mixed $value): bool => $value !== null && $value !== false);

            $rows[] = [
                'name' => $name,
                'domain' => $domain,
                'parameters' => $parameters,
                'reference' => ['type' => 'aggregate', 'record' => $domain === 'document_requests' ? 'request' : 'document'],
                'clarification_required' => false,
                'clarification_question' => null,
                'language' => $language,
                'clause' => $clause,
            ];
        }

        return $rows;
    }

    private function isAggregateQuestion(string $message): bool
    {
        return $this->hasAggregateCue($message)
            || ($this->hasCountCue($message) && ! $this->hasExplicitRecordCue($message));
    }

    private function hasAggregateCue(string $message): bool
    {
        return preg_match('/\b(?:how many|count|number of|do i have|have i|ilan|ilang|dami|karami|lahat|all|every|authorized|records?)\b/', $message) === 1;
    }

    private function hasExplicitRecordCue(string $message): bool
    {
        return preg_match('/\b(?:lao|request\s*(?:number|no|#|id)?\s*\d+|document\s*\d+|latest|selected|that|this|it|yan|jan|diyan|niyan|siya)\b/i', $message) === 1;
    }

    private function hasRequestDomain(string $message): bool
    {
        return preg_match('/\b(?:request|requests|hiling|application|hinihiling|humiling)\b/', $message) === 1;
    }

    private function hasDocumentDomain(string $message): bool
    {
        return preg_match('/\b(?:document|documents|doc|docs|submission|submissions|dokumento|papel|file|files)\b/', $message) === 1;
    }

    private function hasLatestCue(string $message): bool
    {
        return preg_match('/\b(?:latest|last|most recent|newest|recent|pinakabago|pinakahuli|pinakalatest|kamakailan|kaka submit|kaka upload)\b/', $message) === 1;
    }

    private function hasCountCue(string $message): bool
    {
        return preg_match('/\b(?:how many|count|number of|do i have|have i|ilan|ilang|dami|karami|mayroon ba|meron ba|may ba)\b/', $message) === 1;
    }

    private function hasYesNoCue(string $message): bool
    {
        return preg_match('/^(?:is|are|do|does|did|has|have|mayroon ba|meron ba|may ba|may)\b/', $message) === 1;
    }

    private function statusFilter(string $message): ?string
    {
        foreach ([
            'in_progress' => '/\b(?:in progress|inprogress|processing|pinoproseso|nasa proseso)\b/',
            'pending' => '/\b(?:pending|awaiting|waiting|nakabinbin|hinihintay)\b/',
            'outgoing' => '/\b(?:outgoing|sent out|naipadala|na forward)\b/',
            'completed' => '/\b(?:completed|complete|finished|natapos|nakumpleto)\b/',
            'returned' => '/\b(?:returned|ibinalik|naibalik)\b/',
            'rejected' => '/\b(?:rejected|reject|tinanggihan|na reject|nareject)\b/',
            'archived' => '/\b(?:archived|archive|naka archive)\b/',
            'accepted' => '/\b(?:accepted|approved|approve|tinanggap|na approve|naaprubahan)\b/',
        ] as $status => $pattern) {
            if (preg_match($pattern, $message) === 1) {
                return $status;
            }
        }

        return null;
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

    private function responseLanguage(string $message): string
    {
        $tagalog = preg_match('/\\b(?:ano|ang|ng|ba|ko|mo|sa|akin|ito|iyon|yan|jan|diyan|paano|pano|kailan|ilan|ilang|may|mayroon|meron|doon|dun|hindi|opo|oo|kamusta|kumusta|mabuti|na|mensahe|dokumento|hiling|tungkol|ibig sabihin|paki|natin|namin|bayad|bayaran|babayaran|magbayad|magkano|singil|gastos)\\b/', $message) === 1;
        $english = preg_match('/\\b(?:what|how|when|where|why|which|many|request|requests|document|documents|status|accepted|pending|message|messages|latest|count|have|do|does|is|are|the|my|about|copy|pickup|download|pay|paid|payment|payments|fee|fees|processing|charge|charges|cost|costs|price|prices|amount|how much)\\b/', $message) === 1;


        return $tagalog && $english ? 'taglish' : ($tagalog ? 'filipino' : 'english');
    }

    private function clarificationQuestion(string $intent, string $language): ?string
    {
        if (! in_array($intent, ['ambiguous_document', 'ambiguous_request', 'invalid_lao'], true)) {
            return null;
        }

        return $language === 'filipino'
            ? 'Aling authorized record ang gusto mong tingnan? Pumili ng document o request mula sa listahan.'
            : 'Which authorized document or request do you want to check? Please choose one from the list.';
    }

    private function clarificationResult(string $normalized, string $language, string $question): array
    {
        return [
            'normalized' => $normalized,
            'domain' => 'general_knowledge',
            'intents' => [[
                'name' => 'clarification',
                'domain' => 'general_knowledge',
                'parameters' => [],
                'reference' => ['type' => 'ambiguous'],
                'clarification_required' => true,
                'clarification_question' => $question,
                'language' => $language,
                'clause' => $normalized,
            ]],
            'parameters' => [],
            'reference' => ['type' => 'ambiguous'],
            'response_language' => $language,
            'clarification_required' => true,
            'clarification_question' => $question,
        ];
    }
}
