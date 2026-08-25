<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class Profile extends Page
{
    use WithFileUploads;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Profile';

    protected static ?string $navigationLabel = 'Profile';

    protected static ?string $slug = 'profile';

    protected string $view = 'filament.pages.profile';

    public string $name = '';

    public string $email = '';

    public $photo = null;

    public ?string $currentPhoto = null;

    public function mount(): void
    {
        $user = Auth::user();

        $this->name = $user->name ?? '';
        $this->email = $user->email ?? '';
        $this->currentPhoto = $user->profile_photo_url;
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email,' . Auth::id(),
            ],

            'photo' => [
                'nullable',
                'image',
                'max:2048',
            ],
        ]);

        $user = Auth::user();

        $user->name = $this->name;
        $user->email = $this->email;

        if ($this->photo) {
            /*
             * Delete previous locally-uploaded profile picture.
             *
             * We do NOT delete Google profile URLs.
             */
            if (
                $user->profile_photo_url &&
                ! str_starts_with($user->profile_photo_url, 'http')
            ) {
                Storage::disk('public')
                    ->delete($user->profile_photo_url);
            }

            $path = $this->photo->store(
                'profile-photos',
                'public'
            );

            $user->profile_photo_url = $path;
        }

        $user->save();

        $this->currentPhoto = $user->profile_photo_url;

        $this->photo = null;

        Notification::make()
            ->title('Profile updated successfully')
            ->success()
            ->send();
    }

    public function getProfilePhotoUrl(): ?string
    {
        if ($this->photo) {
            return $this->photo->temporaryUrl();
        }

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