<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentDeadlineReminder extends Notification
{
    public function __construct(
        public Document $document,
        public string $reminderType,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('LexTrack: Document deadline reminder')
            ->greeting('Hello, ' . $notifiable->name . '!')
            ->line($this->getReminderMessage())
            ->line('Document: ' . ($this->document->particulars ?: 'Untitled document'))
            ->line('LAO Number: ' . ($this->document->lao_number ?: 'Not assigned'))
            ->line('Deadline: ' . $this->document->deadline->format('F d, Y'))
            ->action('Open Calendar', url('/admin/calendar'))
            ->line('This is an automated notification from LexTrack.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Document deadline reminder',
            'body' => $this->getReminderMessage() . ' Deadline: ' .
                $this->document->deadline->format('F d, Y') . '.',
            'icon' => 'heroicon-o-calendar-days',
            'iconColor' => 'warning',
            'status' => 'warning',
            'duration' => 'persistent',
            'format' => 'filament',
            'document_id' => $this->document->document_id,
            'deadline' => $this->document->deadline->format('Y-m-d'),
            'reminder_type' => $this->reminderType,
        ];
    }

    private function getReminderMessage(): string
    {
        return match ($this->reminderType) {
            '3_days' => 'A document deadline is in 3 days.',
            '1_day' => 'A document deadline is tomorrow.',
            'deadline_day' => 'A document deadline is today.',
            default => 'A document deadline is approaching.',
        };
    }
}
