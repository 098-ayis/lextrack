<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\RejectedDocument;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Document $document,
        public ?RejectedDocument $rejection = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
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
                'Reason: ' . (
                    $this->rejection?->reason
                    ?? $this->document->rejection_reason
                    ?? 'Not provided'
                )
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

    public function toDatabase(object $notifiable): array
    {
        $reason = $this->rejection?->reason
            ?? $this->document->rejection_reason
            ?? 'Not provided';

        return [
            ...FilamentNotification::make()
                ->title('Document Rejected')
                ->body('Your document has been rejected. Reason: ' . $reason)
                ->danger()
                ->getDatabaseMessage(),
            'document_id' => $this->document->document_id,
            'redirect_url' => url(
                '/client/documents?tab=rejected&document=' .
                $this->document->document_id
            ),
        ];
    }

}
