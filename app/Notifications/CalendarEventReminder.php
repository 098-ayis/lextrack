<?php

namespace App\Notifications;

use App\Models\Calendar;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CalendarEventReminder extends Notification
{
    use Queueable;

    public function __construct(
        public Calendar $event,
        public string $reminderType,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('LexTrack Calendar Reminder: ' . $this->event->event)
            ->greeting('Hello, ' . $notifiable->name . '!')
            ->line($this->getReminderMessage())
            ->line('Event: ' . $this->event->event)
            ->line('Details: ' . $this->event->details)
            ->line(
                'Date: ' . $this->event->date->format('F d, Y')
            )
            ->line(
                'Time: ' . $this->event->time->format('h:i A')
            )
            ->action(
                'Open Calendar',
                url('/admin/calendar')
            )
            ->line(
                'This is an automated reminder from LexTrack.'
            );
    }

    private function getReminderMessage(): string
    {
        return match ($this->reminderType) {
            '3_days' =>
                'Reminder: This event is scheduled in 3 days.',

            '1_day' =>
                'Reminder: This event is scheduled tomorrow.',

            '10_minutes' =>
                'Reminder: This event will start in 10 minutes.',

            default =>
                'You have an upcoming calendar event.',
        };
    }
}