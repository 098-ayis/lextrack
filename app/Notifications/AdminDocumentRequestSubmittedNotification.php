<?php

namespace App\Notifications;

use App\Models\DocumentRequest;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminDocumentRequestSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public DocumentRequest $request,
    ) {}

    public function via(object $notifiable): array
    {
        // The database channel is persisted immediately by
        // InAppNotificationService; only email is queued from the request.
        return ['mail'];
    }

    public function viaConnections(): array
    {
        return ['mail' => 'background'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $requesterName = $this->request->user?->name ?? 'A client';

        return (new MailMessage)
            ->subject('New document request')
            ->greeting('Hello, ' . $notifiable->name . '!')
            ->line($requesterName . ' submitted a new document request.')
            ->line('Purpose: ' . ($this->request->purpose ?: 'Not specified'))
            ->line('Copy type: ' . $this->copyTypeLabel())
            ->action(
                'Review Request',
                url('/admin/document-requests?section=pending')
            )
            ->line('This is an automated notification from LexTrack.');
    }

    public function toDatabase(object $notifiable): array
    {
        $requesterName = $this->request->user?->name ?? 'A client';

        return [
            ...FilamentNotification::make()
                ->title('New document request')
                ->body(
                    $requesterName . ' submitted a request for ' .
                    ($this->request->purpose ?: 'a document') . '.'
                )
                ->info()
                ->getDatabaseMessage(),
            'request_id' => $this->request->request_id,
            'submission_user_id' => $this->request->user_id,
            'redirect_url' => \App\Filament\Pages\DocumentRequests::getUrl([
                'section' => 'pending',
            ], true, 'admin'),
        ];
    }

    private function copyTypeLabel(): string
    {
        return match ($this->request->copy_type) {
            'original' => 'Original copy',
            'soft_copy' => 'Soft copy',
            default => 'Not specified',
        };
    }
}
