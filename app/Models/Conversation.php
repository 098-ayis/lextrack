<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'document_id',
        'created_by',
        'assigned_to',
        'status',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(
            Document::class,
            'document_id',
            'document_id'
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'assigned_to'
        );
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'conversation_participants',
            'conversation_id',
            'user_id'
        )
        ->withPivot('joined_at')
        ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(
            Message::class,
            'conversation_id'
        );
    }
    
}