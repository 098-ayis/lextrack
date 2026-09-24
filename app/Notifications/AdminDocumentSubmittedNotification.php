<?php

namespace App\Notifications;

use App\Models\Document;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminDocumentSubmittedNotification extends Notification
{
    public function __construct(
        public Document $document,
        public int $documentCount,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $submitterName = $this->document->user?->name ?? 'A client';

        return (new MailMessage)
            ->subject(
                'New document submission: ' .
                $this->document->notificationLabel()
            )
            ->greeting('Hello, ' . $notifiable->name . '!')
            ->line(
                $submitterName . ' submitted a new document for review.'
            )
            ->line('Latest Submission: ' . $this->document->notificationLabel())
            ->line('Pending documents from this client: ' . $this->documentCount)
            ->line('Status: Pending review')
            ->action(
                'Review Submissions',
                url('/admin/incoming?section=pending')
            )
            ->line('This is an automated notification from LexTrack.');
    }

    public function toDatabase(object $notifiable): array
    {
        $submitterName = $this->document->user?->name ?? 'A client';

        return [
            ...FilamentNotification::make()
                ->title($this->document->notificationLabel())
                ->body(
                    $submitterName . ' submitted a new document for review. ' .
                    'Pending documents from this client: ' . $this->documentCount . '.'
                )
                ->info()
                ->getDatabaseMessage(),
            'submission_user_id' => $this->document->user_id,
            'document_id' => $this->document->document_id,
            'document_count' => $this->documentCount,
            'redirect_url' => \App\Filament\Pages\Document::getUrl([
                'section' => 'pending',
                'document' => $this->document->public_id,
            ], true, 'admin'),
        ];
    }
}
