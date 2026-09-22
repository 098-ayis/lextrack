<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DocumentRequest extends Model
{
    protected $primaryKey = 'request_id';

    protected $fillable = [
        'document_id',
        'purpose',
        'purpose_details',
        'copy_type',
        'pickup_at',
        'rejection_reason',
        'attachment_path',
        'user_id',
        'status',
        'date_of_request',
        'date_processed',
    ];

    protected function casts(): array
    {
        return [
            'date_of_request' => 'date',
            'date_processed' => 'date',
            'pickup_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id', 'document_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class, 'document_request_id', 'request_id');
    }
}
