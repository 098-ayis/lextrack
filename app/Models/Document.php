<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    protected $fillable = [
        'name',
        'content',
        'type_id',
        'status',
        'sent_to',
        'sent_date',
        'returned_from',
        'date_returned',
        'outgoing_date',
        'uploaded_by',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }

    protected static function booted(): void
    {
        static::created(function (Document $document) {

            $document->conversation()->create([
                'user_id' => $document->user_id,
            ]);

        });
    }
}
