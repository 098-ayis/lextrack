<?php

namespace App\Filament\Client\Pages;

use app\Models\Document;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Track extends Page
{
    protected string $view = 'filament.client.pages.track';

    public string $trackingNumber = '';

    public ?Document $document = null;

    public bool $hasSearched = false;

    public function trackDocument(): void
    {
        $this->validate([
            'trackingNumber' => ['required', 'string'],
        ]);

        $this->hasSearched = true;

        $this->document = Document::where(
            'tracking_number',
            $this->trackingNumber
        )->first();

        if(!$this->document){
            Notification::make()
                ->title('Document not found')
                ->danger()
                ->send();

            return;
        }
        
    }

}
