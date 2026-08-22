<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Client;
use App\Models\Conversation;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Filament\Models\Contracts\HasAvatar;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]

class User extends Authenticatable implements HasAvatar
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'provider',
        'profile_photo_url',
        'role_name',
        'status',
        'join_date',
        'last_login',
    ];

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->profile_photo_url;
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

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'user_id', 'id');
    }
    
    public function client()
    {
        return $this->hasOne(Client::class, 'user_id');
    }


    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(Calendar::class, 'user_id', 'id');
    }

    public function conversations(): HasManyThrough
    {
        return $this->hasManyThrough(
            Conversation::class,
            Document::class,
            'user_id',       
            'document_id',   
            'id',            
            'document_id'    
        );
    }
}
