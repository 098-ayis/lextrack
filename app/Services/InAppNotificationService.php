<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Throwable;

class InAppNotificationService
{
    public function send(User $recipient, Notification $notification): void
    {
        // Persist the bell notification independently of SMTP delivery.
        $recipient->notifyNow($notification, ['database']);

        if (filled($recipient->email)) {
            try {
                $recipient->notifyNow($notification, ['mail']);
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }
}
