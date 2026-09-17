<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
// use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class Profile extends Page
{
    // use HasPageShield;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Profile';

    protected static ?string $navigationLabel = 'Profile';

    protected static ?string $slug = 'profile';

    protected string $view = 'filament.pages.profile';

    public string $name = '';

    public string $email = '';

    public ?string $currentPhoto = null;

    public function mount(): void
    {
        $user = Auth::user();

        $this->name = $user->name ?? '';
        $this->email = $user->email ?? '';
        $this->currentPhoto = $user->getProfilePhotoUrl();
    }

    public function getProfilePhotoUrl(): ?string
    {
        if (! $this->currentPhoto) {
            return null;
        }

        if (
            str_starts_with($this->currentPhoto, 'http://') ||
            str_starts_with($this->currentPhoto, 'https://')
        ) {
            return $this->currentPhoto;
        }

        return Storage::disk('public')->url(
            $this->currentPhoto
        );
    }
}
