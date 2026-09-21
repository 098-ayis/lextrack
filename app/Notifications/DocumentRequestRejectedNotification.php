<?php

namespace App\Notifications;

use App\Models\DocumentRequest;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentRequestRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public DocumentRequest $request,
        public string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your document request was rejected')
            ->greeting('Hello, ' . $notifiable->name . '!')
            ->line('Your document request was rejected by the Legal Affairs Office.')
            ->line('Purpose: ' . $this->request->purpose)
            ->line('Reason: ' . $this->reason)
            ->action(
                'Open Messages',
                url('/client/messages?request=' . $this->request->request_id)
            )
            ->line('Please message the Legal Affairs Office if you need clarification.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            ...FilamentNotification::make()
                ->title('Document request rejected')
                ->body('Reason: ' . $this->reason)
                ->danger()
                ->getDatabaseMessage(),
            'redirect_url' => url(
                '/client/messages?request=' . $this->request->request_id
            ),
        ];
    }
}
