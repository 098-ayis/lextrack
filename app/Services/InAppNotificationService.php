<?php

namespace App\Services;

use App\Models\User;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use Illuminate\Notifications\Notification;
use Throwable;

class InAppNotificationService
{
    public function send(User $recipient, Notification $notification): void
    {
        // Persist the bell notification independently of SMTP delivery.
        $recipient->notifyNow($notification, ['database']);
        DatabaseNotificationsSent::dispatch($recipient);

        if (filled($recipient->email)) {
            try {
                if (app()->runningInConsole()) {
                    // Keep console commands and tests deterministic.
                    $recipient->notifyNow($notification, ['mail']);
                } else {
                    // Never make a client submission wait for an SMTP server.
                    $recipient->notify($notification);
                }
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }
}
