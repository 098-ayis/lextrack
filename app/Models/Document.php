<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Document extends Model
{
    protected $primaryKey = 'document_id';
    
    protected $fillable = [
        'user_id',
        'type_id',
        'client_id',
        'lao_number',
        'office_unit',
        'particulars',
        'deadline',
        'action_taken',
        'sent_to',
        'sent_date',
        'returned_from',
        'date_returned',
        'outgoing_date',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'type_id', 'type_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'client_id');
    }

    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }

    protected static function booted(): void
    {
        static::created(function (Document $document) {

        $document->conversation()->create();

        });
    }
}