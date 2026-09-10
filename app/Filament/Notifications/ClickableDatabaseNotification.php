<?php

namespace App\Filament\Notifications;

use Filament\Notifications\Notification as FilamentNotification;

class ClickableDatabaseNotification extends FilamentNotification
{
    protected ?string $redirectUrl = null;

    public function redirectUrl(?string $url): static
    {
        $this->redirectUrl = $url;

        return $this;
    }

    public function toEmbeddedHtml(): string
    {
        $html = parent::toEmbeddedHtml();

        // Database notifications must not be dismissible because dismissing
        // one also deletes it from the user's notification history.
        $html = preg_replace(
            '/\s*<button\b(?=[^>]*\bfi-no-notification-close-btn\b)[^>]*>.*?<\/button>/s',
            '',
            $html,
        ) ?? $html;

        if (! filled($this->redirectUrl)) {
            return $html;
        }

        $notificationId = preg_replace(
            '/[^A-Za-z0-9-]/',
            '',
            $this->getId(),
        );

        $clickHandler = "if (! \$event.target.closest('button, a')) { \$wire.openNotification('{$notificationId}'); }";
        $keyboardHandler = "if (! \$event.target.closest('button, a')) { \$event.preventDefault(); \$wire.openNotification('{$notificationId}'); }";

        $attributes = sprintf(
            ' x-on:click="%s" x-on:keydown.enter="%s" x-on:keydown.space="%s" tabindex="0" x-bind:class="\'cursor-pointer\'"',
            $clickHandler,
            $keyboardHandler,
            $keyboardHandler,
        );

        return preg_replace_callback(
            '/<div(\s+)/',
            fn (array $matches): string => '<div' . $attributes . $matches[1],
            $html,
            1,
        ) ?? $html;
    }
}
