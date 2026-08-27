<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentRejectedNotification extends Notification
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
            ->subject('LexTrack: Document Submission Rejected')
            ->greeting('Hello, ' . $notifiable->name . '!')
            ->line(
                'Your submitted document has been rejected by the Legal Office.'
            )
            ->line('Document: ' . $this->document->particulars)
            ->line('Status: Rejected')
            ->line(
                'Reason: ' . $this->document->rejection_reason
            )
            ->action(
                'View Document',
                url(
                    '/client/documents/' .
                    $this->document->document_id
                )
            )
            ->line(
                'Please review the reason above and contact the Legal Office if you need further clarification.'
            );
    }

}
