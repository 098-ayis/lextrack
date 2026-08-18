<?php

namespace App\Filament\Pages;

use App\Models\Conversation;
use Filament\Pages\Page;

class Messages extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected string $view = 'filament.pages.messages';

    public function getViewData(): array
    {
        return [
            'conversations' => Conversation::with([
                'document',
                'messages.sender',
                'messages.recipient',
            ])
            ->latest()
            ->get(),
        ];
    }
}