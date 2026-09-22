<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\DocumentRequest;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentRequestFulfilledNotification extends Notification
{
    use Queueable;

    public function __construct(
        public DocumentRequest $request,
        public Document $document,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isSoftCopy = $this->request->copy_type === 'soft_copy';
        $pickupSchedule = $this->request->pickup_at?->format('F d, Y g:i A');
        $pickupMessage = $pickupSchedule
            ? 'Your requested original document has been scheduled for pickup on ' . $pickupSchedule . '.'
            : 'Your requested original document has a pickup date scheduled.';

        $mail = (new MailMessage)
            ->subject(
                $isSoftCopy
                    ? 'Your requested document is ready'
                    : 'Original document pickup scheduled'
            )
            ->greeting('Hello, ' . $notifiable->name . '!')
            ->line(
                $isSoftCopy
                    ? 'Your requested soft copy is ready.'
                    : $pickupMessage
            )
            ->line('Purpose: ' . $this->request->purpose);

        return $mail
            ->action(
                'View Request',
                url('/client/documents/' . $this->document->document_id . '?tab=requested')
            )
            ->line('You can message the Legal Affairs Office if you need clarification.');
    }

    public function toDatabase(object $notifiable): array
    {
        $isSoftCopy = $this->request->copy_type === 'soft_copy';
        $pickupMessage = $this->request->pickup_at
            ? 'Your requested original document has been scheduled for pickup on ' . $this->request->pickup_at->format('F d, Y g:i A') . '.'
            : 'Your requested original document has a pickup date scheduled.';

        return [
            ...FilamentNotification::make()
                ->title($isSoftCopy
                    ? 'Requested document is ready'
                    : 'Original document pickup scheduled')
                ->body($isSoftCopy
                    ? 'Your requested soft copy is ready to view and download.'
                    : $pickupMessage)
                ->success()
                ->getDatabaseMessage(),
            'document_id' => $this->document->document_id,
            'redirect_url' => url(
                '/client/documents?tab=requested&document=' .
                $this->document->document_id
            ),
        ];
    }
}
