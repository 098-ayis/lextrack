<?php

namespace App\Notifications;

use App\Models\Document;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentCompletedNotification extends Notification
{
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
                'LexTrack: Document Completed - ' .
                ($this->document->lao_number ?? $this->document->document_id)
            )
            ->greeting('Hello, ' . $notifiable->name . '!')
            ->line('Your document has been completed by the Legal Office.')
            ->line('Document: ' . $this->document->particulars)
            ->line('Status: Completed')
            ->action(
                'View Document',
                url('/client/documents/' . $this->document->document_id)
            )
            ->line(
                'You can view the completed document and its latest status in LexTrack.'
            );
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            ...FilamentNotification::make()
                ->title('Document Completed')
                ->body(
                    'Your document has been completed by the Legal Office.'
                )
                ->success()
                ->getDatabaseMessage(),
            'document_id' => $this->document->document_id,
            'redirect_url' => url(
                '/client/documents?tab=completed&document=' .
                $this->document->document_id
            ),
        ];
    }
}
