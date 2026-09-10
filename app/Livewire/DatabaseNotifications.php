<?php

namespace App\Livewire;

use App\Filament\Notifications\ClickableDatabaseNotification;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Livewire\Attributes\On;

class DatabaseNotifications extends \Filament\Livewire\DatabaseNotifications
{
    #[On('notificationClosed')]
    public function removeNotification(string $id): void
    {
        // Database notifications are retained permanently. The close event is
        // ignored so a notification cannot be deleted from the notification UI.
    }

    public function clearNotifications(): void
    {
        // Database notifications are retained permanently.
    }

    public function clearNotificationsAction(): Action
    {
        return parent::clearNotificationsAction()->hidden();
    }

    public function openNotification(string $id): void
    {
        if (! Str::isUuid($id)) {
            return;
        }

        $notification = $this->getNotificationsQuery()
            ->whereKey($id)
            ->first();

        if (! $notification) {
            return;
        }

        $redirectUrl = $this->getRedirectUrl($notification);

        if (! filled($redirectUrl)) {
            return;
        }

        $notification->markAsRead();

        $this->redirect($redirectUrl);
    }

    public function getNotification(DatabaseNotification $notification): Notification
    {
        return ClickableDatabaseNotification::fromDatabase($notification)
            ->actions([])
            ->redirectUrl($this->getRedirectUrl($notification))
            ->date($this->formatNotificationDate($notification->getAttributeValue('created_at')));
    }

    private function getRedirectUrl(DatabaseNotification $notification): ?string
    {
        $redirectUrl = data_get($notification->data, 'redirect_url');

        if (filled($redirectUrl)) {
            return $this->normalizeRedirectUrl((string) $redirectUrl);
        }

        $legacyUrl = collect(data_get($notification->data, 'actions', []))
            ->pluck('url')
            ->first(fn ($url): bool => filled($url));

        return $this->normalizeRedirectUrl($legacyUrl);
    }

    private function normalizeRedirectUrl(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $parts = parse_url($url);
        $path = $parts['path'] ?? '';

        if (preg_match('#^/client/documents/(\d+)$#', $path, $matches)) {
            $query = [];
            parse_str($parts['query'] ?? '', $query);

            $tab = $query['tab'] ?? 'all';
            $tab = in_array($tab, [
                'all',
                'pending',
                'in_progress',
                'completed',
                'rejected',
                'requested',
            ], true) ? $tab : 'all';

            return url(
                '/client/documents?tab=' . $tab .
                '&document=' . (int) $matches[1]
            );
        }

        if (preg_match('#^/admin/documents/(\d+)$#', $path, $matches)) {
            $query = [];
            parse_str($parts['query'] ?? '', $query);

            $section = $query['section'] ?? 'pending';
            $section = in_array($section, [
                'pending',
                'incoming',
                'outgoing',
                'completed',
                'rejected',
                'archived',
            ], true) ? $section : 'pending';

            return url(
                '/admin/incoming?section=' . $section .
                '&document=' . (int) $matches[1]
            );
        }

        return $url;
    }
}
