<?php

namespace App\Services;

use App\Models\ActionType;
use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\DocumentRequest;

class DocumentStatusTimeline
{
    /**
     * Build the public-facing status history from the document's activity logs.
     *
     * @return array<int, array{status: string, title: string, description: string, time: string, date: string, color: string}>
     */
    public function build(Document $document, bool $includeClientRevisions = false): array
    {
        $timeline = [
            $this->timelineEntry(
                'pending',
                'Pending',
                'Your document was submitted and is waiting for review.',
                $document->created_at,
            ),
        ];

        $events = $document->activityLogs->collect();

        if ($includeClientRevisions) {
            $revisionMessages = $document->conversation?->messages
                ->filter(fn ($message): bool => (int) $message->sender_id === (int) $document->user_id
                    && (str_starts_with($message->body, 'A revised document was uploaded as version ')
                        || str_starts_with($message->body, 'Revised documents were uploaded as versions ')))
                ->map(fn ($message): object => (object) [
                    'action_type' => 'client_revision_submitted',
                    'action_details' => $message->body,
                    'created_at' => $message->created_at,
                ]) ?? collect();

            $events = $events->concat($revisionMessages)->sortBy('created_at')->values();
        }

        foreach ($events as $log) {
            $entry = $this->timelineEntryFromActivity($log, $document);

            if ($entry === null) {
                continue;
            }

            $isAcceptedEvent = str_contains(
                strtolower(trim((string) $log->action_type)),
                'accepted',
            );

            $lastEntry = $timeline[array_key_last($timeline)] ?? null;

            if (
                $lastEntry
                && $log->action_type !== 'client_revision_submitted'
                && $lastEntry['status'] === $entry['status']
                && $lastEntry['title'] === $entry['title']
            ) {
                $timeline[array_key_last($timeline)] = $entry;
            } else {
                $timeline[] = $entry;
            }

            if ($isAcceptedEvent && filled($document->action_type)) {
                $actionType = trim((string) $document->action_type);

                $timeline[] = $this->timelineEntry(
                    'action',
                    'Action Taken: ' . $actionType,
                    'The assigned action is ' . $actionType . '.',
                    $log->created_at ?? $document->updated_at ?? $document->created_at,
                    $this->actionTypeColor($actionType),
                );
            }
        }

        $lastEntry = $timeline[array_key_last($timeline)] ?? null;

        if (
            $document->status !== 'pending'
            && ($lastEntry === null || $lastEntry['status'] !== $document->status)
        ) {
            $timeline[] = $this->fallbackStatusEntry($document);
        }

        return array_values(array_filter(
            $timeline,
            fn (array $entry): bool => $entry['title'] !== 'In Progress',
        ));
    }

    /**
     * Build the public-facing history for a document request.
     *
     * Request actions are recorded against the request, rather than only
     * against the document, so pickup schedules and claim events need their
     * own timeline. The request activity is kept separate from the document
     * processing history to avoid showing unrelated document transitions.
     *
     * @return array<int, array{status: string, title: string, description: string, time: string, date: string, color: string}>
     */
    public function buildForRequest(Document $document, DocumentRequest $request): array
    {
        $timeline = [
            $this->timelineEntry(
                'pending',
                'Pending',
                'Your request was submitted and is waiting for review.',
                $request->created_at ?? $request->date_of_request ?? $document->created_at,
            ),
        ];

        $events = ActivityLog::query()
            ->where(function ($query) use ($request): void {
                $query
                    ->where('request_id', $request->request_id)
                    ->orWhere(function ($subjectQuery) use ($request): void {
                        $subjectQuery
                            ->where('subject_type', DocumentRequest::class)
                            ->where('subject_id', $request->request_id);
                    });
            })
            ->oldest('created_at')
            ->oldest('log_id')
            ->get();

        foreach ($events as $log) {
            $entry = $this->requestTimelineEntryFromActivity($log, $request);

            if ($entry === null) {
                continue;
            }

            $lastEntry = $timeline[array_key_last($timeline)] ?? null;

            if (
                $lastEntry
                && $lastEntry['status'] === $entry['status']
                && $lastEntry['title'] === $entry['title']
            ) {
                $timeline[array_key_last($timeline)] = $entry;

                continue;
            }

            $timeline[] = $entry;
        }

        $hasPickupEvent = collect($timeline)
            ->contains(fn (array $entry): bool => in_array($entry['title'], ['Pickup Scheduled', 'Pickup Rescheduled'], true));

        if ($request->pickup_at && ! $hasPickupEvent) {
            $timeline[] = $this->timelineEntry(
                'ready_for_pickup',
                'Pickup Scheduled',
                $this->pickupDescription('Your document is scheduled for pickup', $request->pickup_at),
                $request->pickup_at,
            );
        }

        $hasClaimEvent = collect($timeline)
            ->contains(fn (array $entry): bool => $entry['title'] === 'Document Claimed');

        if ($request->claimed_at && ! $hasClaimEvent) {
            $timeline[] = $this->timelineEntry(
                'completed',
                'Document Claimed',
                'Your document was claimed.',
                $request->claimed_at,
            );
        }

        $lastEntry = $timeline[array_key_last($timeline)] ?? null;
        $requestStatus = (string) $request->status;

        if ($requestStatus !== 'pending' && ($lastEntry === null || $lastEntry['status'] !== $requestStatus)) {
            $timeline[] = $this->requestFallbackEntry($request);
        }

        return array_map(function (array $entry): array {
            $entry['color'] = $this->requestStatusColor($entry['status']);

            return $entry;
        }, $timeline);
    }

    private function requestTimelineEntryFromActivity(mixed $log, DocumentRequest $request): ?array
    {
        $action = strtolower(trim((string) $log->action_type));
        $normalizedAction = str_replace([' ', '-'], '_', $action);
        $details = trim((string) ($log->action_details ?? ''));
        $timestamp = $log->created_at ?? $request->updated_at ?? $request->created_at;

        return match ($normalizedAction) {
            'request_submitted' => null,
            'request_accepted' => $this->timelineEntry(
                'for_release',
                'Accepted',
                'Your request was accepted and is being processed.',
                $timestamp,
            ),
            'pickup_scheduled' => $this->timelineEntry(
                'ready_for_pickup',
                'Pickup Scheduled',
                $this->pickupDescription(
                    'Your document is scheduled for pickup',
                    $this->pickupValueForLog($log, $request, $details),
                    $details,
                ),
                $timestamp,
            ),
            'pickup_rescheduled' => $this->timelineEntry(
                'ready_for_pickup',
                'Pickup Rescheduled',
                $this->pickupDescription(
                    'Your pickup was rescheduled for',
                    $log->new_value,
                    $details,
                ),
                $timestamp,
            ),
            'pickup_no_show' => $this->timelineEntry(
                'ready_for_pickup',
                'Pickup Missed',
                'The scheduled pickup was marked as a no-show.',
                $timestamp,
            ),
            'request_claimed' => $this->timelineEntry(
                'completed',
                'Document Claimed',
                'Your document was claimed.',
                $timestamp,
            ),
            'soft_copy_uploaded' => $this->timelineEntry(
                'completed',
                'Document Completed',
                'The requested document was uploaded and is ready for you.',
                $timestamp,
            ),
            'soft_copy_replaced' => $this->timelineEntry(
                'completed',
                'Document Updated',
                'A new version of the requested document was uploaded.',
                $timestamp,
            ),
            'request_rejected' => $this->timelineEntry(
                'rejected',
                'Rejected',
                $this->requestRejectionDescription($request, $details),
                $timestamp,
            ),
            'request_restored' => $this->timelineEntry(
                'pending',
                'Pending',
                'Your request was restored and is waiting for review.',
                $timestamp,
            ),
            default => null,
        };
    }

    private function requestFallbackEntry(DocumentRequest $request): array
    {
        $status = (string) $request->status;
        $timestamp = $request->updated_at ?? $request->created_at;

        return match ($status) {
            'for_release' => $this->timelineEntry(
                'for_release',
                'Accepted',
                'Your request was accepted and is being processed.',
                $timestamp,
            ),
            'ready_for_pickup' => $this->timelineEntry(
                'ready_for_pickup',
                'Ready for Pickup',
                $request->pickup_at
                    ? $this->pickupDescription('Your document is ready for pickup', $request->pickup_at)
                    : 'Your document is ready for pickup.',
                $timestamp,
            ),
            'completed' => $request->claimed_at
                ? $this->timelineEntry(
                    'completed',
                    'Document Claimed',
                    'Your document was claimed.',
                    $request->claimed_at,
                )
                : $this->timelineEntry(
                    'completed',
                    'Document Completed',
                    'Your requested document has been completed.',
                    $timestamp,
                ),
            'rejected' => $this->timelineEntry(
                'rejected',
                'Rejected',
                $this->requestRejectionDescription($request),
                $timestamp,
            ),
            default => $this->timelineEntry(
                $status,
                $this->requestStatusLabel($status),
                'The request status was updated to ' . $this->requestStatusLabel($status) . '.',
                $timestamp,
            ),
        };
    }

    private function pickupValueForLog(mixed $log, DocumentRequest $request, string $details): mixed
    {
        if ($log->new_value && $this->isDateTimeValue($log->new_value)) {
            return $log->new_value;
        }

        if (preg_match('/pickup(?: time)?\s+for\s+(.+?)\.$/i', $details, $matches) === 1) {
            return trim($matches[1]);
        }

        return $request->pickup_at;
    }

    private function pickupDescription(string $prefix, mixed $pickupAt, string $details = ''): string
    {
        if ($pickupAt) {
            try {
                $pickup = $pickupAt instanceof \DateTimeInterface
                    ? \Carbon\Carbon::instance($pickupAt)
                    : \Carbon\Carbon::parse((string) $pickupAt);

                return $prefix . ' ' . $pickup->format('M d, Y \a\t g:i A') . '.';
            } catch (\Throwable) {
                // Fall through to the generic activity description.
            }
        }

        return $details !== '' ? $details : rtrim($prefix, '.') . '.';
    }

    private function isDateTimeValue(mixed $value): bool
    {
        if ($value instanceof \DateTimeInterface) {
            return true;
        }

        if (! is_string($value) || trim($value) === '') {
            return false;
        }

        try {
            \Carbon\Carbon::parse($value);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function requestRejectionDescription(DocumentRequest $request, string $details = ''): string
    {
        $description = 'Your request was rejected.';
        $reason = trim((string) $request->rejection_reason);

        if ($reason !== '') {
            return $description . ' Reason: ' . $reason;
        }

        return $details !== '' ? $details : $description;
    }

    private function requestStatusLabel(string $status): string
    {
        return match ($status) {
            'for_release' => 'Accepted',
            'ready_for_pickup' => 'Ready for Pickup',
            'completed' => 'Completed',
            'rejected' => 'Rejected',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }

    private function requestStatusColor(string $status): string
    {
        return match ($status) {
            'pending' => '#d97706',
            'for_release' => '#3b82f6',
            'ready_for_pickup' => '#16a34a',
            'completed' => '#22c55e',
            'rejected' => '#ef4444',
            default => '#64748b',
        };
    }

    private function timelineEntryFromActivity(mixed $log, Document $document): ?array
    {
        $action = strtolower(trim((string) $log->action_type));
        $details = trim((string) ($log->action_details ?? ''));
        $timestamp = $log->created_at ?? $document->updated_at ?? $document->created_at;

        if ($action === 'client_revision_submitted') {
            return $this->timelineEntry(
                'in_progress',
                'New Version Submitted',
                str_replace(
                    ['A revised document was uploaded', 'Revised documents were uploaded'],
                    ['You submitted a revised document', 'You submitted revised documents'],
                    $details,
                ),
                $timestamp,
            );
        }

        if (str_contains($action, 'accepted')) {
            return $this->timelineEntry(
                'in_progress',
                'Accepted',
                'Your document was accepted and moved to Incoming.',
                $timestamp,
            );
        }

        if ($action === 'document moved to outgoing') {
            $sentTo = preg_replace(
                '/^Sent to\s+(.+?)\s+on\s+\d{4}-\d{2}-\d{2}\.$/i',
                '$1',
                $details,
            );
            $sentTo = $sentTo !== $details ? trim((string) $sentTo) : trim((string) $document->sent_to);
            $sentTo = $sentTo !== '' ? $sentTo : 'the receiving office';

            return $this->timelineEntry(
                'outgoing',
                'Sent to ' . $sentTo,
                'Your document was sent to ' . $sentTo . '.',
                $timestamp,
            );
        }

        if ($action === 'document returned') {
            if (filled($document->returned_from)) {
                return $this->returnedFromEntry(
                    $document,
                    $timestamp,
                    (string) $document->returned_from,
                );
            }

            $destination = str_contains(strtolower($details), 'outgoing')
                ? 'Outgoing'
                : 'Incoming';

            return $this->timelineEntry(
                $destination === 'Outgoing' ? 'outgoing' : 'in_progress',
                'Returned to ' . $destination,
                'Your document was returned to the ' . strtolower($destination) . ' section.',
                $timestamp,
            );
        }

        if ($action === 'document returned from archive') {
            return $this->timelineEntry(
                'completed',
                'Completed',
                'Your document was returned from the archive.',
                $timestamp,
            );
        }

        if ($action === 'document completed') {
            return $this->timelineEntry(
                'completed',
                'Completed',
                'Your document has been completed by the Legal Affairs Office.',
                $timestamp,
            );
        }

        if (str_contains($action, 'rejected')) {
            $reason = preg_replace(
                '/^Rejected (?:the )?(?:revised )?document:\s*/i',
                '',
                $details,
            );
            $description = 'Your document was rejected.';

            if ($reason !== $details && trim((string) $reason) !== '') {
                $description .= ' Reason: ' . trim((string) $reason);
            }

            return $this->timelineEntry(
                'rejected',
                'Rejected',
                $description,
                $timestamp,
            );
        }

        if ($action === 'document archived') {
            return $this->timelineEntry(
                'archived',
                'Archived',
                'Your document was archived.',
                $timestamp,
            );
        }

        if (in_array($action, ['document updated', 'document details updated'], true)) {
            $old = json_decode((string) ($log->old_value ?? ''), true);
            $new = json_decode((string) ($log->new_value ?? ''), true);
            $old = is_array($old) ? $old : [];
            $new = is_array($new) ? $new : [];

            if (
                filled($new['returned_from'] ?? null)
                && ($new['returned_from'] ?? null) !== ($old['returned_from'] ?? null)
            ) {
                return $this->returnedFromEntry(
                    $document,
                    $timestamp,
                    (string) $new['returned_from'],
                );
            }

            if (
                filled($new['action_type'] ?? null)
                && ($new['action_type'] ?? null) !== ($old['action_type'] ?? null)
            ) {
                $actionType = trim((string) $new['action_type']);

                return $this->timelineEntry(
                    'action',
                    'Action Taken: ' . $actionType,
                    'The action taken was changed to ' . $actionType . '.',
                    $timestamp,
                    $this->actionTypeColor($actionType),
                );
            }

            if (
                filled($new['sent_to'] ?? null)
                && ($new['sent_to'] ?? null) !== ($old['sent_to'] ?? null)
            ) {
                $sentTo = trim((string) $new['sent_to']);

                return $this->timelineEntry(
                    'outgoing',
                    'Sent to ' . $sentTo,
                    'Your document was sent to ' . $sentTo . '.',
                    $timestamp,
                );
            }

            if (
                filled($new['status'] ?? null)
                && ($new['status'] ?? null) !== ($old['status'] ?? null)
            ) {
                $status = (string) $new['status'];
                $label = $this->statusLabelFor($status);

                return $this->timelineEntry(
                    $status,
                    $label,
                    'The document status was updated to ' . $label . '.',
                    $timestamp,
                );
            }
        }

        return null;
    }

    private function fallbackStatusEntry(Document $document): array
    {
        $status = (string) $document->status;
        $label = $this->statusLabelFor($status);

        if (filled($document->returned_from)) {
            return $this->returnedFromEntry(
                $document,
                $document->updated_at ?? $document->created_at,
                (string) $document->returned_from,
            );
        }

        if ($status === 'outgoing' && filled($document->sent_to)) {
            return $this->timelineEntry(
                'outgoing',
                'Sent to ' . $document->sent_to,
                'Your document was sent to ' . $document->sent_to . '.',
                $document->updated_at ?? $document->created_at,
            );
        }

        $description = match ($status) {
            'in_progress' => 'Your document is being processed by the Legal Affairs Office.',
            'completed' => 'Your document has been completed by the Legal Affairs Office.',
            'rejected' => filled($document->rejection_reason)
                ? 'Your document was rejected. Reason: ' . $document->rejection_reason
                : 'Your document was rejected.',
            'archived' => 'Your document was archived.',
            default => 'The document status was updated to ' . $label . '.',
        };

        return $this->timelineEntry(
            $status,
            $label,
            $description,
            $document->updated_at ?? $document->created_at,
        );
    }

    private function returnedFromEntry(
        Document $document,
        mixed $timestamp,
        string $returnedFrom,
    ): array {
        $returnedFrom = trim($returnedFrom) ?: 'the receiving office';
        $status = (string) $document->status;
        $status = $status !== '' && $status !== 'pending' ? $status : 'in_progress';

        return $this->timelineEntry(
            $status,
            'Returned from ' . $returnedFrom,
            'Your document was returned from ' . $returnedFrom . '.',
            $timestamp,
        );
    }

    private function timelineEntry(
        string $status,
        string $title,
        string $description,
        mixed $timestamp,
        ?string $color = null,
    ): array {
        return [
            'status' => $status,
            'title' => $title,
            'description' => $description,
            'time' => $timestamp instanceof \DateTimeInterface
                ? $timestamp->format('g:i A')
                : '—',
            'date' => $timestamp instanceof \DateTimeInterface
                ? $timestamp->format('M d, Y')
                : '—',
            'color' => $color ?: $this->statusColor($status),
        ];
    }

    private function actionTypeColor(?string $actionType): ?string
    {
        $actionType = trim((string) $actionType);

        if ($actionType === '') {
            return null;
        }

        $color = ActionType::query()
            ->where('action_name', $actionType)
            ->value('color');

        return is_string($color) && preg_match('/^#[0-9a-f]{6}$/i', $color)
            ? $color
            : null;
    }

    private function statusColor(string $status): string
    {
        return match ($status) {
            'pending' => '#d97706',
            'for_release' => '#3b82f6',
            'ready_for_pickup' => '#16a34a',
            'in_progress', 'outgoing' => '#3b82f6',
            'completed', 'archived' => '#22c55e',
            'rejected' => '#ef4444',
            default => '#64748b',
        };
    }

    private function statusLabelFor(string $status): string
    {
        return match ($status) {
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'pending' => 'Pending',
            'rejected' => 'Rejected',
            'outgoing' => 'Outgoing',
            'returned' => 'Returned',
            'archived' => 'Archived',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }
}
