<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'document_id',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(
            Document::class,
            'document_id',
            'document_id'
        );
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}