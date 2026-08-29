<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RejectedDocument extends Model
{
    protected $primaryKey = 'rejected_id';

    protected $fillable = [
        'document_id',
        'reason',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(
            Document::class,
            'document_id',
            'document_id'
        );
    }
}
