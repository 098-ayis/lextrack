<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentTransmittal extends Model
{
    protected $primaryKey = 'transmittal_id';

    protected $fillable = [
        'document_id',
        'user_id',
        'file_path',
        'file_disk',
        'original_filename',
        'file_hash',
    ];

    public function storageDisk()
    {
        return \Illuminate\Support\Facades\Storage::disk(
            $this->file_disk ?: 'local'
        );
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id', 'document_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'id'
        )->withTrashed();
    }
}
