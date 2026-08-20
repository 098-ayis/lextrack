<?php

namespace App\Filament\Client\Pages;

use Filament\Pages\Page;

class Messages extends Page
{
    protected string $view = 'filament.client.pages.messages';

    public function getViewData(): array
    {
        return [
            'conversations' => auth()->user()->conversations()
                ->with([
                    'document',
                    'messages.sender',
                    'messages.recipient',
                ])
                ->latest()
                ->get(),
        ];
    }
}
