<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentAcceptedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Document $document
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(
                'LexTrack: Document Accepted - ' .
                $this->document->lao_number
            )
            ->greeting('Hello, ' . $notifiable->name . '!')
            ->line('Your submitted document has been accepted by the Legal Office.')
            ->line('Document: ' . $this->document->particulars)
            ->line('Assigned LAO Number: ' . $this->document->lao_number)
            ->line('Status: In Progress')
            ->action(
                'View Document',
                url('/client/documents/' . $this->document->document_id)
            )
            ->line('You can use your LAO number to track the document in LexTrack.');
    }
}