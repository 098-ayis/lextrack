<?php

namespace App\Services;

use App\Models\ActionType;
use App\Models\Document;

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

            $lastEntry = $timeline[array_key_last($timeline)] ?? null;

            if (
                $lastEntry
                && $log->action_type !== 'client_revision_submitted'
                && $lastEntry['status'] === $entry['status']
                && $lastEntry['title'] === $entry['title']
            ) {
                $timeline[array_key_last($timeline)] = $entry;

                continue;
            }

            $timeline[] = $entry;
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
                'In Progress',
                'Your document was accepted and moved to Incoming.',
                $timestamp,
                $this->actionTypeColor($document->action_type),
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
