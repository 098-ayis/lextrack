<?php

namespace App\Filament\Client\Pages;

use App\Models\Document;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Track extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.client.pages.track';

    public function getHeading(): string
    {
        return '';
    }

    public string $trackingNumber = '';

    public ?Document $document = null;

    public bool $hasSearched = false;

    public function trackDocument(): void
    {
        $this->validate([
            'trackingNumber' => ['required', 'string'],
        ]);

        $this->hasSearched = true;

        $trackingNumber = strtoupper(
            trim($this->trackingNumber)
        );

        $this->document = Document::query()
            ->with(['type', 'actionType'])
            ->where('lao_number', $trackingNumber)
            ->where('user_id', auth()->id())
            ->first();

        if (!$this->document) {
            Notification::make()
                ->title('Document not found')
                ->body('Please check your LAO number and try again.')
                ->danger()
                ->send();

            return;
        }
    }
}
