<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Client;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use App\Models\Conversation;
use App\Models\Document;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]


class User extends Authenticatable implements HasAvatar, FilamentUser
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
    use HasApiTokens, HasFactory, Notifiable;
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
    ];

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->getProfilePhotoUrl();
    }

    public function getProfilePhotoUrl(): ?string
    {
        if (! $this->profile_photo_url) {
            return null;
        }

        if (
            str_starts_with($this->profile_photo_url, 'http://') ||
            str_starts_with($this->profile_photo_url, 'https://')
        ) {
            return $this->profile_photo_url;
        }

        return Storage::disk('public')->url(
            $this->profile_photo_url
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

    public function assignedConversations(): HasMany
    {
        return $this->hasMany(
            Conversation::class,
            'assigned_to'
        );
    }

    public function documentRequests(): HasMany
    {
        return $this->hasMany(DocumentRequest::class, 'user_id');
    }
}
