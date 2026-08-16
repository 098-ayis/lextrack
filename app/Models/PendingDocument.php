<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendingDocument extends Model
{
    protected $fillable = [
        'document_type',
        'office_unit',
        'particulars',
        'status',
        'date_received',
        'file_path',
        'submitted_by',
    ];

    protected $casts = [
        'date_received' => 'date',
    ];

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}