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
        $documentLabel = $this->documentCount === 1
            ? 'document'
            : 'documents';

        return (new MailMessage)
            ->subject(
                'LexTrack: New document submission from ' . $submitterName
            )
            ->greeting('Hello, ' . $notifiable->name . '!')
            ->line(
                $submitterName . ' has submitted ' .
                $this->documentCount . ' ' . $documentLabel . ' for review.'
            )
            ->line('Latest submission: ' . ($this->document->particulars ?: 'Untitled document'))
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
        $documentLabel = $this->documentCount === 1
            ? 'document'
            : 'documents';

        return [
            ...FilamentNotification::make()
                ->title('New document submission')
                ->body(
                    $submitterName . ' submitted ' .
                    $this->documentCount . ' ' . $documentLabel . ' for review.'
                )
                ->info()
                ->getDatabaseMessage(),
            'submission_user_id' => $this->document->user_id,
            'document_id' => $this->document->document_id,
            'document_count' => $this->documentCount,
            'redirect_url' => \App\Filament\Pages\Document::getUrl([
                'section' => 'pending',
                'document' => $this->document->document_id,
            ], true, 'admin'),
        ];
    }
}
