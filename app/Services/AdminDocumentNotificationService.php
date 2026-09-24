<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentRequest;
use App\Models\User;
use App\Notifications\AdminDocumentRequestSubmittedNotification;
use App\Notifications\AdminDocumentSubmittedNotification;
use App\Notifications\DocumentDeadlineReminder;
use Illuminate\Support\Collection;

class AdminDocumentNotificationService
{
    /**
     * Notify every administrator about each client document submission.
     */
    public function notifyDocumentSubmitted(Document $document): void
    {
        $document->loadMissing('user');

        if (! $document->user) {
            return;
        }

        $documentCount = Document::query()
            ->where('user_id', $document->user_id)
            ->where('status', 'pending')
            ->count();

        foreach ($this->administrators() as $admin) {
            $notification = new AdminDocumentSubmittedNotification(
                $document,
                $documentCount,
            );

            app(InAppNotificationService::class)->send($admin, $notification);
        }
    }

    /**
     * Notify every admin when a client submits a document request.
     */
    public function notifyRequestSubmitted(DocumentRequest $request): void
    {
        $request->loadMissing('user');

        foreach ($this->administrators() as $admin) {
            app(InAppNotificationService::class)->send(
                $admin,
                new AdminDocumentRequestSubmittedNotification($request)
            );
        }
    }

    /**
     * Send one email and one app notification for a document deadline.
     * The notification data contains the deadline, so changing a deadline
     * naturally creates a new reminder series without extra document flags.
     */
    public function notifyDocumentDeadline(
        Document $document,
        string $reminderType,
    ): void {
        $document->loadMissing('user');

        foreach ($this->administrators() as $admin) {
            $alreadySent = $admin->notifications()
                ->where('type', DocumentDeadlineReminder::class)
                ->get()
                ->contains(
                    fn ($notification): bool => (int) data_get($notification->data, 'document_id') === (int) $document->document_id
                        && data_get($notification->data, 'deadline') === $document->deadline->format('Y-m-d')
                        && data_get($notification->data, 'reminder_type') === $reminderType
                );

            if ($alreadySent) {
                continue;
            }

            app(InAppNotificationService::class)->send(
                $admin,
                new DocumentDeadlineReminder($document, $reminderType)
            );
        }
    }

    /**
     * Admin-panel users who should receive document workflow alerts.
     */
    protected function administrators(): Collection
    {
        return User::query()
            ->whereHas(
                'roles',
                fn ($query) => $query->whereRaw(
                    "LOWER(REPLACE(name, '_', ' ')) IN (?, ?)",
                    ['admin', 'super admin']
                )
            )
            ->get();
    }
}
