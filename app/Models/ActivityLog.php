<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $primaryKey = 'log_id';

    protected $fillable = [
        'user_id',
        'document_id',
        'action_type',
        'action_details',
        'old_value',
        'new_value',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id', 'document_id');
    }
}
