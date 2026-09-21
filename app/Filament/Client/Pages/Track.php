<?php

namespace App\Filament\Client\Pages;

use App\Models\Document;
use App\Services\DocumentStatusTimeline;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Track extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.client.pages.track';

    public function getHeading(): string
    {
        return '';
    }

    public string $trackingNumber = '';

    public ?Document $document = null;

    public bool $hasSearched = false;

    /**
     * @var array<int, array{status: string, title: string, description: string, time: string, date: string}>
     */
    public array $statusTimeline = [];

    public function trackDocument(): void
    {
        $this->validate([
            'trackingNumber' => ['required', 'string'],
        ]);

        $this->hasSearched = true;
        $this->statusTimeline = [];

        $trackingNumber = strtoupper(
            trim($this->trackingNumber)
        );

        $this->document = Document::query()
            ->with([
                'activityLogs' => fn ($query) => $query
                    ->oldest('created_at')
                    ->oldest('log_id'),
            ])
            ->where('lao_number', $trackingNumber)
            ->where('user_id', auth()->id())
            ->first();

        if (!$this->document) {
            Notification::make()
                ->title('Document not found')
                ->body('Please check your LAO number and try again.')
                ->danger()
                ->send();

            return;
        }

        $this->statusTimeline = app(DocumentStatusTimeline::class)->build($this->document);
    }

    private function buildStatusTimeline(Document $document): array
    {
        $timeline = [
            $this->timelineEntry(
                'pending',
                'Pending',
                'Your document was submitted and is waiting for review.',
                $document->created_at,
            ),
        ];

        foreach ($document->activityLogs as $log) {
            $entry = $this->timelineEntryFromActivity($log, $document);

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

        $lastEntry = $timeline[array_key_last($timeline)] ?? null;

        if (
            $document->status !== 'pending'
            && ($lastEntry === null || $lastEntry['status'] !== $document->status)
        ) {
            $timeline[] = $this->fallbackStatusEntry($document);
        }

        return $timeline;
    }

    private function timelineEntryFromActivity(mixed $log, Document $document): ?array
    {
        $action = strtolower(trim((string) $log->action_type));
        $details = trim((string) ($log->action_details ?? ''));
        $timestamp = $log->created_at ?? $document->updated_at ?? $document->created_at;

        if (str_contains($action, 'accepted')) {
            return $this->timelineEntry(
                'in_progress',
                'In Progress',
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

    private function timelineEntry(
        string $status,
        string $title,
        string $description,
        mixed $timestamp,
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
        ];
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
