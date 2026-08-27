<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\RejectedDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Document $document,
        public RejectedDocument $rejection,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('LexTrack Document Rejected')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your document has been rejected.')
            ->line('Document: ' . $this->document->particulars)
            ->line('Reason: ' . $this->rejection->reason)
            ->action(
                'View Document',
                url('/client/documents/' . $this->document->document_id)
            )
            ->line('Please review the rejection reason and resubmit if necessary.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Document Rejected',
            'message' => 'Your document has been rejected.',
            'document_id' => $this->document->document_id,
            'particulars' => $this->document->particulars,
            'reason' => $this->rejection->reason,
        ];
    }
}
