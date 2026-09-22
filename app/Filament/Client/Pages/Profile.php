<?php

namespace App\Filament\Client\Pages;

use App\Filament\Pages\Profile as BaseProfile;
use App\Models\Client;
use Filament\Notifications\Notification;

class Profile extends BaseProfile
{
    protected string $view = 'filament.client.pages.profile';

    public string $office = '';

    public function mount(): void
    {
        parent::mount();

        $client = auth()->user()?->client;

        $this->office = (string) ($client?->office ?? '');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'office' => ['required', 'string', 'max:255'],
        ]);

        $client = Client::query()->firstOrNew([
            'user_id' => auth()->id(),
        ]);
        $client->office = trim($validated['office']);
        $client->save();

        Notification::make()
            ->success()
            ->title('Profile updated')
            ->body('Your office information has been saved.')
            ->send();
    }

    public function getHeading(): string
    {
        return '';
    }
}
