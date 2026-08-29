<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentRequest extends Model
{
    protected $primaryKey = 'request_id';

    protected $fillable = [
        'document_id',
        'purpose',
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
}
