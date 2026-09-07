<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use App\Notifications\AdminDocumentSubmittedNotification;
use App\Notifications\DocumentDeadlineReminder;
use Illuminate\Support\Collection;

class AdminDocumentNotificationService
{
    /**
     * Send one unread submission notification per client to each admin.
     * Later submissions update the existing in-app notification count.
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
            $existing = $admin->unreadNotifications()
                ->where('type', AdminDocumentSubmittedNotification::class)
                ->get()
                ->first(
                    fn ($notification): bool => (int) data_get(
                        $notification->data,
                        'submission_user_id'
                    ) === (int) $document->user_id
                );

            $notification = new AdminDocumentSubmittedNotification(
                $document,
                $documentCount,
            );

            if ($existing) {
                $existing->update([
                    'data' => $notification->toDatabase($admin),
                ]);

                continue;
            }

            $admin->notify($notification);
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
                    fn ($notification): bool =>
                        (int) data_get($notification->data, 'document_id') === (int) $document->document_id
                        && data_get($notification->data, 'deadline') === $document->deadline->format('Y-m-d')
                        && data_get($notification->data, 'reminder_type') === $reminderType
                );

            if ($alreadySent) {
                continue;
            }

            $admin->notify(
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
            ->whereNotNull('email')
            ->whereHas(
                'roles',
                fn ($query) => $query->whereIn('name', [
                    'Admin',
                    'Super Admin',
                    'super_admin',
                ])
            )
            ->get();
    }
}
