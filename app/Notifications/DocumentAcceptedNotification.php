<?php

namespace App\Notifications;

use App\Models\Document;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentAcceptedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Document $document,
        public string $tab = 'in_progress',
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->document->notificationLabel())
            ->greeting('Hello, ' . $notifiable->name . '!')
            ->line('Your submitted document has been accepted by the Legal Office.')
            ->line('Document: ' . $this->document->notificationLabel())
            ->line('Assigned LAO Number: ' . $this->document->lao_number)
            ->line('Status: In Progress')
            ->action(
                'View Document',
                url('/client/documents/' . $this->document->public_id)
            )
            ->line('You can use your LAO number to track the document in LexTrack.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            ...FilamentNotification::make()
                ->title($this->document->notificationLabel())
                ->body(
                    'Your document has been accepted and is now being processed.'
                )
                ->success()
                ->getDatabaseMessage(),
            'document_id' => $this->document->document_id,
            'redirect_url' => url(
                '/client/documents?tab=' . $this->tab .
                '&document=' . $this->document->public_id
            ),
        ];
    }
}
