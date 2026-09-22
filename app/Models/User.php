<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'phone_number'])]
#[Hidden(['password', 'remember_token'])]

class User extends Authenticatable implements FilamentUser, HasAvatar, MustVerifyEmail
{
    public const ADMIN_ROLES = ['Admin', 'Super Admin'];

    public const DEFAULT_STATUS = 'Active';

    public const STATUS_OPTIONS = [
        'Active' => 'Active',
        'Inactive' => 'Inactive',
        'Pending' => 'Pending',
        'Suspended' => 'Suspended',
    ];

    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, MustVerifyEmailTrait, Notifiable;

    use HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'provider',
        'profile_photo_url',
        'status',
        'join_date',
        'last_login',
        'phone_number',
    ];

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->getProfilePhotoUrl();
    }

    public function getProfilePhotoUrl(): ?string
    {
        $photoUrl = null;

        $photoUrl = trim(
            stripslashes((string) $this->profile_photo_url),
            " \t\n\r\0\x0B\"'"
        );
        $photoUrl = str_replace('\\/', '/', $photoUrl);

        if (! $photoUrl) {
            return null;
        }

        if (str_starts_with($photoUrl, '//')) {
            return 'https:'.$photoUrl;
        }

        $photoHost = parse_url($photoUrl, PHP_URL_HOST);
        $photoScheme = strtolower((string) parse_url($photoUrl, PHP_URL_SCHEME));

        if (in_array($photoScheme, ['http', 'https'], true)) {
            $isGooglePhoto = $photoHost === 'google.com'
                || $photoHost === 'googleusercontent.com'
                || ($photoHost && str_ends_with($photoHost, '.google.com'))
                || ($photoHost && str_ends_with($photoHost, '.googleusercontent.com'));

            if ($isGooglePhoto && $photoScheme === 'http') {
                return 'https://'.substr($photoUrl, 7);
            }

            return $photoUrl;
        }

        return Storage::disk('public')->url(
            ltrim($photoUrl, '/')
        );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->hasAnyRole(self::ADMIN_ROLES);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Super Admin');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->status !== self::DEFAULT_STATUS) {
            return false;
        }

        return match ($panel->getId()) {
            'admin' => $this->hasAnyRole(self::ADMIN_ROLES),

            'client' => $this->hasRole('Client'),

            default => false,
        };
    }

    public function client()
    {
        return $this->hasOne(Client::class, 'user_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(
            Document::class,
            'user_id'
        );
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(
            Message::class,
            'sender_id'
        );
    }

    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(Calendar::class, 'user_id', 'id');
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(
            Conversation::class,
            'conversation_participants',
            'user_id',
            'conversation_id'
        )
            ->withPivot('joined_at')
            ->withTimestamps();
    }

    public function documentRequests(): HasMany
    {
        return $this->hasMany(DocumentRequest::class, 'user_id');
    }
}
