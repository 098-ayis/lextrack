<?php

namespace App\Notifications;

use App\Models\Document;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentPendingNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Document $document,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(
                'Document pending again: ' .
                $this->document->notificationLabel()
            )
            ->greeting('Hello, ' . $notifiable->name . '!')
            ->line(
                'Your document has been returned to Pending by the Legal Office for validation.'
            )
            ->line('Document: ' . $this->document->notificationLabel())
            ->line('Status: Pending')
            ->action(
                'View Document',
                url('/client/documents/' . $this->document->public_id)
            )
            ->line(
                'Please wait for the Legal Office to review and validate your document again.'
            );
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            ...FilamentNotification::make()
                ->title($this->document->notificationLabel())
                ->body(
                    'Your document was returned to Pending for validation.'
                )
                ->warning()
                ->getDatabaseMessage(),
            'document_id' => $this->document->document_id,
            'redirect_url' => url(
                '/client/documents?tab=pending&document=' .
                $this->document->public_id
            ),
        ];
    }
}
