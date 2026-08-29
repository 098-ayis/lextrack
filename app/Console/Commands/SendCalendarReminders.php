<?php

namespace App\Console\Commands;

use App\Models\Calendar;
use App\Notifications\CalendarEventReminder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendCalendarReminders extends Command
{
    protected $signature = 'calendar:send-reminders';

    protected $description = 'Send email reminders for upcoming calendar events';

    public function handle(): int
    {
        $now = now();

        $events = Calendar::with('user')
            ->whereNotNull('date')
            ->whereNotNull('time')
            ->whereNotNull('user_id')
            ->get();

        foreach ($events as $event) {
            if (!$event->user || !$event->user->email) {
                continue;
            }

            $eventDateTime = Carbon::parse(
                $event->date->format('Y-m-d') . ' ' .
                $event->time->format('H:i:s')
            );

            if ($eventDateTime->isPast()) {
                continue;
            }

            $minutesUntilEvent = $now->diffInMinutes(
                $eventDateTime,
                false
            );

            /*
             * 3 DAYS BEFORE
             *
             * 3 days = 4320 minutes.
             * Give the scheduler a one-minute window.
             */
            if (
                $minutesUntilEvent <= 4320 &&
                $minutesUntilEvent > 4319 &&
                !$event->reminder_3_days_sent_at
            ) {
                $event->user->notify(
                    new CalendarEventReminder($event, '3_days')
                );

                // Notification bell
                \Filament\Notifications\Notification::make()
                    ->title('Calendar Reminder')
                    ->body(
                        $event->event . ' is scheduled in 3 days.'
                    )
                    ->info()
                    ->sendToDatabase($event->user);

                $event->update([
                    'reminder_3_days_sent_at' => now(),
                ]);

                $this->info(
                    "3-day reminder sent for: {$event->event}"
                );
            }

            /*
             * 1 DAY BEFORE
             *
             * 1 day = 1440 minutes.
             */
            if (
                $minutesUntilEvent <= 1440 &&
                $minutesUntilEvent > 1439 &&
                !$event->reminder_1_day_sent_at
            ) {
                $event->user->notify(
                    new CalendarEventReminder($event, '1_day')
                );

                \Filament\Notifications\Notification::make()
                    ->title('Calendar Reminder')
                    ->body(
                        $event->event . ' is scheduled for tomorrow.'
                    )
                    ->warning()
                    ->sendToDatabase($event->user);

                $event->update([
                    'reminder_1_day_sent_at' => now(),
                ]);

                $this->info(
                    "1-day reminder sent for: {$event->event}"
                );
            }

            /*
             * 10 MINUTES BEFORE
             */
            if (
                $minutesUntilEvent <= 10 &&
                $minutesUntilEvent > 9 &&
                !$event->reminder_10_minutes_sent_at
            ) {
                $event->user->notify(
                    new CalendarEventReminder($event, '10_minutes')
                );

                \Filament\Notifications\Notification::make()
                    ->title('Upcoming Calendar Event')
                    ->body(
                        $event->event . ' will start in 10 minutes.'
                    )
                    ->warning()
                    ->sendToDatabase($event->user);

                $event->update([
                    'reminder_10_minutes_sent_at' => now(),
                ]);

                $this->info(
                    "10-minute reminder sent for: {$event->event}"
                );
            }
        }

        return self::SUCCESS;
    }
}