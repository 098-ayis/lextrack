<?php

namespace App\Notifications;

use App\Models\DocumentRequest;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentRequestAcceptedNotification extends Notification
{
    use Queueable;

    public function __construct(public DocumentRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your document request was accepted')
            ->greeting('Hello, ' . $notifiable->name . '!')
            ->line('Your document request was accepted by the Legal Affairs Office.')
            ->line('The Legal Affairs Office will now prepare your requested copy.')
            ->action('View Request', url('/client/messages?request=' . $this->request->request_id));
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            ...FilamentNotification::make()
                ->title('Document request accepted')
                ->body('Your request is now being prepared.')
                ->success()
                ->getDatabaseMessage(),
            'redirect_url' => url('/client/messages?request=' . $this->request->request_id),
        ];
    }
}
