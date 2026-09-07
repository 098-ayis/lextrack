<?php

namespace App\Console\Commands;

use App\Models\Calendar;
use App\Models\Document;
use App\Notifications\CalendarEventReminder;
use App\Services\AdminDocumentNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendCalendarReminders extends Command
{
    protected $signature = 'calendar:send-reminders';

    protected $description = 'Send email and in-app reminders for calendar events and document deadlines';

    public function handle(AdminDocumentNotificationService $notifications): int
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

                $event->update([
                    'reminder_10_minutes_sent_at' => now(),
                ]);

                $this->info(
                    "10-minute reminder sent for: {$event->event}"
                );
            }
        }

        $this->sendDocumentDeadlineReminders($notifications);

        return self::SUCCESS;
    }

    private function sendDocumentDeadlineReminders(
        AdminDocumentNotificationService $notifications,
    ): void {
        $documents = Document::query()
            ->whereNotNull('deadline')
            ->whereNotIn('status', [
                'completed',
                'rejected',
                'archived',
            ])
            ->get();

        foreach ($documents as $document) {
            $daysUntilDeadline = today()->diffInDays(
                $document->deadline,
                false,
            );

            $reminderType = match ($daysUntilDeadline) {
                3 => '3_days',
                1 => '1_day',
                0 => 'deadline_day',
                default => null,
            };

            if (! $reminderType) {
                continue;
            }

            $notifications->notifyDocumentDeadline(
                $document,
                $reminderType,
            );

            $this->info(
                "{$reminderType} deadline reminder checked for document #{$document->document_id}"
            );
        }
    }
}
