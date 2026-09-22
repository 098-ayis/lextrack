<?php

namespace App\Services;

use App\Models\DocumentRequest;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;

class ClientDocumentRequestLookupService
{
    private const NO_AUTHORIZED_MATCH = 'I couldn’t find an authorized document request.';

    /**
     * Resolve the newest request belonging to the authenticated client.
     * Request IDs are never used without the owner scope in Laravel.
     *
     * @return array{reply: string, request_id: ?int, status: ?string}
     */
    public function latestResult(User $user, string $language = 'english'): array
    {
        $request = $this->ownedRequests($user)
            ->latest('date_of_request')
            ->latest('request_id')
            ->first($this->requestFields());

        return $request
            ? $this->resultFor($user, $request, $language)
            : $this->result('You have no document requests yet.');
    }

    /**
     * @return array{reply: string, request_id: ?int, status: ?string}
     */
    public function byIdResult(User $user, int $requestId, string $language = 'english'): array
    {
        $request = $this->ownedRequests($user)
            ->where('request_id', $requestId)
            ->first($this->requestFields());

        return $request
            ? $this->resultFor($user, $request, $language)
            : $this->result(self::NO_AUTHORIZED_MATCH);
    }

    /**
     * Return an explicitly requested aspect of an already selected request.
     * The owner query is repeated for every follow-up.
     *
     * @return array{reply: string, request_id: ?int, status: ?string}
     */
    public function detailsByIdResult(User $user, int $requestId, string $topic = 'summary', string $language = 'english'): array
    {
        $request = $this->ownedRequests($user)
            ->where('request_id', $requestId)
            ->first($this->requestFields());

        if (! $request) {
            return $this->result(self::NO_AUTHORIZED_MATCH);
        }

        $status = (string) $request->status;
        $requestLabel = 'Request #' . $request->request_id;

        $reply = match ($topic) {
            'copy_type' => $this->copyTypeReply($requestLabel, $request, $language),
            'pickup' => $this->pickupReply($requestLabel, $request, $language),
            'download' => $this->downloadReply($user, $requestLabel, $request, $language),
            default => $this->formatRequest($user, $request, $language),
        };

        return $this->result($reply, (int) $request->request_id, $status);
    }

    /** @return array<string, int> */
    public function countsByStatus(User $user): array
    {
        $statuses = ['pending', 'accepted', 'rejected'];
        $counts = $this->ownedRequests($user)
            ->select('status')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return collect($statuses)
            ->mapWithKeys(static fn (string $status): array => [$status => (int) ($counts[$status] ?? 0)])
            ->all();
    }

    public function countByStatus(User $user, string $status): int
    {
        return $this->countsByStatus($user)[$status] ?? 0;
    }

    public function statusLabelForChat(string $status): string
    {
        return match ($status) {
            'pending' => 'pending document requests',
            'accepted' => 'accepted document requests',
            'rejected' => 'rejected document requests',
            default => 'document requests',
        };
    }

    /** @return list<array{request_id: int, status: string, copy_type: ?string, requested_at: string}> */
    public function authorizedChoices(User $user, int $limit = 10): array
    {
        return $this->ownedRequests($user)
            ->latest('date_of_request')
            ->latest('request_id')
            ->limit(max(1, min($limit, 10)))
            ->get(['request_id', 'status', 'copy_type', 'date_of_request'])
            ->map(static fn (DocumentRequest $request): array => [
                'request_id' => (int) $request->request_id,
                'status' => (string) $request->status,
                'copy_type' => filled($request->copy_type) ? (string) $request->copy_type : null,
                'requested_at' => $request->date_of_request?->format('F j, Y') ?? 'date unavailable',
            ])
            ->all();
    }

    private function ownedRequests(User $user)
    {
        return DocumentRequest::query()->where('user_id', $user->getKey());
    }

    /** @return list<string> */
    private function requestFields(): array
    {
        return [
            'request_id',
            'document_id',
            'status',
            'copy_type',
            'pickup_at',
            'date_of_request',
            'date_processed',
            'rejection_reason',
        ];
    }

    private function formatRequest(User $user, DocumentRequest $request, string $language = 'english'): string
    {
        $label = 'Request #' . $request->request_id;
        $status = $this->statusLabel((string) $request->status);

        if ($request->status === 'rejected') {
            return $language === 'filipino'
                ? "{$label} ay Rejected. Tingnan sa Documents ang authorized na rejection reason."
                : "{$label} is Rejected. Please review the authorized rejection reason in Documents.";
        }

        $reply = $language === 'filipino'
            ? "{$label} ay {$status}."
            : "{$label} is {$status}.";

        if ($request->copy_type === 'soft_copy') {
            $reply .= $language === 'filipino' ? ' Soft copy ang hiniling.' : ' Soft copy requested.';

            if ($request->status === 'accepted') {
                $reply .= ' ' . $this->downloadSentence($user, $request, $language);
            }
        } elseif ($request->copy_type === 'original') {
            $reply .= ($language === 'filipino' ? ' Original copy ang hiniling. ' : ' Original copy requested. ')
                . $this->pickupSentence($request, $language);
        }

        return $reply;
    }

    private function copyTypeReply(string $label, DocumentRequest $request, string $language = 'english'): string
    {
        $copyType = match ($request->copy_type) {
            'soft_copy' => $language === 'filipino' ? 'Soft copy (digital)' : 'Soft copy (digital)',
            'original' => $language === 'filipino' ? 'Original copy (para sa pickup)' : 'Original copy (for pickup)',
            default => $language === 'filipino' ? 'Walang recorded na copy type' : 'No copy type has been recorded',
        };

        return $language === 'filipino'
            ? "{$label} ay {$this->statusLabel((string) $request->status)}. Copy type: {$copyType}."
            : "{$label} is {$this->statusLabel((string) $request->status)}. Copy type: {$copyType}.";
    }

    private function pickupReply(string $label, DocumentRequest $request, string $language = 'english'): string
    {
        if ($request->copy_type !== 'original') {
            return $language === 'filipino'
                ? "{$label} ay hindi Original copy request. Ang pickup schedule ay para lamang sa original copies."
                : "{$label} is not an Original copy request. Pickup scheduling applies only to original copies.";
        }

        return ($language === 'filipino'
            ? "{$label} ay {$this->statusLabel((string) $request->status)}. "
            : "{$label} is {$this->statusLabel((string) $request->status)}. ")
            . $this->pickupSentence($request, $language);
    }

    private function downloadReply(User $user, string $label, DocumentRequest $request, string $language = 'english'): string
    {
        if ($request->copy_type !== 'soft_copy') {
            return $language === 'filipino'
                ? "{$label} ay hindi Soft copy request. Ang download availability ay para lamang sa accepted soft copies."
                : "{$label} is not a Soft copy request. Download availability applies only to accepted soft copies.";
        }

        if ($request->status !== 'accepted') {
            return $language === 'filipino'
                ? "{$label} ay {$this->statusLabel((string) $request->status)}. Hindi available ang soft-copy download hangga’t hindi accepted ang request."
                : "{$label} is {$this->statusLabel((string) $request->status)}. A soft-copy download is not available until the request is accepted.";
        }

        return ($language === 'filipino' ? "{$label} ay Accepted. " : "{$label} is Accepted. ")
            . $this->downloadSentence($user, $request, $language);
    }

    private function pickupSentence(DocumentRequest $request, string $language = 'english'): string
    {
        return $request->pickup_at
            ? ($language === 'filipino' ? 'Naka-schedule ang pickup sa ' : 'Pickup is scheduled for ')
                . $request->pickup_at->format('F j, Y g:i A') . '.'
            : ($language === 'filipino' ? 'Walang recorded na pickup schedule.' : 'No pickup schedule has been recorded yet.');
    }

    private function downloadSentence(User $user, DocumentRequest $request, string $language = 'english'): string
    {
        $documentOwned = $request->document_id !== null
            && Document::query()
                ->where('document_id', $request->document_id)
                ->where('user_id', $user->getKey())
                ->exists();
        $available = $documentOwned
            && DocumentVersion::query()
                ->where('document_id', $request->document_id)
                ->whereNotNull('file_path')
                ->exists();

        return $available
            ? ($language === 'filipino'
                ? 'Available ang soft-copy download sa Documents.'
                : 'The soft-copy download is available in Documents.')
            : ($language === 'filipino'
                ? 'Hindi pa available ang soft-copy download sa Documents.'
                : 'The soft-copy download is not available yet in Documents.');
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Pending',
            'accepted' => 'Accepted',
            'rejected' => 'Rejected',
            default => 'Unavailable',
        };
    }

    /** @return array{reply: string, request_id: ?int, status: ?string} */
    private function result(string $reply, ?int $requestId = null, ?string $status = null): array
    {
        return [
            'reply' => $reply,
            'request_id' => $requestId,
            'status' => $status,
        ];
    }

    /** @return array{reply: string, request_id: ?int, status: ?string} */
    private function resultFor(User $user, DocumentRequest $request, string $language = 'english'): array
    {
        return $this->result(
            $this->formatRequest($user, $request, $language),
            (int) $request->request_id,
            (string) $request->status,
        );
    }
}
